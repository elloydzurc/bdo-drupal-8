<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/28/2019
 * Time: 2:15 PM
 */

namespace Drupal\bdo_enews_edm\Service;

use Drupal;
use Drupal\bdo_enews_edm\Service\Contract\DatabaseQuery;
use Drupal\Component\Datetime\Time;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;
use Exception;

class EnewsEdmService extends DatabaseQuery
{
    /**
     * @var AccountProxyInterface $user
     */
    protected $user;

    /**
     * @var LanguageManager $languageManager
     */
    protected $languageManager;

    /**
     * @var Time $time
     */
    protected $time;

    /**
     * @var TaxonomyTermService $taxonomyTermService
     */
    protected $taxonomyTermService;

    /**
     * @var NodeStorage $entityTypeManager
     */
    protected $nodeStorage;

    /**
     * EnewsEdmService constructor.
     * @param Connection $connection
     * @param AccountProxyInterface $user
     * @param LanguageManager $languageManager
     * @param Time $time
     * @param TaxonomyTermService $taxonomyTermService
     * @param EntityTypeManagerInterface $entityTypeManager
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(
        Connection $connection,
        AccountProxyInterface $user,
        LanguageManager $languageManager,
        Time $time,
        TaxonomyTermService $taxonomyTermService,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->user = $user;
        $this->languageManager = $languageManager;

        $this->time = $time;
        $this->taxonomyTermService = $taxonomyTermService;
        $this->nodeStorage = $entityTypeManager->getStorage('node');

        parent::__construct($connection, 'bdo_enews_edm_table');
    }

    /**
     * Preparing statement
     *
     * @return mixed
     * @throws EntityStorageException
     * @throws InvalidPluginDefinitionException
     */
    protected function prepare()
    {
        $args = $this->args;

        if (in_array($args['action'], ['create', 'update'])) {
            $nodeId = $args['nid'] ?? null;

            // Query for inserting
            if ($args['action'] == 'create' && !$nodeId) {
                $this->query = $this->connection->insert($this->table);
                $nodeId = $this->createNode()->id();
            }

            // Query for updating
            if ($args['action'] == 'update' && $nodeId) {
                $this->query = $this->connection->update($this->table);
            }

            // Set EDM field value
            if ($nodeId) {
                $templateData = [];
                $template = $this->taxonomyTermService->get($args['edm_template_list'], true);

                // Default Template data
                if ($template) {
                    $templateData['view_in_browser'] = $template->field_edm_view_in_browser->value ?? null;
                    $templateData['view_in_browser_format'] = $template->field_edm_view_in_browser->format ?? null;

                    $templateData['opt_out'] = $template->field_edm_opt_out->value ?? null;
                    $templateData['opt_out_format'] = $template->field_edm_opt_out->format ?? null;
                }

                // Template data defined by user
                if ($args['edm_editor']['value']) {
                    $templateData['view_in_browser'] = $args['edm_editor']['value'];
                    $templateData['view_in_browser_format'] = $args['edm_editor']['format'];
                }

                if ($args['optout_editor']['value']) {
                    $templateData['opt_out'] = $args['optout_editor']['value'];
                    $templateData['opt_out_format'] = $args['optout_editor']['format'];
                }

                // Populate table field
                $fields = [
                    'nid' => $nodeId,
                    'node_title' => $args['edm_title'],
                    'token' => $this->generateToken(),
                    'template' => $args['edm_template_list'],
                    'custom_token' => $args['custom_token_check'],
                    'custom_font' => $args['custom_font_check'],
                    'data' => serialize($templateData),
                    'createdby' => $this->user->id(),
                    'createddate' => $this->time->getRequestTime(),
                    'updatedby' => $this->user->id(),
                    'updateddate' => $this->time->getRequestTime()
                ];

                $this->query->fields($fields);
            }
        }

        // Getting eNews EDM record
        if ($args['action'] == 'get') {
            $this->query = $this->connection->select($this->table, 'enews');
            $this->query->fields('enews');
        }

        // Delete eNews EDM record and node
        if ($args['action'] == 'delete') {
            $this->query = $this->connection->delete($this->table);
            $this->nodeStorage->load($args['nid'])->delete();
        }

        return $this;
    }

    /**
     * Execute statement
     * @return mixed
     */
    protected function execute()
    {
        $args = $this->args;
        $transaction = $this->connection->startTransaction();

        try {
            // Condition for select, update and delete query
            if (in_array($args['action'], ['get', 'update', 'delete']) && isset($args['edmid'])) {
                $this->query->condition('edmid', $args['edmid']);
            }

            if (in_array($args['action'], ['get']) && isset($args['nid'])) {
                $this->query->condition('nid', $args['nid']);
            }

            $this->results = $this->query->execute();
        } catch (Exception $e) {
            $transaction->rollBack();
            Drupal::logger('bdo_enews_edm')->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Format rows data
     * @param array $results
     * @return mixed
     */
    protected function format(array $results)
    {
        return $results;
    }

    /**
     * Node page for newly created eNews EDM
     * @return EntityInterface|Node
     * @throws EntityStorageException
     */
    private function createNode()
    {
        $edm = $this->nodeStorage->create([
            'type' => 'page',
            'is_new' => 1,
            'status' => 1,
            'promote' => 0,
            'sticky' => 0,
            'uid' => $this->user->id(),
            'language' => $this->languageManager->getDefaultLanguage(),
            'theme' => 'bdo_blank',
            'title' => $this->args['edm_title'],
            'body' => [
                'value' => $this->args['edm_title'],
                'format' => 'email_alternate'
            ],
            'path' => [
                'alias' => $this->formatPath($this->args['edm_node_path']),
                'pathauto' => false
            ]
        ]);

        $edm->save();
        return $edm;
    }

    /**
     * Generate EDM Token
     */
    private function generateToken()
    {
        if ($this->args['custom_token_check']) {
            return $this->args['custom_token'];
        }

        $token = md5($this->args['edm_title']);
        $token = substr($token, 0, 8);

        return $token;
    }

    /**
     * Formal Path
     * @param $url
     * @return string
     */
    private function formatPath($url)
    {
        if ($url[0] != "/") {
            $url = '/' . $url;
        }
        return $url;
    }
}
