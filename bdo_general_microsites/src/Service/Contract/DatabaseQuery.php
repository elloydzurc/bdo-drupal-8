<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/21/2019
 * Time: 12:54 PM
 */

namespace Drupal\bdo_general_microsites\Service\Contract;


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
     * @var $data
     */
    protected $data;

    /**
     * @var boolean $run
     */
    protected $isExecuted = false;

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
     * Preparing select statement
     *
     * @return mixed
     */
    abstract protected function prepare();

    /**
     * @return mixed
     */
    abstract protected function execute();

    /**
     * @param array $args
     * @return DatabaseQuery
     */
    public function data(array $args = [])
    {
        $this->args = $args;
        $this->prepare()->execute();
        $this->isExecuted = true;

        return $this;
    }

    /**
     * Get table rows
     * @return array
     */
    public function all()
    {
        $this->data();

        if ($this->results) {
            return $this->results->fetchAll();
        }
    }

    /**
     * Get table field value
     * @param String|null $field
     * @return string
     * @throws Exception
     */
    public function get(String $field = null)
    {
        if (!$this->isExecuted) {
            throw new Exception('You must execute ' . get_class($this) . '::data($args) before this method');
        }

        if (!$this->data) {
            $this->data = $this->results->fetchAssoc();
        }

        return $this->data[$field] ? $this->data[$field] : '';
    }

    /**
     * Update table
     * @param array $fields
     * @param array $conditions
     * @return mixed
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