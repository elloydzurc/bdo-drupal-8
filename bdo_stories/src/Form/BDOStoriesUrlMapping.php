<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/12/2019
 * Time: 1:33 PM
 */

namespace Drupal\bdo_stories\Form;

use Drupal\bdo_stories\Exception\SettingsNameNotSetException;
use Drupal\bdo_stories\Form\Content\Description;
use Drupal\bdo_stories\Service\TaxonomyTermService;
use Drupal\bdo_stories\Service\UrlMappingService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOStoriesUrlMapping extends FormBase
{
    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var UrlMappingService $urlMappingService
     */
    protected $urlMappingService;

    /**
     * @var array $urlMappings
     */
    private $urlMappings;

    /**
     * @var array $storiesType
     */
    private $storiesType;

    /**
     * @param ContainerInterface $container
     * @return BDOStoriesUrlMapping|FormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('bdo_stories.taxonomy_term'),
            $container->get('bdo_stories.url_mapping')
        );
    }

    /**
     * BDOStoriesUrlMapping constructor.
     * @param TaxonomyTermService $taxonomyTermService
     * @param UrlMappingService $urlMappingService
     */
    public function __construct(TaxonomyTermService $taxonomyTermService, UrlMappingService $urlMappingService)
    {
        try {
            $this->taxonomyTermService = $taxonomyTermService;
            $this->storiesType = $this->taxonomyTermService->get('stories_type');

            $this->urlMappingService = $urlMappingService;
            $this->urlMappings = $this->urlMappingService->init('get');
        } catch (InvalidPluginDefinitionException | SettingsNameNotSetException $e) {
            \Drupal::logger('bdo_stories')->error($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_stories_url_mapping";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $mapId = 0, $action = null)
    {
        // Show other option when not on edit mode
        if ($action !== 'edit') {
            $this->unsetUsedOption();
        }

        // Fieldset on Add/Update
        if ($action !== 'delete') {
            $title = $action ? 'Update URL Mapping' : 'Add New URL Mapping';

            $form['add_new_url_mapping'] = [
                '#type' => 'fieldset',
                '#title' => $this->t($title),
                '#collapsible' => TRUE,
                '#collapsed' => FALSE
            ];

            $defaultOption = $action ? 'Default' : '- Select -';

            $form['add_new_url_mapping']['stories_type'] = [
                '#type' => 'select',
                '#title' => $this->t('Choose Stories Type'),
                '#options' => [$defaultOption] + $this->storiesType,
                '#required' => TRUE,
                '#description' => $this->t(Description::STORIES_TYPE),
                '#default_value' => $mapId,
                '#disabled' => $action ? TRUE : FALSE
            ];

            $defaultUrl = isset($this->urlMappings[$mapId]) ?
                $this->urlMappings[$mapId][1]['data'] : '';

            $form['add_new_url_mapping']['landing_page_url'] = [
                '#type' => 'textfield',
                '#title' => t('Landing Page URL'),
                '#description' => $this->t(Description::LANDING_PAGE_URL),
                '#required' => TRUE,
                '#default_value' => $defaultUrl
            ];

            $form['add_new_url_mapping']['add_mapping'] = [
                '#type' => 'submit',
                '#value' => $action ? 'Update' : 'Add'
            ];

            if ($action === 'edit') {
                $form['add_new_url_mapping']['cancel_mapping'] = [
                    '#type' => 'submit',
                    '#value' => 'Cancel'
                ];
            }
        }

        // Hide this section on edit/delete
        if (!$action) {
            $form['default_note'] = [
                '#markup' => $this->t(Description::DEFAULT_NOTE)
            ];

            $form['landing_page_url_table'] = [
                '#type' => 'table',
                '#header' => [
                    $this->t('Type'),
                    $this->t('Landing Page URL'),
                    $this->t('Operation')
                ],
                '#rows' => $this->urlMappings,
                '#empty' => 'No records found.',
                '#sticky' => true
            ];
        }

        // Form for deleting URL Mapping
        if ($action === 'delete') {
            $form = \Drupal::formBuilder()
                ->getForm('Drupal\bdo_stories\Form\BDOStoriesUrlMappingDeletion', $mapId);
        }

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
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $message = null;
            $input = $form_state->getValues();

            if (in_array($input['op'], ['Add', 'Update'])) {
                $storyType = $input['stories_type'];
                $storyTypeName = isset($this->storiesType[$storyType]) ?
                    $this->storiesType[$storyType] : 'Default';

                $data[$storyType] = [
                    'name' => $storyTypeName,
                    'url' => $input['landing_page_url']
                ];

                $this->urlMappingService->init('createOrUpdate', $data);
                $message = 'URL Mapping saved!';
            }

            if ($message) {
                $this->messenger()->addMessage($message);
            }

            $form_state->setRedirect('bdo_stories.url_mapping');

        } catch (SettingsNameNotSetException $e) {
            \Drupal::logger('bdo_stories')->error($e->getMessage());
        }
    }

    /**
     * Delete from stories type the used option
     */
    private function unsetUsedOption()
    {
        foreach ($this->urlMappings as $mapping) {
            if (($key = array_search($mapping[0]['data'], $this->storiesType))) {
                unset($this->storiesType[$key]);
            }
        }
    }
}