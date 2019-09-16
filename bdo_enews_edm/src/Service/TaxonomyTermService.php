<?php

namespace Drupal\bdo_enews_edm\Service;

use Drupal\bdo_enews_edm\Service\Contract\Taxonomy;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;

/**
 * Class TaxonomyTermService
 * @package Drupal\bdo_stories\Service
 */
class TaxonomyTermService extends Taxonomy
{
    /**
     * @var TermStorage $storage
     */
    private $storage;

    /**
     * @var array $tree
     */
    protected $tree;

    /**
     * @var array $terms
     */
    protected $terms;

    /**
     * TaxonomyTermService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        parent::__construct($entityTypeManager);
    }

    /**
     * Load taxonomy from Storage
     * @param String $vocabulary
     * @return mixed
     */
    protected function load(String $vocabulary)
    {
        try {
            $parentTermId = 0;
            $depth = null;
            $this->terms = null;

            if (!$this->storage) {
                $this->storage = $this->entityTypeManager->getStorage($this->entityType);
            }

            // Load taxonomy by ID
            if (is_numeric($vocabulary)) {
                $this->terms = $this->storage->load($vocabulary);
            }

            // Load taxonomy by name
            if (!is_numeric($vocabulary)) {
                $this->terms = $this->storage
                    ->loadTree($vocabulary, $parentTermId, $depth, true);
            }

            return $this;
        } catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
            \Drupal::logger('bdo_enews_edm')->error($e->getMessage());
        }
    }

    /**
     * Convert terms into array
     */
    protected function buildTree()
    {
        $this->tree = [];

        if ($this->terms) {
            foreach ($this->terms as $term) {
                if ($term instanceof Term) {
                    $prefix = $term->depth > 0 ? ' - ' : '';
                    $this->tree[$term->id()] = $prefix . $term->get('name')->getValue()[0]['value'];
                }
            }
        }
    }
}
