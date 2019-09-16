<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 2:07 PM
 */

namespace Drupal\bdo_stories\Plugin\Block;

use Drupal\bdo_stories\Service\FoundationStoryViewService;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 *
 * @Block(
 *      id = "bdo_stories_foundation_story_view_block",
 *      admin_label = @Translation("BDO Stories Foundation"),
 *      category = @Translation("BDO")
 * )
 */
class FoundationStoryViewBlock extends BlockBase implements ContainerFactoryPluginInterface
{
    /**
     * @var FoundationStoryViewService $foundationStoryView
     */
    protected $foundationStoryView;

    /**
     * @var array $args
     */
    private $args;

    /**
     * @var RequestStack $requestService
     */
    protected $requestService;

    /**
     * @var Request $request
     */
    private $request;

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
            $container->get('request_stack'),
            $container->get('bdo_stories.foundation_story_view')
        );
    }

    /**
     * FoundationListBlock constructor.
     * @param array $configuration
     * @param $plugin_id
     * @param $plugin_definition
     * @param RequestStack $requestService
     * @param FoundationStoryViewService $foundationStoryView
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RequestStack $requestService,
        FoundationStoryViewService $foundationStoryView
    ) {
        $this->request = $requestService->getCurrentRequest();
        $this->foundationStoryView = $foundationStoryView;
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     * @return array
     * @throws \Drupal\bdo_stories\Exception\NodeIdNotSetException
     */
    public function build()
    {
        $this->parseArgs();

        if (!$this->args['name']) {
            return [];
        }

        $content = $this->foundationStoryView
            ->render('/' . $this->args['name']);

        return $content;
    }

    /**
     * Parse the current url query string
     */
    private function parseArgs() : void
    {
        $queryStrings = explode('&', $this->request->getQueryString());

        foreach ($queryStrings as $string) {
            $array = explode('=', $string);

            if (count($array) > 1) {
                $this->args[$array[0]] = $array[1];
            }
        }
    }
}
