<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/26/2019
 * Time: 3:54 PM
 */

namespace Drupal\bdo_enews_edm\Service\Contract;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Statement;
use Exception;

abstract class DatabaseQuery
{
    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var Select $query
     */
    protected $query;

    /**
     * @var String  $table
     */
    protected $table;

    /**
     * @var Statement $results
     */
    protected $results;

    /**
     * @var array $args
     */
    protected $args;

    /**
     * DatabaseQuery constructor.
     * @param Connection $connection
     * @param String $table
     */
    public function __construct(Connection $connection, String $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Preparing statement
     *
     * @return mixed
     */
    abstract protected function prepare();

    /**
     * Execute statement
     * @return mixed
     */
    abstract protected function execute();

    /**
     * @param array $args
     * @return void
     */
    public function init(array $args = [])
    {
        $this->args = $args;
        $this->prepare()->execute();
    }

    /**
     * Get table rows
     * @param array $args
     * @param bool $formatted
     * @return array
     */
    public function get(array $args = [], $formatted = false)
    {
        $this->init($args);

        if ($this->results) {
            if ($formatted) {
                return $this->format($this->results->fetchAll());
            }
            return $this->results->fetchAll();
        }
    }

    /**
     * Format rows data
     * @param array $results
     * @return mixed
     */
    abstract protected function format(array $results);

    /**
     * Update table
     * @param array $fields
     * @param array $conditions
     */
    public function update(array $fields, array $conditions = null)
    {
        $this->query = $this->connection->update($this->table)
            ->fields($fields);

        if ($conditions) {
            foreach ($conditions as $condition) {
                $operation = isset($condition['operation']) ? $condition['operation'] : '=';
                $this->query->condition($condition['field'], $condition['value'], $operation);
            }
        }

        $this->query->execute();
    }

    /**
     * Close Database connection
     */
    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->destroy();
        }
    }
}
