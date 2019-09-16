<?php

namespace Drupal\bdo_general_microsites\Form;

use Drupal\bdo_general_microsites\Batch\UserSettingBatchProcess;
use Drupal\bdo_general_microsites\Service\MicrositeService;
use Drupal\bdo_general_microsites\Service\UserService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GeneralMicrositesUsers extends ConfigFormBase
{
    /**
     * @var UserService $userService
     */
    protected $userService;

    /**
     * @var MicrositeService $micrositeService
     */
    protected $micrositeService;

    /**
     * @var $site
     */
    protected $site;

    /**
     * @var String $languageId
     */
    protected $languageId;

    /**
     * @var array $languages
     */
    protected $languages;

    /**
     * @var array $siteLanguages
     */
    protected $siteLanguages;

    /**
     * @var UserSettingBatchProcess $userSettingsBatchProcess
     */
    protected $userSettingsBatchProcess;

    /**
     * GeneralMicrositesUsers constructor.
     * @param ConfigFactoryInterface $config_factory
     * @param UserService $userService
     * @param MicrositeService $micrositeService
     * @param LanguageManager $languageManager
     * @param UserSettingBatchProcess $userSettingBatchProcess
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        UserService $userService,
        MicrositeService $micrositeService,
        LanguageManager $languageManager,
        UserSettingBatchProcess $userSettingBatchProcess
    ) {
        try {
            $this->userService = $userService;
            $this->micrositeService = $micrositeService;

            $this->languageId = $languageManager->getDefaultLanguage()->getId();
            $this->languages = $languageManager->getLanguages();

            $this->userSettingsBatchProcess = $userSettingBatchProcess;

            parent::__construct($config_factory);
        } catch (\Exception $e) {
            \Drupal::logger('bdo_general_microsites')->error($e->getMessage());
        }
    }

    /**
     * @param ContainerInterface $container
     * @return GeneralMicrositesUsers|ConfigFormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('config.factory'),
            $container->get('bdo_general_microsites.users'),
            $container->get('bdo_general_microsites.microsite'),
            $container->get('language_manager'),
            $container->get('bdo_general_microsites.user_settings_batch_process')
        );
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames()
    {
        return [
            'bdo_general_microsites.settings'
        ];
    }

    /**
     * Returns a unique string identifying the form.
     *
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return "bdo_general_microsites_users";
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @param String|null $id
     * @param String|null $action
     * @return array
     * @throws \Exception
     */
    public function buildForm(array $form, FormStateInterface $form_state, String $action = null, String $id = null)
    {
        if (!$id) {
            throw new Exception('Microsite ID must be supplied');
        }

        $users = $this->userService->get('multiple', [[
            'field' => 'status',
            'value' => 1
        ]]);

        $this->site = $this->micrositeService->data([
            'msid' => $id
        ]);

        $this->siteLanguages = unserialize($this->site->get('site_languages'));

        $form['machine_name'] = [
            '#type' => 'value',
            '#value' => $this->site->get('machine_name')
        ];

        $form['msid'] = [
            '#type' => 'value',
            '#value' => $this->site->get('msid')
        ];

        $form['multilingual'] = [
            '#type' => 'value',
            '#value' => $this->site->get('multilingual')
        ];

        $form['site_languages'] = [
            '#type' => 'value',
            '#value' => $this->siteLanguages
        ];

        $form['current_editors'] = [
            '#type' => 'value',
            '#value' => $this->site->get('editors')
        ];

        $form['current_publishers'] = [
            '#type' => 'value',
            '#value' => $this->site->get('publishers')
        ];

        $form['content_editing'] = [
            '#type' => 'container'
        ];

        $form['content_editing']['heading'] = [
            '#markup' => 'Content Editing/Publishing'
        ];

        $form['content_editing']['description'] = [
            '#markup' => 'Select the users that will have access and be able to edit and publish the contents/pages of 
                the microsite.'
        ];

        $form['default_content'] = [
            '#type' => 'fieldset',
            '#collapsible' => false,
            '#collapsed' => false,
            '#title' => $this->t('Default Content Editor/Publisher'),
            '#description' => 'Default Content Editor/Publisher manages the default language and 
                language neutral contents'
        ];

        $form['default_content']['default_editor'] = [
            '#type' => 'select',
            '#multiple' => true,
            '#title' => $this->t('Assign Default Content Editor'),
            '#options' => $users,
            '#required' => true,
            '#default_value' => unserialize($this->site->get('editors'))['default']
        ];

        $form['default_content']['default_publisher'] = [
            '#type' => 'select',
            '#multiple' => true,
            '#title' => $this->t('Assign Default Content Publisher'),
            '#options' => $users,
            '#required' => true,
            '#default_value' => unserialize($this->site->get('publishers'))['default']
        ];

        if ($this->site->get('multilingual')) {
            $form['language_content'] = [
                '#type' => 'fieldset',
                '#collapsible' => false,
                '#collapsed' => false,
                '#title' => $this->t('Language Specific Content Editor/Publisher'),
            ];

            foreach ($this->siteLanguages as $language) {
                if ($language != $this->languageId) {
                    $editor = $language . '_content_editor';
                    $publisher = $language . '_content_publisher';

                    $form['language_content'][$editor] = [
                        '#type' => 'select',
                        '#multiple' => true,
                        '#title' => $this->t('Assign ' . $this->languages[$language]->getName() . ' Content Editor'),
                        '#options' => $users,
                        '#required' => true,
                        '#default_value' => unserialize($this->site->get('editors')[$language])
                    ];

                    $form['language_content'][$publisher] = [
                        '#type' => 'select',
                        '#multiple' => true,
                        '#title' => t('Assign ' . $this->languages[$language]->getName() . ' Content Publisher'),
                        '#options' => $users,
                        '#required' => true,
                        '#default_value' => unserialize($this->site->get('publishers')[$language])
                    ];
                }
            }
        }

        $form['save'] = [
            '#type' => 'submit',
            '#value' => 'Save Users'
        ];

        return [
            $form,
            '#attached' => [
                'library' => [
                    'bdo_general_microsites/bdo_general_microsites_users',
                ],
            ]
        ];
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @throws Exception
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();
        $multiLingual = [];

        if ($input['op'] === 'Save Users') {
            if ($this->site->get('msid')) {
                // Default set of editors and publishers
                $editorsPublishers = array_unique(
                    array_merge($input['default_editor'], $input['default_publisher'])
                );

                if ($this->site->get('multilingual')) {
                    foreach ($this->siteLanguages as $language) {
                        if ($language != $this->languageId) {
                            // Mixed set of editors and publishers
                            $editorsPublishers = array_unique(array_merge(
                                $editorsPublishers,
                                $input[$language . '_content_editor'],
                                $input[$language . '_content_publisher']
                            ));
                            // Set of editors and publishers for specified language
                            $multiLingual = array_unique(array_merge(
                                $input[$language . '_content_editor'],
                                $input[$language . '_content_publisher']
                            ));
                        }
                    }
                }

                $batch = [
                    'title' => $this->t('User Settings...'),
                    'init_message'     => $this->t('Initializing'),
                    'progress_message' => $this->t('Processed @current out of @total.'),
                    'error_message'    => $this->t('An error occurred during processing'),
                    'finished' => [$this->userSettingsBatchProcess, 'finished'],
                ];

                $batch['operations'][] = [
                    [$this->userSettingsBatchProcess, 'processUsers'],
                    [$input, $editorsPublishers, $multiLingual]
                ];

                batch_set($batch);
            }
        }
    }
}