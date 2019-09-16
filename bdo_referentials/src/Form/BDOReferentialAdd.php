<?php

namespace Drupal\bdo_referentials\Form;

use Drupal\bdo_referentials\Form\Content\Description;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class BDOReferentialAdd extends ConfigFormBase
{
    /**
     * @var ConfigFactory $config
     */
    protected $settings;

    /**
     * @var array $existingOptions
     */
    protected $existingOptions;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        $this->settings = $this->config('bdo_referentials.settings');
        $this->existingOptions = $this->settings->get('type');
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
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'bdo_referential_add';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['referential'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add Referential'),
            '#collapsible' => false,
            '#collapsed' => false
        ];

        $form['referential']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#required' => true
        ];

        $form['referential']['variable'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Variable Name'),
            '#required' => true
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

        $form['referential']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => Url::fromRoute('bdo_referentials.index'),
        ];

        $form['referential']['save'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#ajax' => [
                'callback' => [$this, 'saveReferentialOptions'],
                'event' => 'click',
                'wrapper' => 'referential_options'
            ],
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
        // to do
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
        $options = $this->encodeOptions($input['options']);

        $variable = $input['variable'];
        $name = $input['name'];

        $response = new AjaxResponse();
        $message = '<p class="failed messages--error">Invalid options!</p>';

        if ($name && $variable) {
            if ($this->isExistingVariable($name, $variable)) {
                $message = '<p class="failed messages--error">Variable Name exist!</p>';
            }

            if ($options && !$this->isExistingVariable($name, $variable)) {
                $this->existingOptions[$variable]['name'] = $name;
                $this->existingOptions[$variable]['variable'] = $variable;

                $this->settings->set('type', $this->existingOptions);
                $this->settings->set($variable, $options);
                $this->settings->save();

                $message = '<p class="success messages--status">Referential Options Saved!</p>';
                $response->addCommand(new InvokeCommand(null, 'callback', ['clear', '']));
            }

            $response->addCommand(new InvokeCommand('#referential_message', 'html', [$message]));
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
     * Search if variable exists in config
     * @param $name
     * @param $variable
     * @return bool
     */
    private function isExistingVariable($name, $variable)
    {
        foreach ($this->existingOptions as $key => $option) {
            if ($option['variable'] === $variable || strtolower($option['name']) === strtolower($name)) {
                return true;
            }
        }
        return false;
    }
}