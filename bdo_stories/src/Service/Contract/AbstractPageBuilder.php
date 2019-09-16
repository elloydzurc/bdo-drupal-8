<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/14/2019
 * Time: 10:08 PM
 */

namespace Drupal\bdo_stories\Service\Contract;


use Drupal\bdo_stories\Exception\NodeIdNotSetException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorage;

abstract class AbstractPageBuilder
{
    /**
     * @var NodeStorage $nodeStorage
     */
    protected $nodeStorage;

    /**
     * @var EntityViewBuilderInterface|mixed|object  $viewBuilder
     */
    protected $viewBuilder;

    /**
     * @var AliasManager $aliasManager
     */
    protected $aliasManager;

    /**
     * @var Node $node
     */
    protected $node;

    /**
     * @var int $nodeId
     */
    protected $nodeId;

    /**
     * @var String $nodeAlias
     */
    protected $nodeAlias;

    /**
     * @var String $channel
     */
    protected $channel;

    /**
     * @var $content
     */
    protected $content;

    /**
     * AbstractPageBuilder constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param AliasManager $aliasManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager, AliasManager $aliasManager)
    {
        try {
            $this->aliasManager = $aliasManager;
            $this->nodeStorage = $entityTypeManager
                ->getStorage('node');
            $this->viewBuilder = $entityTypeManager
                ->getViewBuilder('node');
        } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
            \Drupal::logger($this->channel)->error($e->getMessage());
        }
    }

    /**
     * Get Node Id using path alias
     */
    private function findId()
    {
        $path = $this->aliasManager->getPathByAlias($this->nodeAlias);

        if (preg_match('/node\/(\d+)/', $path, $matches)) {
            $this->nodeId = $matches[1];
        }

        return $this;
    }

    /**
     * Get page node
     * @throws NodeIdNotSetException
     */
    private function getNode()
    {
        if (!$this->nodeId) {
            throw new NodeIdNotSetException;
        }

        $this->node = $this->nodeStorage->load($this->nodeId);
        $this->configure();

        return $this;
    }

    /**
     * Allow to configure node elements
     */
    abstract protected function configure();

    /**
     * Allow to  attached library and etc to page
     * @return mixed
     */
    abstract protected function attached();

    /**
     * Return the page
     * @param String $nodeAlias
     * @return mixed
     * @throws NodeIdNotSetException
     */
    public function render(String $nodeAlias)
    {
        $this->nodeAlias = $nodeAlias;
        $this->findId()->getNode();

        $this->content = $this->viewBuilder->view($this->node, 'full');
        $this->attached();

        return $this->content;
    }
}