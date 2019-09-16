<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/27/2019
 * Time: 5:39 PM
 */

namespace Drupal\bdo_enews_edm\Form;

use Drupal\bdo_enews_edm\Form\Library\Content;
use Drupal\bdo_enews_edm\Service\EnewsEdmService;
use Drupal\bdo_enews_edm\Service\TaxonomyTermService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOEnewsEdmAdd extends FormBase
{
    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var array $templateOptions
     */
    private $templateOptions;

    /**
     * @var EnewsEdmService $edmService
     */
    protected $edmService;

    /**
     * BDOEnewsEdmDashboard constructor.
     * @param TaxonomyTermService $taxonomyTermService
     * @param EnewsEdmService $edmService
     * @throws InvalidPluginDefinitionException
     */
    public function __construct(
        TaxonomyTermService $taxonomyTermService,
        EnewsEdmService $edmService
    ) {
        $this->taxonomyTermService = $taxonomyTermService;
        $this->templateOptions = $this->taxonomyTermService->get('bdo_enews_edm_templates');

        $this->edmService = $edmService;
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
            $container->get('bdo_enews_edm.taxonomy_term'),
            $container->get('bdo_enews_edm.enews_edm')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_enews_edm_add";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['action'] = [
            '#type' => 'value',
            '#value' => 'create'
        ];

        $form['instructions'] = [
            '#type' => 'details',
            '#title' => $this->t('Instructions'),
            '#open' => true
        ];

        $form['instructions']['text'] = [
            '#type' => '#markup',
            '#markup' => Content::INSTRUCTION
        ];

        $form['edm_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Content Title'),
            '#description' => Content::TITLE_DESC,
            '#maxlength' => 50,
            '#size' => 50,
            '#required' => true,
            '#default_value' => ''
        ];

        $form['edm_node_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path'),
            '#description' => Content::PATH_DESC,
            '#maxlength' => 255,
            '#size' => 60,
            '#required' => true,
            '#default_value' => ''
        ];

        $form['edm_template_list'] = [
            '#type' => 'select',
            '#title' => $this->t('Templates'),
            '#empty_option' => $this->t('Select Template'),
            '#description' => Content::TEMPLATE_DESC,
            '#options' => $this->templateOptions + ['010' => 'Custom'],
            '#required' => true,
            '#default_value' => '',
        ];

        $form['custom_font_check'] = [
            '#type' => 'checkbox',
            '#title' => 'Use Custom Font?',
            '#description' => 'Note: Only applicable to Landing Page Version',
            '#default_value' => '',
        ];

        $form['custom_token_check'] = [
            '#type' => 'checkbox',
            '#title' => 'Use Custom Token?',
            '#description' => 'If checked, you need to provide the Custom Token to be used.',
        ];

        $form['custom_token'] = [
            '#type' => 'textfield',
            '#title' => 'Custom Token',
            '#maxlength' => 100,
            '#states' => [
                'visible' => [
                    ':input[name=custom_token_check]' => ['checked' => true],
                ],
            ],
        ];

        $form['token_note'] = [
            '#type' => 'item',
            '#title' => 'Note:',
            '#markup' => Content::TOKEN_NOTE
        ];

        $form['edm_view'] = [
            '#type' => 'container'
        ];

        $form['edm_view']['instruction'] = [
            '#markup' => Content::EDM_NOTE
        ];

        $form['edm_view']['edm_editor'] = [
            '#type' => 'text_format',
            '#format' => 'email_alternate'
        ];

        $form['optout'] = [
            '#type' => 'container'
        ];

        $form['optout']['instruction'] = [
            '#markup' => Content::OPTOUT_NOTE
        ];

        $form['optout']['optout_editor'] = [
            '#type' => 'text_format',
            '#format' => 'email_alternate'
        ];

        $form['edm_save_content'] = [
            '#value' => $this->t('Save and Create Content'),
            '#type' => 'submit'
        ];

        $form['edm_cancel'] = [
            '#value' => $this->t('Cancel'),
            '#type' => 'submit',
            '#limit_validation_errors' => [],
            '#submit' => [
                [$this, 'cancelForm'],
            ]
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
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if (in_array($input['op'], ['Save and Create Content'])) {
            if ($input['custom_token_check'] && !$input['custom_token']) {
                $form_state->setErrorByName(
                    'custom_token',
                    'Custom Token is required!'
                );
            }

            if (!$input['edm_editor']['value']) {
                $form_state->setErrorByName(
                    'edm_editor',
                    'An error occurred and processing did not complete. View in browser text cannot be empty.'
                );
            }

            if (!$input['optout_editor']['value']) {
                $form_state->setErrorByName(
                    'optout_editor',
                    'An error occurred and processing did not complete. Opt-out/Unsubscribe cannot be empty.'
                );
            }
        }

        if (in_array($input['op'], ['Cancel'])) {
            $form_state->setRedirect('bdo_enews_edm.dashboard');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if (in_array($input['op'], ['Save and Create Content'])) {
            $data = [
                'action' => $input['action'],
                'edm_title' => $input['edm_title'],
                'edm_node_path' => $input['edm_node_path'],
                'edm_editor' => $input['edm_editor'],
                'optout_editor' => $input['optout_editor'],
                'custom_token_check' => $input['custom_token_check'],
                'custom_token' => $input['custom_token'],
                'custom_font_check' => $input['custom_font_check'],
                'edm_template_list' => $input['edm_template_list']
            ];

            if ($input['custom_token_check']) {
                $data['custom_token'] = $input['custom_token'];
            }

            $this->edmService->init($data);

            $this->messenger()->addMessage('eDM Content was created successfully.');
            $form_state->setRedirect('bdo_enews_edm.dashboard');
        }
    }

    /**
     * Dummy method to prevent validation on cancelling
     */
    public function cancelForm()
    {
        // Do nothing
    }
}
