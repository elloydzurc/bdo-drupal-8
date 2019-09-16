<?php

namespace Drupal\bdo_enews_edm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class BDOEnewsEdmDefaultConfig extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_enews_edm_add";
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = [];
        $edmService = \Drupal::service('bdo_enews_edm.enews_edm');
        $default_view_in = [];
        $default_opt_out = [];

        $view_in_browser = $edmService->getConfig('edm_view_in_browser_default');
        $opt_out = $edmService->getConfig('edm_opt_out_default');

        $default_view_in['value'] = $view_in_browser['value'];
        $default_view_in['format'] = $view_in_browser['format'];

        $default_opt_out['value'] = $opt_out['value'];
        $default_opt_out['format'] = $opt_out['format'];

        $default_opt_out = $edmService->getConfig('edm_opt_out_default');
        
        if ($edmService->getConfig('edm_valid_ip_addresses_for_email_page')) {
            $ip_addresses = implode("\r\n", $edmService->getConfig('edm_valid_ip_addresses_for_email_page'));
        } else {
            $ip_addresses = '';
        }

        $form['ip_address'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Valid IP Settings'),
            '#description' => $this->t(
                'Specify ip addresses that have access to edm enews email page. Enter one ip address per line.'
            ),
            '#resizable' => false,
            '#attributes' => [
                'spellcheck' => 'FALSE',
            ],
            '#required' => true,
            '#default_value' => $ip_addresses,
        ];


        $form['edm_view_in_browser'] = [
            '#type' => 'text_format',
            '#format' => $default_view_in['format'] ?? '',
            '#title' => 'View in browser text',
            '#default_value' => $default_view_in['value'] ?? '',
            '#prefix' => '<div style="margin-bottom: 30px;">',
            '#suffix' => '</div>',
        ];

        $form['edm_opt_out'] = [
            '#type' => 'text_format',
            '#format' => $default_opt_out['format'] ?? '',
            '#title' => 'Opt out / Unsubscribe',
            '#default_value' => $default_opt_out['value'] ?? '',
        ];

        $form['edm_save_content'] = [
            '#value' => $this->t('Save Settings'),
            '#type' => 'submit',
            '#attributes' => ['style' => ['margin-top: 20px;']],
        ];

        $form['edm_cancel'] = [
            '#value' => $this->t('Cancel'),
            '#type' => 'submit',
            '#limit_validation_errors' => [],
            '#attributes' => ['style' => ['position: relative; left: -21px;']],
            '#submit' => ['bdo_enews_edm_default_submit'],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();

        switch ($values['op']) {
            case 'Save Settings':
                if (empty($values['edm_view_in_browser']['value'])) {
                    \Drupal::messenger()->addError(
                        $this->t(
                            'An error occurred and processing did not complete. View in browser text cannot ' .
                            'be empty.'
                        )
                    );
                }

                if (empty($values['edm_opt_out']['value'])) {
                    \Drupal::messenger()->addError(
                        $this->t(
                            'An error occurred and processing did not complete. Opt out / Unsubscribe cannot ' .
                            'be empty.'
                        )
                    );
                }
                break;
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        $edmService = \Drupal::service('bdo_enews_edm.enews_edm');

        switch ($values['op']) {
            case 'Cancel':
                $form_state->setRebuild(true);
                $form_state->setRedirect('bdo_enews_edm.dashboard');
                break;

            case 'Save Settings':
                $view_in_browser = [];
                $view_in_browser['value'] = $values['edm_view_in_browser']['value'];
                $view_in_browser['format'] = 'email_alternate';

                $opt_out = [];
                $opt_out['value'] = $values['edm_opt_out']['value'];
                $opt_out['format'] = 'email_alternate';

                $edmService->saveConfig('bdo_enews_edm', 'edm_view_in_browser_default', $view_in_browser);
                $edmService->saveConfig('bdo_enews_edm', 'edm_opt_out_default', $opt_out);

                $ip_addresses = preg_split("/[\r\n]/", $values['ip_address'], -1, PREG_SPLIT_NO_EMPTY);
                $edmService->saveConfig('bdo_enews_edm', 'edm_valid_ip_addresses_for_email_page', $ip_addresses);

                \Drupal::messenger()->addStatus($this->t('Configuration successfully saved.'));

                break;
        }
    }
}