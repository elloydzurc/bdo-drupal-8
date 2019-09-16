<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/30/2019
 * Time: 2:46 PM
 */

namespace Drupal\bdo_enews_edm\Form;

use Drupal\bdo_enews_edm\Service\EnewsEdmMailService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOEnewsEdmEmailTest extends FormBase
{
    /**
     * @var EnewsEdmMailService $edmMailService
     */
    protected $edmMailService;

    /**
     * BDOEnewsEdmEmailTest constructor.
     * @param EnewsEdmMailService $edmMailService
     */
    public function __construct(EnewsEdmMailService $edmMailService)
    {
        $this->edmMailService = $edmMailService;
    }

    /**
     * @param ContainerInterface $container
     * @return BDOEnewsEdmEmailTest|FormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('bdo_enews_edm.mailer')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_enews_bdo_email_test";
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function buildForm(array $form, FormStateInterface $form_state, String $nid = null)
    {
        if (!is_numeric($nid)) {
            throw new Exception('Invalid Node ID');
        }

        $form['nid'] = [
            '#type' => 'value',
            '#value' => $nid
        ];

        $form['email_addresses'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Test Email Recipients'),
            '#description' => $this->t('Comma Delimited. Format is: ' .
                'bdouser01@bdo.com.ph,bdouser02@bdo.com.ph,bdouser03@bdo.com.ph etc.'),
            '#resizable' => false,
            '#attributes' => [
                'spellcheck' => 'FALSE'
            ],
            '#required' => true
        ];

        $form['send'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send Test Email'),
            '#weight' => 350,
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

        if ($input['op'] == 'Send Test Email') {
            $this->edmMailService->send(
                $input['nid'],
                $input['email_addresses']
            );
        }
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if ($input['op'] == 'Send Test Email') {
            if (!$input['email_addresses']) {
                $form_state->setErrorByName(
                    'email_addresses',
                    'An error occurred and processing did not complete. Email Address cannot be empty.'
                );
            }

            $emails = explode(',', $input['email_addresses']);

            foreach ($emails as $email) {
                if (!$this->edmMailService->validateEmail($email)) {
                    $form_state->setErrorByName(
                        'email_addresses',
                        'Invalid email address supplied: ' . $email
                    );
                }
            }
        }
    }
}