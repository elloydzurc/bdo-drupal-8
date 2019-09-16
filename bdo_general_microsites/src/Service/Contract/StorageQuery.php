<?php

namespace Drupal\bdo_general_microsites\Service\Contract;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Exception;

abstract class StorageQuery
{
    /**
     * @var EntityStorageInterface $storage
     */
    protected $storage;

    /**
     * @var String $module
     */
    protected $module;

    /**
     * @var $query
     */
    protected $query;

    /**
     * @var $results
     */
    protected $results;

    /**
     * @var $ids
     */
    protected $ids;

    /**
     * StorageQuery constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param String $storageType
     * @param String $module
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, String $storageType, String $module)
    {
        try {
            $this->module = $module;
            $this->storage = $entityTypeManager->getStorage($storageType);
        } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
            \Drupal::logger($this->module)->error($e->getMessage());
        }
    }

    /**
     * Get entity from storage
     * @param String $type Must be single or multiple type
     * @param array $conditions
     * @param bool $raw
     * @return mixed
     * @throws Exception
     */
    public function get(String $type, array $conditions = [], bool $raw = false)
    {
        if (!in_array($type, ['single', 'multiple'])) {
            throw new Exception('Invalid type');
        }

        $this->query = $this->storage->getQuery();

        foreach ($conditions as $condition) {
            if (array_key_exists('field', $condition) && array_key_exists('value', $condition)) {
                $operation = isset($condition['operation']) ? $condition['operation'] : '=';
                $this->query->condition($condition['field'], $condition['value'], $operation);
            }
        }

        $this->ids = $this->query->execute();

        if ($type === 'single') {
            $this->results = $this->storage->load($this->ids);
        }

        if ($type === 'multiple') {
            $this->results = $this->storage->loadMultiple($this->ids);
        }

        return $this->format($this->results, $raw);
    }

    /**
     * Format storage query results according to your will
     * @param $results
     * @param $raw
     * @return mixed
     */
    abstract protected function format($results, $raw);
}
