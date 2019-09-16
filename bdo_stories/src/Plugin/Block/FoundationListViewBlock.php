<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 2:07 PM
 */

namespace Drupal\bdo_stories\Plugin\Block;

use Drupal\bdo_stories\Service\FoundationListViewService;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @Block(
 *      id = "bdo_stories_foundation_list_view_block",
 *      admin_label = @Translation("BDO Stories Foundation"),
 *      category = @Translation("BDO")
 * )
 */
class FoundationListViewBlock extends BlockBase implements ContainerFactoryPluginInterface
{
    /**
     * @var FoundationListViewService $foundationListView
     */
    protected $foundationListView;

    /**
     * {@inheritdoc}
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        /** @noinspection PhpParamsInspection */
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('bdo_stories.foundation_list_view')
        );
    }

    /**
     * FoundationListBlock constructor.
     * @param array $configuration
     * @param $plugin_id
     * @param $plugin_definition
     * @param FoundationListViewService $foundationListView
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        FoundationListViewService $foundationListView
    ) {
        $this->foundationListView = $foundationListView;
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        return $this->foundationListView->render();
    }
}
