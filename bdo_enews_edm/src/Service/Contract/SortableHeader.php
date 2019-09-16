<?php

namespace Drupal\bdo_enews_edm\Service\Contract;

interface SortableHeader
{
    /**
     * Set sortable table header
     * @param array $header
     */
    public function setHeader(array $header) : void;

    /**
     * Get sortable table header
     * @return array
     */
    public function getHeader() : array;
}