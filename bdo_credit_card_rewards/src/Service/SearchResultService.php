<?php

namespace Drupal\bdo_credit_card_rewards\Service;

use Drupal\bdo_credit_card_rewards\Service\Contract\SearchResultServiceInterface;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Class SearchResultService
 * @package Drupal\bdo_credit_card_rewards\Service
 */
class SearchResultService implements SearchResultServiceInterface
{
    /**
     * @var ViewExecutable $view
     */
    private $view;

    /**
     * @var string $viewId
     */
    private $viewId = 'credit_card_rewards';

    /**
     * @var string $displayId
     */
    private $displayId = 'search_result';

    /**
     * SearchResultService constructor.
     */
    public function __construct()
    {
        $this->view = Views::getView($this->viewId);
        $this->view->setDisplay($this->displayId);
    }

    /**
     * @param array $args
     * @return $this
     */
    public function query(array $args)
    {
        if ($args) {
            foreach ($args as $key => $arg) {
                if ($arg) {
                    $filter = $this->view->getHandler($this->displayId, 'filter', 'cat');
                    if ($filter) {
                        $filter['value'] = $arg ? $arg : '';
                        $this->view->setHandler($this->displayId, 'filter', $key, $filter);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return array|null
     */
    public function buildView()
    {
        $this->view->preExecute();
        $this->view->execute();

        return $this->view->render();
    }
}
