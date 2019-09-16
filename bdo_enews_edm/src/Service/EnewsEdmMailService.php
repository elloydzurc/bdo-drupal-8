<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/31/2019
 * Time: 6:56 AM
 */

namespace Drupal\bdo_enews_edm\Service;

use Drupal\bdo_enews_edm\Batch\EmailBatchProcess;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class EnewsEdmMailService
{
    use StringTranslationTrait;

    /**
     * @var array $emails
     */
    private $emails;

    /**
     * @var EmailBatchProcess $emailBatchProcess
     */
    protected $emailBatchProcess;

    /**
     * EnewsEdmMailService constructor.
     * @param EmailBatchProcess $emailBatchProcess
     */
    public function __construct(EmailBatchProcess $emailBatchProcess)
    {
        $this->emailBatchProcess = $emailBatchProcess;
    }

    /**
     * @param String $nid
     * @param String $emails
     */
    public function send(String $nid, String $emails)
    {
        $this->parseEmail($emails);

        $batch = [
            'title' => $this->t('Sending Test Email...'),
            'init_message'     => $this->t('Initializing'),
            'progress_message' => $this->t('Sending @current out of @total.'),
            'error_message'    => $this->t('An error occurred during processing'),
            'finished' => [$this->emailBatchProcess, 'finished'],
        ];

        $batch['operations'][] = [
            [$this->emailBatchProcess, 'processEmails'],
            [(int)$nid, $this->emails]
        ];

        batch_set($batch);
    }

    /**
     * Parse email-addresses into an array
     * @param String $emails
     */
    public function parseEmail(String $emails)
    {
        $this->emails = array_unique(explode(',', $emails));
    }

    /**
     * Return bool if email is valid
     * @param String $email
     * @return false|int
     */
    public function validateEmail(String $email)
    {
        $pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:
        [\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f]
        )*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]
        |[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:
        [\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";

        return preg_match($pattern, $email);
    }
}