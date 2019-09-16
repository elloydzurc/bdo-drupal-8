<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 2/1/2019
 * Time: 11:37 AM
 */

namespace Drupal\bdo_enews_edm\Event;

use Drupal\bdo_enews_edm\Service\EnewsEdmService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\AliasManager;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EmailTemplateViewerSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityStorageInterface|mixed|object $nodeStorage
     */
    protected $nodeStorage;

    /**
     * @var EnewsEdmService $edmServince
     */
    protected $edmService;

    /**
     * @var AliasManager $aliasManager
     */
    protected $aliasManager;

    /**
     * @var LanguageManager $languageManager
     */
    protected $languageManager;

    /**
     * @var $edm
     */
    private $edm;

    /**
     * @var Node $node
     */
    private $node;

    /**
     * EnewsEdmPathProcessorService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param EnewsEdmService $edmService
     * @param AliasManager $aliasManager
     * @param LanguageManager $languageManager
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EnewsEdmService $edmService,
        AliasManager $aliasManager,
        LanguageManager $languageManager
    ) {
        $this->nodeStorage = $entityTypeManager->getStorage('node');
        $this->edmService = $edmService;

        $this->aliasManager = $aliasManager;
        $this->languageManager = $languageManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::REQUEST][] = 'onRenderTemplateView';
        return $events;
    }

    /**
     * Change view rendered to email view
     * @param GetResponseEvent $event
     */
    public function onRenderTemplateView(GetResponseEvent $event)
    {
        $path = $event->getRequest()->getPathInfo();
        $pathArray = explode('/', $path);
        $last = array_pop($pathArray);

        if ($last == 'email') {
            $edmPath = implode('/', $pathArray);

            $edmInternalPath = $this->aliasManager->getPathByAlias(
                $edmPath,
                $this->languageManager->getDefaultLanguage()->getId()
            );

            if (preg_match('/node\/(\d+)/', $edmInternalPath, $node)) {
                $this->node = $this->nodeStorage->load($node[1]);

                $this->edm = $this->edmService->get([
                    'action' => 'get',
                    'nid' => $node[1]
                ]);

                $response = new Response();
                $response->setContent($this->processEmailBody());
                $event->setResponse($response);
            }
        }
    }

    /**
     * Replace content on eNews EDM template
     * @return mixed|String
     */
    private function processEmailBody()
    {
        $template = $this->node->get('body')->getValue()[0]['value'];
        $content = unserialize($this->edm[0]->data);

        $token = [
            'opt_out' => $content['opt_out'],
            'view_in_browser_text' => $this->replaceToken(
                $content['view_in_browser'],
                ['edm' => ['url_token' => $this->edm[0]->token]]
            )
        ];

        $template = $this->replaceToken($template, ['edm' => $token]);
        return $template;
    }

    /**
     * Replace token tag on markup
     * @param String $markup
     * @param array $replacement
     * @return mixed|String
     */
    private function replaceToken(String $markup, $replacement = [])
    {
        if (!$replacement) {
            $token = key($replacement);
            $data = current($replacement);
            foreach ($data as $key => $value) {
                $markup = str_replace('[' . $token . ':' . $key . ']', $value, $markup);
            }
        }
        return $markup;
    }
}