<?php

namespace Drupal\bdo_credit_card_rewards\Form;

use Drupal\bdo_credit_card_rewards\Service\TaxonomyTermService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreditCardRewardsForm extends FormBase
{
    /**
     * @var RequestStack $requestService
     */
    protected $requestService;

    /**
     * @var $request
     */
    private $request;

    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var array $categories
     */
    protected $categories;

    /**
     * @var array $pointsRange
     */
    protected $pointsRange;

    /**
     * @param ContainerInterface $container
     * @return CreditCardRewardsForm|FormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('request_stack'),
            $container->get('bdo_credit_card_rewards.taxonomy_term')
        );
    }

    /**
     * CreditCardRewardsForm constructor.
     * @param RequestStack $requestService
     * @param TaxonomyTermService $taxonomyTermService
     */
    public function __construct(
        RequestStack $requestService,
        TaxonomyTermService $taxonomyTermService
    ) {
        $this->taxonomyTermService = $taxonomyTermService;

        $this->requestService = $requestService;
        $this->request = $requestService->getCurrentRequest();

        $this->categories = $this->taxonomyTermService->getCategories();
        $this->pointsRange = $this->taxonomyTermService->getPointsRange();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "search_cc_rewards_form";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $defaultCategory = $this->request->get('cat') ?? 'All';

        $defaultPoints = !empty($this->request->get('pid')) >= 0 ?
            $this->request->get('pid') : 'All';

        $defaultKeyword = $this->request->get('key');

        $form['cc_rewards_wrapper'] = [
            '#markup' => '<div id="cc-rewards-wrapper">',
        ];

        $form['search_label'] = [
            '#markup' => '<div class="cc-rewards-label">Search for Rewards</div>',
        ];

        $form['cc_rewards_filter'] = [
            '#markup' => '<div class="cc-rewards-expose-filter-wrapper">',
        ];

        $form['opening_left_filter'] = [
            '#markup' => '<div class="left-filters">',
        ];

        $form['rewards_category'] = [
            '#type' => 'select',
            '#title' => $this->t('Category'),
            '#options' => ['All' => '- Any -'] + $this->categories,
            '#default_value' => array_search($defaultCategory, $this->categories)
        ];

        $form['rewards_points'] = [
            '#type' => 'select',
            '#title' => $this->t('Points'),
            '#options' => ['All' => 'Select Points'] + $this->pointsRange,
            '#default_value' => $defaultPoints
        ];

        $form['end_left_container'] = [
            '#markup' => '</div>',
        ];

        $form['opening_right_filter'] = [
            '#markup' => '<div class="right-filters">',
        ];

        $form['keyword'] = [
            '#type' =>'textfield',
            '#title' => $this->t('Keyword'),
            '#default_value' => $defaultKeyword,
            '#size' => 30,
            '#maxlength' => 30,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Find'),
            '#attributes' => [
                'id' => 'find-button'
            ],
        ];

        $form['bottom_wrapper'] = [
            '#markup' => '</div></div>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $path = $this->request->getPathInfo();
        $input = $form_state->getValues();
        $query = [];

        $range = [0, 99999];
        $keyword = null;
        $category = null;
        $min = null;
        $max = null;

        if ($input['keyword']) {
            $keyword = $input['keyword'];
            $query['key'] = $keyword;
        }

        if ($input['rewards_category'] != 'All') {
            $category = $this->categories[(int) $input['rewards_category']];
            $query['cat'] = $category;
        }

        if ($input['rewards_points'] != 'All') {
            $points = $this->pointsRange[(int) $input['rewards_points']];

            if (strpos($points, '-')) {
                $range = explode('-', $points);
            }

            if (strpos(strtolower($points), 'and')) {
                $range = explode('and', $points);
            }

            $min = (int) trim($range[0]);
            $query['pt_min'] = $min;
            $query['rp_min'] = $min;

            $max = (int) trim($range[1]);
            $query['pt_max'] = $max;
            $query['rp_max'] = $max;

            $query['pid'] = $input['rewards_points'];
        }

        if (strpos($path, 'results') === false) {
            $path .= substr($path, -1) == '/' ? '' : '/';
            $path .= 'results';
        }

        $form_state->setRedirectUrl(
            Url::fromUri('internal:' . $path, ['query' => $query])
        );
    }
}
