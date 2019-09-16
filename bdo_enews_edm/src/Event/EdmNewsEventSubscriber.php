<?php

namespace Drupal\bdo_enews_edm\Event;

use Drupal\bdo_enews_edm\Service\EnewsEdmService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\AliasManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\SafeMarkup;

class EdmNewsEventSubscriber implements EventSubscriberInterface
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
     * EnewsEdmPathProcessorService constructor.
     * @param EntityTypeManager $entityTypeManager
     * @param EnewsEdmService $edmService
     * @param AliasManager $aliasManager
     * @param LanguageManager $languageManager
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function __construct(
        EntityTypeManager $entityTypeManager,
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
        $events[KernelEvents::REQUEST][] = 'onEdmNewsInit';
        return $events;
    }

    public function onEdmNewsInit(GetResponseEvent $event)
    {
        $user = \Drupal::currentUser();

        $request_path = $event->getRequest()->getPathInfo();
        $exploded_path = explode('/', $request_path);

        if ($exploded_path[1] != 'node') {
            return;
        }

        if ($this->edmIsNumberOnly($exploded_path[2])) {
            $edm_content = $this->getEdmContentByNid($exploded_path[2]);

            if (!empty($edm_content) && !in_array('authenticated', $user->getRoles())) {
                $url = Url::fromRoute('<front>', []);
                $response = new RedirectResponse($url->toString());
                $response->send();
            }
        }

        $last_element = end($exploded_path);
        $count = ($last_element == 'email') ? count($exploded_path)-1 : count($exploded_path);
        $edm_path = '';

        for ($i=0; $i < $count; $i++) {
            $edm_path .= $exploded_path[$i] . '/';
        }

        $nid = $exploded_path[2];
        $edm_content = $this->getEdmContentByNid($nid);

        $valid_ip_addresses = $this->edmService->getConfig('edm_valid_ip_addresses_for_email_page');

        //check if authenticated user
        if (!in_array('authenticated', $user->getRoles())) {
            //check if in edm table
            if (!empty($edm_content)) {
                $token_from_url = SafeMarkup::checkPlain($_SERVER['QUERY_STRING']);
                $original_token = $edm_content->token;

                if (!empty($token_from_url)) {
                    if ($last_element == 'email') {
                        $ip = getenv('HTTP_X_FORWARDED_FOR');
                        if (empty($ip)) {
                            $ip = getenv('REMOTE_ADDR');
                        } else {
                            $string = preg_replace('/\s+/', '', $ip);
                            $temp = explode(',', $string);
                            $ip = $temp[count($temp)-1];
                        }

                        if (!in_array($ip, $valid_ip_addresses)) {
                            //invalid ip redirect to front page
                            $url = Url::fromRoute('<front>', []);
                            $response = new RedirectResponse($url->toString());
                            $response->send();
                        }
                    }

                    if ($token_from_url != $original_token) {
                        $url = Url::fromRoute('<front>', []);
                        $response = new RedirectResponse($url->toString());
                        $response->send();
                    }
                } else {
                    $url = Url::fromRoute('<front>', []);
                    $response = new RedirectResponse($url->toString());
                    $response->send();
                }
            }
        }
    }

    private function edmIsNumberOnly($number)
    {
        return (bool) preg_match("/^[1-9][0-9]*$/", $number);
    }

    private function getEdmContentByNid($nid)
    {
        // $service = \Drupal::service('bdo_enews_edm.enews_edm');
        $all_fields = array(
            'edmid',
            'nid',
            'node_title',
            'token',
            'template',
            'custom_token',
            'custom_font',
            'data',
            'createdby',
            'createddate',
            'updateddate'
        );

        return \Drupal::service('database')
            ->select('bdo_enews_edm_table', 'edm')
            ->fields('edm', $all_fields)
            ->condition('nid', $nid)
            ->execute()
            ->fetchObject();
    }
}