<?php

namespace Drupal\bdo_stories\Service;

use Drupal\bdo_stories\Service\Contract\AbstractDataQuery;
use Drupal\Core\Database\Connection;

class StoryListService extends AbstractDataQuery
{
    /**
     * StoryListService constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Preparing select statement
     *
     * @return mixed
     */
    protected function prepare()
    {
        $this->query = $this->connection->select('node_field_data', 'n');

        $this->query
            ->fields('n', ['nid', 'type', 'title', 'uid', 'status', 'changed'])
            ->fields('g', ['field_stories_type_target_id'])
            ->fields('w', ['field_stories_weight_value'])
            ->orderBy('field_stories_weight_value', 'ASC');

        return $this;
    }

    /**
     * @return mixed
     */
    protected function execute()
    {
        $this->query->condition('n.type', 'stories', '=');

        $this->query->leftJoin(
            'node__field_stories_weight',
            'w',
            "(w.entity_id = n.nid)"
        );

        $this->query->leftJoin(
            'node__field_stories_type',
            'g',
            "(g.entity_id = n.nid)"
        );

        if (isset($this->args['type'])) {
            $this->query->condition('field_stories_type_target_id', $this->args['type'], '=');
        }

        if (isset($this->args['title'])) {
            $this->query->condition('title', '%' . $this->args['title'] . '%', 'LIKE');
        }

        if (isset($this->args['status'])) {
            $this->query->condition('status', $this->args['status'], '=');
        }

        $this->results = $this->query->execute();

        return $this;
    }
}