<?php

namespace Drupal\bdo_stories\Service\Contract;

use Drupal\Core\Database\Connection;

abstract class AbstractDataQuery
{
    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var $query
     */
    protected $query;

    /**
     * @var $results
     */
    protected $results;

    /**
     * @var array $args
     */
    protected $args;

    /**
     * AbstractDataQuery constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
     * Converting results set into an array
     * @param array $args
     * @return array
     */
    public function data(array $args = [])
    {
        $this->args = $args;
        $this->prepare()->execute();

        return $this->results;
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