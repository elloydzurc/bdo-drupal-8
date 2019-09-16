<?php

namespace Drupal\bdo_credit_card_rewards\Service\Contract;

interface SearchResultServiceInterface
{
    /**
     * @param array $args
     * @return mixed
     */
    public function query(array $args);

    /**
     * @return mixed
     */
    public function buildView();
}