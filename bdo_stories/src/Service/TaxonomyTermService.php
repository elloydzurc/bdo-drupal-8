<?php

namespace Drupal\bdo_stories\Service;

use Drupal\bdo_stories\Service\Contract\AbstractTaxonomyTerm;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermStorage;

/**
 * Class TaxonomyTermService
 * @package Drupal\bdo_stories\Service
 */
class TaxonomyTermService extends AbstractTaxonomyTerm
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
     * @throws InvalidPluginDefinitionException
     */
    protected function load(String $vocabulary)
    {
        try {
            $parentTermId = 0;
            $depth = null;
            $this->terms = null;

            if (!$this->storage) {
                $this->storage = $this->entityTypeManager->getStorage('taxonomy_term');
            }

            $this->terms = $this->storage
                ->loadTree($vocabulary, $parentTermId, $depth, true);

            return $this;
        } catch (PluginNotFoundException $e) {
            \Drupal::logger('bdo_stories')->error($e->getMessage());
        }
    }

    /**
     * Convert terms into array
     * @return mixed
     */
    protected function buildTree()
    {
        $this->tree = [];

        if ($this->terms) {
            foreach ($this->terms as $term) {
                $prefix = $term->depth > 0 ? ' - ' : '';
                $this->tree[$term->id()] = $prefix . $term->get('name')->getValue()[0]['value'];
            }
        }
    }
}
