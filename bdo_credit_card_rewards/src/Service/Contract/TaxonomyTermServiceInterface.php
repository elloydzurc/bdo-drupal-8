<?php

namespace Drupal\bdo_credit_card_rewards\Service\Contract;

interface TaxonomyTermServiceInterface
{
    /**
     * Get taxonomy tree of categories
     * @return array
     */
    public function getCategories() : array;

    /**
     * Get taxonomy tree of points range
     * @return array
     */
    public function getPointsRange() : array;
}
