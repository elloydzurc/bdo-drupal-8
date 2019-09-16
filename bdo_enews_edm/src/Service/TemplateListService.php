<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/26/2019
 * Time: 6:51 PM
 */

namespace Drupal\bdo_enews_edm\Service;

use DateTime;
use Drupal\bdo_enews_edm\Service\Contract\DatabaseQuery;
use Drupal\bdo_enews_edm\Service\Contract\SortableHeader;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Exception;

class TemplateListService extends DatabaseQuery implements SortableHeader
{
    const DATETIME_FORMAT = 'm/d/Y h:i A';

    /**
     * An array of table header
     * @var array $header
     */
    private $header;

    /**
     * @var EntityTypeManager $entityTypeManager
     */
    protected $userStorage;

    /**
     * @var array $templateOptions
     */
    protected $templateOptions;

    /**
     * TemplateListService constructor.
     * @param Connection $connection
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param TaxonomyTermService $taxonomyTermService
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(
        Connection $connection,
        EntityTypeManagerInterface $entityTypeManager,
        TaxonomyTermService $taxonomyTermService
    ) {
        $this->userStorage = $entityTypeManager->getStorage('user');

        $this->templateOptions = $taxonomyTermService->get('bdo_enews_edm_templates');
        $this->templateOptions += ['010' => 'Custom'];

        parent::__construct($connection, 'bdo_enews_edm_table');
    }

    /**
     * Preparing select statement
     *
     * @return mixed
     */
    protected function prepare()
    {
        $this->query = $this->connection
            ->select($this->table, 'enews');
        $this->query
            ->fields('enews');

        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    protected function execute()
    {
        if (!$this->header) {
            throw new Exception("Table header must set first");
        }

        if (isset($this->args['template'])) {
            $this->query->condition('template', $this->args['template']);
        }

        if (isset($this->args['title'])) {
            $this->query->condition('node_title', '%' . $this->args['title'] . '%', 'LIKE');
        }

        $this->results = $this->query
                ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                ->limit(20)
                ->extend('Drupal\Core\Database\Query\TableSortExtender')
                ->orderByHeader($this->header)
                ->execute();

        return $this;
    }

    /**
     * Set sortable table header
     * @param array $header
     */
    public function setHeader(array $header): void
    {
        $this->header = $header;
    }

    /**
     * Get sortable table header
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * Format rows data
     * @param $results
     * @return mixed
     * @throws Exception
     */
    protected function format(array $results)
    {
        $rows = [];

        foreach ($results as $result) {
            $user = $this->userStorage->load($result->createdby);
            $rows[] = [
                ['data' => $result->nid],
                ['data' => $result->node_title],
                ['data' => new FormattableMarkup('@landing <br/> @email', [
                    '@landing' => $this->generatePageLinks($result->nid, $result->token),
                    '@email' => $this->generatePageLinks($result->nid, $result->token, true)
                ])],
                ['data' => $this->templateOptions[$result->template]],
                ['data' => $user->getUsername() ?? ''],
                ['data' => $this->toDateTime($result->createddate)],
                ['data' => $this->toDateTime($result->updateddate)],
                ['data' => new FormattableMarkup('@update <br/> @edit <br/> @delete <br/> @send', [
                    '@update' => $this->generateSettingsLink($result->edmid),
                    '@edit' => $this->generateContentLink($result->nid),
                    '@delete' => $this->generateDeleteLink($result->edmid),
                    '@send' => $this->generateSendTestEmailLink($result->nid)
                ])]
            ];
        }

        return $rows;
    }

    /**
     * Generate template links
     * @param $nodeId
     * @param $token
     * @param bool $email
     * @return GeneratedLink
     */
    private function generatePageLinks($nodeId, $token, $email = false)
    {
        $url = Url::fromRoute(
            'entity.node.canonical',
            ['node' => $nodeId],
            ['absolute' => true]
        );

        if ($email) {
            $url = Url::fromUri($url->toString() . '/email');
        }

        $url->setOption('query', [
            $token => null
        ]);

        $link = Link::fromTextAndUrl($url->toString(), $url);
        return $link->toString();
    }

    /**
     * Generate update settings link
     * @param $edmId
     * @return GeneratedLink
     */
    private function generateSettingsLink($edmId)
    {
        $url = Url::fromRoute(
            'bdo_enews_edm.settings',
            ['edmid' => $edmId]
        );

        $link = Link::fromTextAndUrl('update settings', $url);
        return $link->toString();
    }

    /**
     * Generate node edit link
     * @param $nodeId
     * @return GeneratedLink
     */
    private function generateContentLink($nodeId)
    {
        $url = Url::fromRoute(
            'entity.node.edit_form',
            ['node' => $nodeId]
        );

        $link = Link::fromTextAndUrl('edit content', $url);
        return $link->toString();
    }

    /**
     * Generate node delete link
     * @param $edmId
     * @return GeneratedLink
     */
    private function generateDeleteLink($edmId)
    {
        $url = Url::fromRoute(
            'bdo_enews_edm.delete',
            ['edmid' => $edmId]
        );

        $link = Link::fromTextAndUrl('delete content', $url);
        return $link->toString();
    }

    /**
     * Generate update settings link
     * @param $nodeId
     * @return GeneratedLink
     */
    private function generateSendTestEmailLink($nodeId)
    {
        $url = Url::fromRoute(
            'bdo_enews_edm.send_mail',
            ['nid' => $nodeId]
        );

        $link = Link::fromTextAndUrl('send test email', $url);
        return $link->toString();
    }

    /**
     * Convert datetime to readable date
     * @param $date
     * @return string
     * @throws \Exception
     */
    private function toDateTime($date)
    {
        $formattedDate = null;

        // Check if not null and timestamp
        if ($date && is_numeric($date)) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($date);
            $formattedDate = $dateTime->format(self::DATETIME_FORMAT);
        }

        // Check if not null and string date
        if ($date && !is_numeric($date)) {
            $dateTime = new DateTime($date);
            $formattedDate = $dateTime->format(self::DATETIME_FORMAT);
        }

        return $formattedDate;
    }
}
