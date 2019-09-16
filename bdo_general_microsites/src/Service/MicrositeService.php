<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/21/2019
 * Time: 12:53 PM
 */

namespace Drupal\bdo_general_microsites\Service;


use Drupal\bdo_general_microsites\Service\Contract\DatabaseQuery;
use Drupal\Core\Database\Connection;

class MicrositeService extends DatabaseQuery
{
    /**
     * MicrositeService constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->table = 'bdo_microsites';
        parent::__construct($connection, $this->table);
    }

    /**
     * Preparing select statement
     *
     * @return mixed
     */
    protected function prepare()
    {
        $this->query = $this->connection
            ->select($this->table, 'm');
        $this->query
            ->fields('m');

        return $this;
    }

    /**
     * @return mixed
     */
    protected function execute()
    {
        if (isset($this->args['msid'])) {
            $this->query->condition('msid', $this->args['msid']);
        }

        $this->results = $this->query->execute();

        return $this;
    }
}