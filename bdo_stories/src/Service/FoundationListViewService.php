<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 2:12 PM
 */

namespace Drupal\bdo_stories\Service;

use Drupal\bdo_stories\Service\Contract\AbstractViewBlock;

class FoundationListViewService extends AbstractViewBlock
{
    /**
     * @var UrlMappingService $urlMappingService
     */
    protected $urlMappingService;

    /**
     * FoundationListViewService constructor.
     * @param UrlMappingService $urlMappingService
     */
    public function __construct(UrlMappingService $urlMappingService)
    {
        $this->urlMappingService = $urlMappingService;
        parent::__construct('bdo_stories', 'foundation_list_view');
    }

    /**
     * Configure view. eg: attached library and etc.
     */
    protected function configure()
    {
        // Get page node alias of bdo foundation
        $urlMap = $this->urlMappingService->init('get');
        $url = $urlMap['3274'][1]['data'];

        $this->view->element['#attached']['library'][] = 'bdo_stories/bdo_stories_foundation';
        $this->view->element['#attached']['drupalSettings']['bdo']['foundation']['stories']['url'] = $url;
    }
}