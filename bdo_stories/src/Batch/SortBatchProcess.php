<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/11/2019
 * Time: 1:47 PM
 */

namespace Drupal\bdo_stories\Batch;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\node\NodeStorage;

class SortBatchProcess
{
    const LIMIT = 20;

    /**
     * @var NodeStorage $nodeStorage
     */
    protected $nodeStorage;

    /**
     * @var Messenger $messenger
     */
    protected $messenger;

    /**
     * SortBatchProcess constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param Messenger $messenger
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Messenger $messenger
    ) {
        $this->nodeStorage = $entityTypeManager->getStorage('node');
        $this->messenger = $messenger;
    }

    /**
     * Processor for batch operations.
     * @param $items
     * @param array $context
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function processItems($items, array &$context)
    {
        $counter = 0;

        // Set default progress values.
        if (empty($context['sandbox']['progress'])) {
            $context['sandbox']['progress'] = 0;
            $context['sandbox']['max'] = count($items);
        }

        // Save items to array which will be changed during processing.
        if (empty($context['sandbox']['items'])) {
            $context['sandbox']['items'] = $items;
        }

        if (!empty($context['sandbox']['items'])) {
            // Remove already processed items.
            if ($context['sandbox']['progress'] != 0) {
                array_splice($context['sandbox']['items'], 0, self::LIMIT);
            }

            foreach ($context['sandbox']['items'] as $item) {
                if ($counter != self::LIMIT) {
                    $this->processItem($item);

                    $counter++;
                    $context['sandbox']['progress']++;

                    $context['message'] = t('Now processing node :progress of :count', [
                        ':progress' => $context['sandbox']['progress'],
                        ':count' => $context['sandbox']['max'],
                    ]);

                    // Increment total processed item values. Will be used in finished callback
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
     * Process single item.
     * @param $item
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function processItem($item)
    {
        $node = $this->nodeStorage->load($item['nid']);
        $node->field_stories_weight = $item['weight'];
        $node->save();
    }

    /**
     * @param $success
     * @param $results
     * @param $operations
     */
    public function finished($success, $results, $operations)
    {
        $message = t('Number of nodes affected by batch: @count', [
            '@count' => $results['processed']
        ]);

        $this->messenger
             ->addMessage($message);
    }
}
