<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/31/2019
 * Time: 4:27 PM
 */

namespace Drupal\bdo_enews_edm\Batch;

use Drupal\bdo_enews_edm\Service\EnewsEdmService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;

class EmailBatchProcess
{
    use DependencySerializationTrait, StringTranslationTrait;

    const LIMIT = 1;

    /**
     * @var NodeStorage $nodeStorage
     */
    protected $nodeStorage;

    /**
     * @var Node $node
     */
    private $node;

    /**
     * @var $edm
     */
    private $edm;

    /**
     * @var EnewsEdmService $edmService
     */
    protected $edmService;

    /**
     * @var Messenger $messenger
     */
    protected $messenger;

    /**
     * @var ImmutableConfig $siteConfig
     */
    protected $siteConfig;

    /**
     * @var MailManager $mailManager
     */
    protected $mailManager;

    /**
     * @var LanguageManager $languageManager
     */
    protected $languageManager;

    /**
     * SortBatchProcess constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param Messenger $messenger
     * @param ConfigFactory $configFactory
     * @param EnewsEdmService $edmService
     * @param MailManager $mailManager
     * @param LanguageManager $languageManager
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Messenger $messenger,
        ConfigFactory $configFactory,
        EnewsEdmService $edmService,
        MailManager $mailManager,
        LanguageManager $languageManager
    ) {
        $this->nodeStorage = $entityTypeManager->getStorage('node');
        $this->edmService = $edmService;

        $this->messenger = $messenger;
        $this->siteConfig = $configFactory->get('system.site');

        $this->mailManager = $mailManager;
        $this->languageManager = $languageManager;

        $this->node = null;
        $this->edm = null;
    }

    /**
     * Processor for batch operations.
     * @param int $nodeId
     * @param array $emails
     * @param array $context
     */
    public function processEmails(
        int $nodeId,
        array $emails,
        array &$context
    ) {
        $counter = 0;

        // Set default progress values.
        if (empty($context['sandbox']['progress'])) {
            $context['sandbox']['progress'] = 0;
            $context['sandbox']['max'] = count($emails);
        }

        // Save emails to array which will be changed during processing.
        if (empty($context['sandbox']['emails'])) {
            $context['sandbox']['emails'] = $emails;
        }

        if (!empty($context['sandbox']['emails'])) {
            // Remove already processed emails.
            if ($context['sandbox']['progress'] != 0) {
                array_splice($context['sandbox']['emails'], 0, self::LIMIT);
            }

            // Load eNews EDM
            if (!$this->edm) {
                $this->edm = $this->edmService->get([
                    'action' => 'get',
                    'nid' => $nodeId
                ]);
            }

            // Load eNews EDM node
            if (!$this->node) {
                $this->node = $this->nodeStorage->load($nodeId);
            }

            foreach ($context['sandbox']['emails'] as $email) {
                if ($counter != self::LIMIT) {
                    $this->processEmail($email);

                    $counter++;
                    $context['sandbox']['progress']++;

                    $context['message'] = $this->t('Now processing node :progress of :count', [
                        ':progress' => $context['sandbox']['progress'],
                        ':count' => $context['sandbox']['max'],
                    ]);

                    // Increment total processed user values. Will be used in finished callback
                    $context['results']['processed'] = $context['sandbox']['progress'];
                }
            }
        }

        // If not finished all tasks, we count percentage of process. 1 = 100%.
        if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
            $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        }
    }

    /**
     * Add role to user. Processed single user at a time
     * @param $recipient
     */
    public function processEmail($recipient)
    {
        $params = [
            'from' => $this->siteConfig->get('mail'),
            'subject' => $this->node->getTitle(),
            'message' => $this->processEmailBody(),
            'title' => $this->node->getTitle()
        ];

        $this->mailManager->mail(
            'bdo_enews_edm',
            'test_email',
            $recipient,
            $this->languageManager->getDefaultLanguage(),
            $params
        );
    }

    /**
     * Update Microsite editors and publishers list
     * @param $success
     * @param $results
     * @param $operations
     */
    public function finished($success, $results, $operations)
    {
        $message = $this->t(
            'Number of email sent: @count',
            ['@count' => $results['processed']]
        );

        $this->messenger
            ->addMessage($message);
    }

    /**
     * Replace content on eNews EDM template
     * @return mixed|String
     */
    private function processEmailBody()
    {
        $template = $this->node->get('body')->getValue()[0]['value'];
        $content = unserialize($this->edm[0]->data);

        $token = [
            'opt_out' => $content['opt_out'],
            'view_in_browser_text' => $this->replaceToken(
                $content['view_in_browser'],
                ['edm' => ['url_token' => $this->edm[0]->token]]
            )
        ];

        $template = $this->replaceToken($template, ['edm' => $token]);
        return $template;
    }

    /**
     * Replace token tag on markup
     * @param String $markup
     * @param array $replacement
     * @return mixed|String
     */
    private function replaceToken(String $markup, $replacement = [])
    {
        if (!$replacement) {
            $token = key($replacement);
            $data = current($replacement);
            foreach ($data as $key => $value) {
                $markup = str_replace('[' . $token . ':' . $key . ']', $value, $markup);
            }
        }
        return $markup;
    }
}
