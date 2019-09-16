<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 2:12 PM
 */

namespace Drupal\bdo_stories\Service;

use Drupal\bdo_stories\Service\Contract\AbstractPageBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManager;

class FoundationStoryViewService extends AbstractPageBuilder
{
    /**
     * FoundationStoryViewService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param AliasManager $aliasManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, AliasManager $aliasManager)
    {
        $this->channel = 'bdo_stories';

        parent::__construct($entityTypeManager, $aliasManager);
    }

    /**
     * Allow to configure node elements
     */
    protected function configure()
    {
        $this->node->set('field_stories_teaser', null);
        $this->node->set('field_stories_thumbnail', null);
        $this->node->set('field_stories_weight', null);
        $this->node->set('comment_node_stories', null);
        $this->node->set('field_stories_type', null);
    }

    /**
     * Allow to  attached library and etc to page
     */
    protected function attached()
    {
        // TODO: Implement attached() method.
    }
}
