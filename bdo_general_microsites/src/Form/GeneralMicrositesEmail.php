<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/22/2019
 * Time: 9:19 PM
 */

namespace Drupal\bdo_general_microsites\Form;

use Drupal;
use Drupal\bdo_general_microsites\Form\Definition\Tabs;
use Drupal\bdo_general_microsites\Form\Definition\Token;
use Drupal\bdo_general_microsites\Service\MicrositeService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxyInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GeneralMicrositesEmail extends FormBase
{
    /**
     * @var MicrositeService $micrositeService
     */
    protected $micrositeService;

    /**
     * @var AccountProxyInterface $currentUser
     */
    protected $currentUser;

    /**
     * @var Messenger $messenger
     */
    protected $messenger;

    /**
     * GeneralMicrositesUsers constructor.
     * @param Messenger $messenger
     * @param AccountProxyInterface $currentUser
     * @param MicrositeService $micrositeService
     */
    public function __construct(
        Messenger $messenger,
        AccountProxyInterface $currentUser,
        MicrositeService $micrositeService
    ) {
        $this->messenger = $messenger;
        $this->currentUser = $currentUser;
        $this->micrositeService = $micrositeService;
    }

    /**
     * @param ContainerInterface $container
     * @return GeneralMicrositesEmail
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('messenger'),
            $container->get('current_user'),
            $container->get('bdo_general_microsites.microsite')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'bdo_general_microsites_email';
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function buildForm(array $form, FormStateInterface $form_state, String $id = null, String $action = null)
    {
        if (!$id || !is_numeric($id)) {
            throw new Exception('Microsite ID must be numeric');
        }

        $site = $this->micrositeService->data([
            'msid' => $id
        ]);

        $settings = unserialize($site->get('notification_settings'));

        $form['msid'] = [
            '#type' => 'value',
            '#value' => $site->get('msid')
        ];

        $form['microsite_name'] = [
            '#type' => 'value',
            '#value' => $site->get('name')
        ];

        $form['email_settings'] = [
            '#type' => 'container'
        ];

        $form['email_settings']['recipients_list'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Microsite Email Notification Recipients'),
            '#resizable' => false,
            '#description' => 'Enter one recipient per line in the format:  <i>Name &lt;email@domain.com&gt;</i>.<br/>
                                e.g.<br/>BDO User 1 &lt;bdouser01@bdomail.com&gt;<br/>BDO User 2 
                                &lt;bdouser02@bdomail.com&gt;',
            '#default_value' => $settings['recipients'] ?? ''
        ];

        $form['email_settings']['email_options'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('Email Reminder')
        ];

        $form['email_settings']['email_options']['email_before_option'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Send email reminder'),
            '#default_value' => $settings['email_before_option'] ?? 0,
        ];

        $form['email_settings']['email_options']['email_days_before'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Days before'),
            '#size' => 10,
            '#maxlength' => 10,
            '#description' => 'Send an email reminder days before the actual action will take place. E.g. Send an email
                                reminder days before a microsite is deactived',
            '#states' => [
                'invisible' => [
                    ':input[name="email_before_option"]' => ['checked' => false],
                ]
            ],
            '#default_value' => $settings['email_days_before'] ?? '',
        ];

        $form['email_settings']['email_notifications_tab'] = [
            '#type' => 'vertical_tabs',
            '#title' => $this->t('Email Notification Messages'),
        ];

        // Generate tab items
        $this->generateTabItem($form, $settings);

        $form['email_settings']['tokens_available'] = [
            '#type' => 'details',
            '#title' => $this->t('Tokens'),
            '#open' => false,
            '#description' => $this->t('These are the available tokens that can be used for the email notification')
        ];

        $form['email_settings']['tokens_available']['tokens_list'] = [
            '#markup' => $this->generateTokenTable()
        ];

        $form['email_settings']['save'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save Email Settings')
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
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if ($input['op'] == 'Save Email Settings') {
            if ($input['email_before_option'] && !$input['email_days_before']) {
                $form_state->setErrorByName(
                    'email_days_before',
                    'Days before field is required.'
                );
            }

            if ($input['recipients_list']) {
                $recipients = explode(PHP_EOL, $input['recipients_list']);
                foreach ($recipients as $recipient) {
                    if (empty($this->parseEmail($recipient)) || stripos($recipient, "<") === false) {
                        $form_state->setErrorByName(
                            'recipients_list',
                            'Invalid recipient format. Must be in the format of: Name <email@domain.com>'
                        );
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if ($input['op'] == 'Save Email Settings') {
            $notificationSettings = [
                'recipients' => $input['recipients_list'],
                'email_before_option' => $input['email_before_option'],
                'email_days_before' => $input['email_days_before'],
                'enable_message' => [
                    'content' => $input['enable_microsite_content']['value'],
                    'format' => $input['enable_microsite_content']['format']
                ],
                'microsite_notification' => [
                    'content' => $input['microsite_content']['value'],
                    'format' => $input['microsite_content']['format']
                ],
                'reactivate_message' => [
                    'content' => $input['reactivate_microsite_content']['value'],
                    'format' => $input['reactivate_microsite_content']['format']
                ],
                'reminder_message' => [
                    'content' => $input['reminder_content']['value'],
                    'format' => $input['reminder_content']['format']
                ],
                'for_review' => [
                    'recipients' => $input['for_review_recipients'],
                    'subject' => $input['for_review_subject'],
                    'content' => $input['reminder_content']['value'],
                    'format' => $input['reminder_content']['format']
                ],
                'approve' => [
                    'recipients' => $input['approved_recipients'],
                    'subject' => $input['approved_subject'],
                    'content' => $input['approved_content']['value'],
                    'format' => $input['approved_content']['format']
                ],
                'disapprove' => [
                    'recipients' => $input['disapproved_recipients'],
                    'subject' => $input['disapproved_subject'],
                    'content' => $input['disapproved_content']['value'],
                    'format' => $input['disapproved_content']['format']
                ],
                'activate' => [
                    'recipients' => $input['activation_recipients'],
                    'subject' => $input['activation_subject'],
                    'content' => $input['activation_content']['value'],
                    'format' => $input['activation_content']['format']
                ],
                'deactivate' => [
                    'recipients' => $input['deactivation_recipients'],
                    'subject' => $input['deactivation_subject'],
                    'content' => $input['deactivation_content']['value'],
                    'format' => $input['deactivation_content']['format']
                ],
                'archive' => [
                    'recipients' => $input['archived_recipients'],
                    'subject' => $input['archived_subject'],
                    'content' => $input['archived_content']['value'],
                    'format' => $input['archived_content']['format']
                ],
                'purge' => [
                    'recipients' => $input['purged_recipients'],
                    'subject' => $input['purged_subject'],
                    'content' => $input['purged_content']['value'],
                    'format' => $input['purged_content']['format']
                ],
                'expire' => [
                    'recipients' => $input['expired_recipients'],
                    'subject' => $input['expired_subject'],
                    'content' => $input['expired_content']['value'],
                    'format' => $input['expired_content']['format']
                ]
            ];

            $fields = [
                'notification_settings' => serialize($notificationSettings),
                'uid' => $this->currentUser->id(),
                'changed' => Drupal::time()->getRequestTime()
            ];

            $conditions = [
                ['field' => 'msid', 'value' => $input['msid']]
            ];

            $this->micrositeService->update($fields, $conditions);
            $this->messenger->addMessage($input['microsite_name'] . ' Email Settings successfully saved!');
        }
    }

    /**
     * Generate Email Notification tab item
     * @param $form
     * @param $settings
     */
    private function generateTabItem(&$form, $settings)
    {
        if (Tabs::ITEMS) {
            foreach (Tabs::ITEMS as $key => $item) {
                $data = $item['data'];

                $form[$key] = [
                    '#type' => 'details',
                    '#title' => $this->t($item['title']),
                    '#group' => 'email_notifications_tab'
                ];

                if (isset($data['has_recipients']) && $data['has_recipients']) {
                    $form[$key][$key . '_recipients'] = [
                        '#title' => $this->t('Recipients'),
                        '#type' => 'select',
                        '#multiple' => true,
                        '#options' => Tabs::RECIPIENTS,
                        '#default_value' => $settings[$item['key']]['recipients'] ?? $data['default_recipients']
                    ];
                }

                if (isset($data['subject'])) {
                    $form[$key][$key . '_subject'] = [
                        '#title' => $this->t('Subject'),
                        '#type' => 'textfield',
                        '#default_value' => $settings[$item['key']]['subject'] ?? $data['subject']
                    ];
                }

                if (isset($data['content'])) {
                    $form[$key][$key . '_content'] = [
                        '#type' => 'text_format',
                        '#default_value' => $settings[$item['key']]['content'] ?? $data['content'],
                        '#format' => $settings[$item['key']]['format'] ?? 'full_html',
                    ];
                }
            }
        }
    }

    /**
     * Generate table for Token
     * @return string
     */
    private function generateTokenTable()
    {
        $table = '<table>' .
                    '<thead>' .
                        '<tr>' .
                            '<th class="token">Token</th>' .
                            '<th class="token-desc">Description</th>' .
                        '</tr>' .
                    '</thead>' .
                    '<tbody>';

        if (Token::ITEMS) {
            $index = 1;
            foreach (Token::ITEMS as $key => $token) {
                $table .= '<tr class="' . ($index % 2 == 0 ? 'even' : 'odd')  . '">' .
                                '<td class="token-name">[' . $key . ']</td>' .
                                '<td>' . $token . '</td>' .
                           '</tr>';
                $index++;
            }
        }

        $table .= '</tbody>' .
                    '</table>';

        return $table;
    }

    /**
     * Get email using regex
     * @param $recipient
     * @return mixed
     */
    private function parseEmail($recipient)
    {
        $emailPattern = "/<(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-
            \x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+
            [a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|
            [01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]
            |\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])>/";
        preg_match($emailPattern, $recipient, $match);

        return $match;
    }
}
