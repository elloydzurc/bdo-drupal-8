<?php

namespace Drupal\bdo_enews_edm\Service\Contract;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

abstract class Taxonomy
{
    /**
     * @var EntityTypeManager $entityTypeManager
     */
    protected $entityTypeManager;

    /**
     * @var $terms
     */
    protected $terms;

    /**
     * @var array $tree
     */
    protected $tree;

    /**
     * @var String $entityType
     */
    protected $entityType;

    /**
     * AbstractTaxonomyTerm constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Load taxonomy from Storage
     * @param String $vocabulary
     * @return mixed
     * @throws InvalidPluginDefinitionException
     */
    abstract protected function load(String $vocabulary);

    /**
     * Convert terms into array
     * @return mixed
     */
    abstract protected function buildTree();

    /**
     * Get terms or tree
     * @param String $term
     * @param bool $raw
     * @param String $entityType
     * @return array
     * @throws InvalidPluginDefinitionException
     */
    public function get(String $term, bool $raw = false, String $entityType = 'taxonomy_term')
    {
        $this->entityType = $entityType;
        $this->load($term)->buildTree();

        return $raw ? $this->terms : $this->tree;
    }
}
