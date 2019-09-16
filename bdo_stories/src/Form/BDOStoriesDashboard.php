<?php

namespace Drupal\bdo_stories\Form;

use DateTime;
use Drupal\bdo_stories\Batch\SortBatchProcess;
use Drupal\bdo_stories\Service\StoryListService;
use Drupal\bdo_stories\Service\TaxonomyTermService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BDOStoriesDashboard extends FormBase
{
    const DATETIME_FORMAT = 'm/d/Y h:i A';

    /**
     * @var RequestStack $requestStack
     */
    protected $requestStack;

    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var StoryListService $storyListService
     */
    protected $storyListService;

    /**
     * @var SortBatchProcess $ortBatchProcess
     */
    protected $sortBatchProcess;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var array $storiesType
     */
    private $storiesType;

    /**
     * @var array $args
     */
    private $args;

    /**
     * @param ContainerInterface $container
     * @return BDOStoriesDashboard|FormBase
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('request_stack'),
            $container->get('bdo_stories.taxonomy_term'),
            $container->get('bdo_stories.list'),
            $container->get('bdo_stories.sort')
        );
    }

    /**
     * BDOStoriesDashboard constructor.
     * @param RequestStack $requestStack
     * @param TaxonomyTermService $taxonomyTermService
     * @param StoryListService $storyListService
     * @param SortBatchProcess $sortBatchProcess
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     */
    public function __construct(
        RequestStack $requestStack,
        TaxonomyTermService $taxonomyTermService,
        StoryListService $storyListService,
        SortBatchProcess $sortBatchProcess
    ) {
        $this->requestStack = $requestStack;
        $this->request = $this->requestStack->getCurrentRequest();

        $this->taxonomyTermService = $taxonomyTermService;
        $this->storiesType = $this->taxonomyTermService->get('stories_type');

        $this->sortBatchProcess = $sortBatchProcess;

        $this->storyListService = $storyListService;
        $this->parseArgs();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_stories_dashboard";
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['filter'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('SHOW ITEMS BY:'),
            '#collapsible' => false,
            '#collapsed' => false
        ];

        $form['filter']['type'] = [
            '#type' => 'select',
            '#title' => $this->t('Story Type'),
            '#options' => $this->storiesType,
            '#empty_option' => '- Select -',
            '#default_value' => $this->args['type'] ?? ''
        ];

        $form['filter']['title'] = [
            '#type' =>'textfield',
            '#title' => $this->t('Title'),
            '#default_value' => $this->args['title'] ?? '',
            '#size' => 60,
            '#maxlength' => 128,
        ];

        $form['filter']['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Status'),
            '#options' => [
                'not published',
                'published'
            ],
            '#empty_option' => '- Select -',
            '#default_value' => $this->args['status'] ?? ''
        ];

        $form['filter']['actions'] = [
            '#type' => 'container'
        ];

        $form['filter']['actions']['apply'] = [
            '#type' => 'submit',
            '#value' => 'Filter'
        ];

        $form['filter']['actions']['reset'] = [
            '#type' => 'submit',
            '#value' => 'Reset'
        ];

        $form['story_list'] = [
            '#type' => 'table',
            '#header' => [
                $this->t(''),
                $this->t('Title'),
                $this->t('Stories Type'),
                $this->t('Status'),
                $this->t('Author'),
                $this->t('Update Date'),
                $this->t('Weight'),
                $this->t('Operations')
            ],
            '#empty' => $this->t('There are no items yet.'),
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => 'story_list-order-weight',
                ]
            ]
        ];

        foreach ($this->storyListService->data($this->args) as $id => $data) {
            $form['story_list'][$id]['#attributes']['class'][] = 'draggable';
            $form['story_list'][$id]['#weight'] = $data->field_stories_weight_value;

            $form['story_list'][$id]['nid'] = [
                '#type' => 'hidden',
                '#default_value' => $data->nid
            ];

            $form['story_list'][$id]['title'] = [
                '#markup' => $this->toTitle($data->title, $data->nid)
            ];

            $form['story_list'][$id]['stories_type'] = [
                '#plain_text' => $this->toType($data->field_stories_type_target_id)
            ];

            $form['story_list'][$id]['status'] = [
                '#plain_text' => $this->toStatus($data->status)
            ];

            $form['story_list'][$id]['author'] = [
                '#plain_text' => $this->toUser($data->uid)
            ];

            $form['story_list'][$id]['update_date'] = [
                '#plain_text' => $this->toDateTime($data->changed)
            ];

            $form['story_list'][$id]['weight'] = [
                '#type' => 'weight',
                '#title' => $this->t('Weight for Story'),
                '#title_display' => 'invisible',
                '#default_value' => $data->field_stories_weight_value,
                '#attributes' => ['class' => ['story_list-order-weight']]
            ];

            $form['story_list'][$id]['operations'] = [
                '#type' => 'operations',
                '#links' => [
                    'edit' => $this->toEditNodeLink($data->nid),
                    'delete' => $this->toDeleteNodeLink($data->nid)
                ]
            ];
        }

        $form['actions'] = [
            '#type' => 'actions'
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save Changes'),
            '#tableselect' => true
        ];

        return [
            $form,
            '#attached' => [
                'library' => [
                    'bdo_stories/bdo_stories_dashboard',
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();
        $params = [];

        if ($input['op'] == 'Save Changes') {
            $list = [];

            foreach ($form_state->getValue('story_list') as $item) {
                $list[] = [
                    'nid' => (int) $item['nid'],
                    'weight' => (int) $item['weight']
                ];
            }

            usort($list, [$this, 'sortWeight']);

            $batch = [
                'title' => $this->t('Sorting Stories...'),
                'init_message'     => $this->t('Initializing'),
                'progress_message' => $this->t('Processed @current out of @total.'),
                'error_message'    => $this->t('An error occurred during processing'),
                'finished' => [$this->sortBatchProcess, 'finished'],
            ];

            $batch['operations'][] = [[$this->sortBatchProcess, 'processItems'], [$list]];

            batch_set($batch);
        }

        if (in_array($input['op'], ['Filter', 'Reset'])) {
            if ($input['title'] != "") {
                $params['title'] = $input['title'];
            }

            if ($input['type'] != "") {
                $params['type'] = $input['type'];
            }

            if ($input['status'] != "") {
                $params['status'] = $input['status'];
            }

            // Reset Filters
            if ($input['op'] == 'Reset') {
                $params = [];
            }

            $form_state->setRedirect('bdo_stories.dashboard', $params);
        }
    }

    /**
     * Parse the current url query string
     */
    private function parseArgs()
    {
        $this->args = [];

        $queryStrings = explode('&', $this->request->getQueryString());

        foreach ($queryStrings as $string) {
            $array = explode('=', $string);

            if (count($array) > 1) {
                $this->args[$array[0]] = $array[1];
            }
        }
    }

    /**
     * Convert title to link
     * @param String $title
     * @param $nodeId
     * @return string
     */
    private function toTitle(String $title, $nodeId)
    {
        $url = Url::fromRoute(
            'entity.node.canonical',
            ['node' => $nodeId]
        );

        $link = Link::fromTextAndUrl($title, $url);

        return $link->toString();
    }

    /**
     * Get string value of story type
     * @param $storyTypeId
     * @return string
     */
    private function toType($storyTypeId)
    {
        $name = null;

        if ($storyTypeId) {
            $term = Term::load($storyTypeId);
            $name = $term->getName();
        }

        return $name;
    }

    /**
     * Get status of the story
     * @param $statusId
     * @return string
     */
    private function toStatus($statusId)
    {
        $prefix = $statusId > 0 ? '' : 'not ';
        return $prefix . 'published';
    }

    /**
     * Get the name of the user
     * @param $userId
     * @return string
     */
    private function toUser($userId)
    {
        $name = null;

        if ($userId) {
            $user = User::load($userId);
            $name = $user->getUsername();
        }

        return $name;
    }

    /**
     * Convert datetime to readable date
     * @param $date
     * @return string
     * @throws \Exception
     */
    private function toDateTime($date)
    {
        $formattedDate = null;

        // Check if not null and timestamp
        if ($date && is_numeric($date)) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($date);
            $formattedDate = $dateTime->format(self::DATETIME_FORMAT);
        }

        // Check if not null and string date
        if ($date && !is_numeric($date)) {
            $dateTime = new DateTime($date);
            $formattedDate = $dateTime->format(self::DATETIME_FORMAT);
        }

        return $formattedDate;
    }

    /**
     * Generate edit link for the node
     * @param $nodeId
     * @return array|mixed[]
     */
    private function toEditNodeLink($nodeId)
    {
        $url = Url::fromRoute(
            'entity.node.edit_form',
            ['node' => $nodeId]
        );

        return [
            'title' => 'edit',
            'url' => $url
        ];
    }

    /**
     * Generate delete link for the node
     * @param $nodeId
     * @return array|mixed[]
     */
    private function toDeleteNodeLink($nodeId)
    {
        $url = Url::fromRoute(
            'entity.node.delete_form',
            ['node' => $nodeId]
        );

        return [
            'title' => 'delete',
            'url' => $url
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    private static function sortWeight($a, $b)
    {
        if (isset($a['weight']) && isset($b['weight'])) {
            return $a['weight'] < $b['weight'] ? -1 : 1;
        }

        return 0;
    }
}
