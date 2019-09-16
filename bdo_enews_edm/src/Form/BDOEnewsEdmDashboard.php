<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/26/2019
 * Time: 3:19 PM
 */

namespace Drupal\bdo_enews_edm\Form;

use Drupal\bdo_enews_edm\Service\TemplateListService;
use Drupal\bdo_enews_edm\Service\TaxonomyTermService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BDOEnewsEdmDashboard extends FormBase
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var TemplateListService $templateListService
     */
    protected $templateListService;

    /**
     * @var array $templateList
     */
    private $templateList;

    /**
     * @var array $templateOptions
     */
    private $templateOptions;

    /**
     * @var array $args
     */
    private $args;

    /**
     * BDOEnewsEdmDashboard constructor.
     * @param RequestStack $requestStack
     * @param TaxonomyTermService $taxonomyTermService
     * @param TemplateListService $templateListService
     * @throws InvalidPluginDefinitionException
     */
    public function __construct(
        RequestStack $requestStack,
        TaxonomyTermService $taxonomyTermService,
        TemplateListService $templateListService
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->parseArgs();

        $this->taxonomyTermService = $taxonomyTermService;
        $this->templateOptions = $this->taxonomyTermService->get('bdo_enews_edm_templates');

        $this->templateListService = $templateListService;
    }

    /**
     * @param ContainerInterface $container
     * @return BDOEnewsEdmDashboard|FormBase
     * @throws InvalidPluginDefinitionException
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('request_stack'),
            $container->get('bdo_enews_edm.taxonomy_term'),
            $container->get('bdo_enews_edm.templates')
        );
    }

    /**
     * {@inheritdoc|
     */
    public function getFormId()
    {
        return "bdo_enews_edm_dashboard";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['filter'] = [
            '#type' => 'details',
            '#title' => $this->t('Filter'),
            '#open' => true
        ];

        $form['filter']['template'] = [
            '#type' => 'select',
            '#title' => $this->t('Templates'),
            '#options' => $this->templateOptions + ['010' => 'Custom'],
            '#empty_option' => 'Select Template',
            '#default_value' => $this->args['template'] ?? 0
        ];

        $form['filter']['title'] = [
            '#type' =>'textfield',
            '#title' => $this->t('Title'),
            '#default_value' => $this->args['title'] ?? '',
        ];

        $form['filter']['reset'] = [
            '#type' => 'submit',
            '#value' => 'Reset'
        ];

        $form['filter']['apply'] = [
            '#type' => 'submit',
            '#value' => 'Filter'
        ];

        $form['filter']['edm_templates'] = [
            '#type' => 'link',
            '#title' => $this->t('Manage eNews eDM Templates'),
            '#url' => Url::fromRoute(
                'entity.taxonomy_vocabulary.overview_form',
                ['taxonomy_vocabulary' => 'bdo_enews_edm_templates']
            ),
        ];

        $form['filter']['default_settings'] = [
            '#type' => 'link',
            '#title' => $this->t('Configure default EDM settings'),
            '#url' => Url::fromRoute('bdo_enews_edm.default_settings'),
        ];

        $form['add_edm'] = [
            '#type' => 'submit',
            '#value' => 'Create New eNews eDM Content'
        ];

        $header = [
            ['data' => 'NID', 'field' => 'nid'],
            ['data' => 'Title'],
            ['data' => 'Landing Page / Email URL'],
            ['data' => 'Template'],
            ['data' => 'Created By', 'field' => 'createdby'],
            ['data' => 'Created Date', 'field' => 'createddate'],
            ['data' => 'Updated Date', 'field' => 'updateddate', 'sort' => 'desc'],
            ['data' => 'Operations']
        ];

        $this->templateListService->setHeader($header);
        $this->templateList = $this->templateListService->get($this->args, true);

        $form['story_list'] = [
            '#type' => 'table',
            '#empty' => $this->t('There are no items yet.'),
            '#header' => $this->templateListService->getHeader(),
            '#rows' => $this->templateList
        ];

        $form['pager'] = [
            '#type' => 'pager'
        ];

        return [
            $form,
            '#attached' => [
                'library' => [
                    'bdo_enews_edm/bdo_enews_edm_dashboard',
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();
        $params = [];

        if (in_array($input['op'], ['Filter', 'Reset'])) {
            if ($input['title'] != '') {
                $params['title'] = $input['title'];
            }

            if ($input['template'] != '') {
                $params['template'] = $input['template'];
            }

            // Reset Filters
            if ($input['op'] == 'Reset') {
                $params = [];
            }

            $form_state->setRedirect('bdo_enews_edm.dashboard', $params);
        }

        if (in_array($input['op'], ['Create New eNews eDM Content'])) {
            $form_state->setRedirect('bdo_enews_edm.add');
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
}