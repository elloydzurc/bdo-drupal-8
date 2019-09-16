<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 2:18 PM
 */

namespace Drupal\bdo_stories\Service\Contract;


use Drupal\bdo_stories\Exception\DisplayNotSetException;
use Drupal\bdo_stories\Exception\ViewNotSetException;
use Drupal\views\Views;

abstract class AbstractViewBlock
{
    /**
     * @var $view
     */
    protected $view;

    /**
     * @var $viewId
     */
    protected $viewId;

    /**
     * @var $displayId
     */
    protected $displayId;

    /**
     * AbstractViewBlock constructor.
     * @param String $viewId
     * @param String $displayId
     */
    public function __construct(String $viewId, String $displayId)
    {
        $this->viewId = $viewId;
        $this->displayId = $displayId;
    }

    /**
     * @throws DisplayNotSetException
     * @throws ViewNotSetException
     */
    protected function build()
    {
        if (!$this->viewId) {
            throw new ViewNotSetException;
        }

        if (!$this->displayId) {
            throw new DisplayNotSetException;
        }

        $this->view = Views::getView($this->viewId);
        $this->view->setDisplay($this->displayId);

        return $this;
    }

    /**
     * Configure view. eg: attached library and etc.
     */
    abstract protected function configure();

    /**
     * @return mixed
     */
    public function render()
    {
        try {
            $this->build()->configure();
            $this->view->preExecute();
            $this->view->execute();

            return $this->view->render();
        } catch (DisplayNotSetException | ViewNotSetException $e) {
            \Drupal::logger($this->viewId)->error($e->getMessage());
        }
    }
}