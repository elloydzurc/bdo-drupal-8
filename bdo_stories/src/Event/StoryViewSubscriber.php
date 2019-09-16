<?php

namespace Drupal\bdo_stories\Event;

use Drupal\bdo_stories\Service\UrlMappingService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StoryViewSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityStorageInterface|mixed|object $nodeStorage
     */
    protected $nodeStorage;

    /**
     * @var AliasManager $aliasManager
     */
    protected $aliasManager;

    /**
     * @var UrlMappingService $urlMappingService
     */
    protected $urlMappingService;

    /**
     * EnewsEdmPathProcessorService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param AliasManager $aliasManager
     * @param UrlMappingService $urlMappingService
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AliasManager $aliasManager,
        UrlMappingService $urlMappingService
    ) {
        $this->nodeStorage = $entityTypeManager->getStorage('node');
        $this->aliasManager = $aliasManager;
        $this->urlMappingService = $urlMappingService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::REQUEST][] = 'onStoryView';
        return $events;
    }

    /**
     * Redirect to story landing page
     * @param GetResponseEvent $event
     * @throws \Drupal\bdo_stories\Exception\SettingsNameNotSetException
     */
    public function onStoryView(GetResponseEvent $event)
    {
        $url = $event->getRequest()->getPathInfo();
        $path = $this->aliasManager->getPathByAlias($url);

        if (preg_match('/node\/(\d+)/', $path, $matches)) {
            $node = $this->nodeStorage->load($matches[1]);
            if ($node && $node->getType() == 'stories') {
                $urlMap = $this->urlMappingService->init('get');
                $landingPage = $urlMap['3274'][1]['data'];

                $response = new RedirectResponse('/' . $landingPage . '?name=' . ltrim($url, '/'));
                $event->setResponse($response);
            }
        }
    }
}
