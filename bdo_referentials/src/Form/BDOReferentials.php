<?php

namespace Drupal\bdo_referentials\Form;

use Drupal\bdo_referentials\Form\Content\Description;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOReferentials extends ConfigFormBase
{
    use DependencySerializationTrait;

    /**
     * @var ConfigFactory $configFactory
     */
    protected $configFactory;

    /**
     * @var $settings $config
     */
    protected $settings;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
        $this->settings = $this->configFactory->getEditable('bdo_referentials.settings');
    }

    /**
     * @param ContainerInterface $container
     * @return BDOReferentials|ConfigFormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('config.factory')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_referentials_index";
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['add_referential'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add Referential')
        ];

        $form['referential'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Referential Options'),
            '#collapsible' => false,
            '#collapsed' => false
        ];

        $form['referential']['type'] = [
            '#type' => 'select',
            '#title' => $this->t('Referential Type'),
            '#options' => $this->getTypeOptions(),
            '#empty_option' => '- Select -',
            '#default_value' => '',
            '#ajax' => [
                'callback' => [$this, 'getReferentialTypeOptions'],
                'event' => 'change',
                'wrapper' => 'referential_options',
            ],
        ];

        $form['referential']['options'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Allowed values list'),
            '#description' => $this->t(Description::ALLOWED_LIST),
            '#resizable' => true,
            '#attributes' => [
                'id' => 'referential_options'
            ]
        ];

        $form['referential']['message'] = [
            '#markup' => '<div id="referential_message"></div>'
        ];

        $form['referential']['save'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#ajax' => [
                'callback' => [$this, 'saveReferentialOptions'],
                'event' => 'click'
            ],
        ];

        $form['referential']['delete'] = [
            '#type' => 'submit',
            '#value' => $this->t('Delete'),
            '#states' => [
                'visible' => [
                    ':input[name="type"]' => ['!value' => '']
                ],
                'invisible' => [
                    ':input[name="type"]' => ['value' => '']
                ]
            ],
            '#ajax' => [
                'callback' => [$this, 'deleteReferentialOption'],
                'event' => 'click'
            ]
        ];

        return [
            $form,
            '#attached' => [
                'library' => [
                    'bdo_referentials/bdo_referentials_admin',
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

        if ($input['op'] == 'Add Referential') {
            $form_state->setRedirect('bdo_referentials.add');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'bdo_referentials.settings',
        ];
    }

    /**
     * Get the referential types
     * @return array
     */
    private function getTypeOptions()
    {
        $options = [];

        foreach ($this->settings->get('type') as $type) {
            $options[$type['variable']] = $type['name'];
        }

        return $options;
    }

    /**
     * Get allowed options by Referential type
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function getReferentialTypeOptions(array $form, FormStateInterface $form_state)
    {
        $content = null;
        $input = $form_state->getValues();
        $responses = new AjaxResponse();

        if ($input['type']) {
            $content = $this->decodeOptions($input['type']);
        }

        $responses->addCommand(new InvokeCommand('#referential_options', 'val', [$content]));
        return $responses;
    }

    /**
     * Update Referential options
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function saveReferentialOptions(array $form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();
        $options = $input['options'];
        $type = $input['type'];

        $response = new AjaxResponse();
        $message = '<p class="success messages--status">Referential Options Saved!</p>';

        if ($type) {
            if (!$this->encodeOptions($options)) {
                $message = '<p class="failed messages--error">Invalid options!</p>';
            }

            if ($options = $this->encodeOptions($options)) {
                $this->settings->set($type, $options)->save();
            }

            $response->addCommand(new InvokeCommand('#referential_message', 'html', [$message]));
        }

        return $response;
    }

    /**
     * Delete referential type and option
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function deleteReferentialOption(array $form, FormStateInterface $form_state)
    {
        // Always get the updated config
        $this->settings = $this->configFactory->getEditable('bdo_referentials.settings');

        $input = $form_state->getValues();
        $type = $input['type'];
        $response = new AjaxResponse();

        if ($type) {
            $keyword = str_replace('referential_', '', $type);
            $referentialType = $this->settings->get('type');

            unset($referentialType[$keyword]);

            $this->settings->set('type', $referentialType);
            $this->settings->clear($type);
            $this->settings->save();

            $response->addCommand(new InvokeCommand(null, 'callback', ['removeOptions', $type]));
        }

        return $response;
    }

    /**
     * Encode options to array
     * @param String $options
     * @return array|bool
     */
    public function encodeOptions(String $options)
    {
        $encodedOptions = [];
        $lineOptions = preg_split('/$\R?^/m', trim($options));

        foreach ($lineOptions as $item) {
            if (strlen($item) > 0 && strpos($item, '|') === false) {
                return false;
            }

            $option = explode('|', $item);
            $encodedOptions[$option[0]] = $option[1];
        }

        return $encodedOptions;
    }

    /**
     * Convert Options to readable text
     * @param String $type
     * @return string|null
     */
    public function decodeOptions(String $type)
    {
        // Always get the updated config
        $this->settings = $this->configFactory->getEditable('bdo_referentials.settings');
        $content = null;

        foreach ($this->settings->get($type) as $key => $config) {
            $content .= $key . '|' . $config . PHP_EOL;
        }

        return $content;
    }
}