<?php

namespace Drupal\bdo_credit_card_rewards\Plugin\Block;

use Drupal\bdo_credit_card_rewards\Service\SearchResultService;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 *
 * @Block(
 *      id = "bdo_credit_card_rewards_results_block",
 *      admin_label = @Translation("BDO Credit Card Rewards Result"),
 *      category = @Translation("BDO")
 * )
 */
class CreditCardRewardsResultsBlock extends BlockBase implements ContainerFactoryPluginInterface
{
    /**
     * @var array $args
     */
    private $args;

    /**
     * @var RequestStack $requestService
     */
    protected $requestService;

    /**
     * @var $request
     */
    private $request;

    /**
     * @var SearchResultService $searchResultService
     */
    protected $searchResultService;

    /**
     * CreditCardRewardsResultsBlock constructor.
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RequestStack $requestService,
        SearchResultService $searchResultService
    ) {
        $this->searchResultService = $searchResultService;
        $this->requestService = $requestService;

        $this->request = $this->requestService->getCurrentRequest();
        $this->args = [];

        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('request_stack'),
            $container->get('bdo_credit_card_rewards.search_result')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this->parseArgs();
        return $this->searchResultService->query($this->args)->buildView();
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
