<?php

namespace Drupal\bdo_credit_card_rewards\Service;

use Drupal\bdo_credit_card_rewards\Service\Contract\TaxonomyTermServiceInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermStorage;

/**
 * Class TaxonomyTermService
 * @package Drupal\bdo_credit_card_rewards\Service
 */
class TaxonomyTermService implements TaxonomyTermServiceInterface
{
    /**
     * @var EntityTypeManager $entityTypeManager
     */
    protected $entityTypeManager;

    /**
     * @var TermStorage $storage
     */
    private $storage;

    /**
     * @var array $tree
     */
    private $tree;

    /**
     * @var array $terms
     */
    private $terms;

    /**
     * TaxonomyTermService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Get taxonomy terms
     * @param String $vocabulary
     * @return $this
     */
    private function load(String $vocabulary)
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
        } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
            \Drupal::logger('bdo_credit_card_rewards')->error($e->getMessage());
        }
    }

    /**
     * Build taxonomy tree
     */
    private function buildTree() : void
    {
        $this->tree = [];

        if ($this->terms) {
            foreach ($this->terms as $term) {
                $this->tree[] = $term->get('name')->getValue()[0]['value'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories() : array
    {
        $this->load('credit_cards_rewards_category')->buildTree();
        return $this->tree;
    }

    /**
     * {@inheritdoc}
     */
    public function getPointsRange() : array
    {
        $this->load('credit_card_rewards_points_range')->buildTree();
        return $this->tree;
    }
}
