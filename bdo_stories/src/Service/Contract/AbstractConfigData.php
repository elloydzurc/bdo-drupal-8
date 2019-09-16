<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/12/2019
 * Time: 4:04 PM
 */

namespace Drupal\bdo_stories\Service\Contract;

use Drupal\bdo_stories\Exception\SettingsNameNotSetException;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class AbstractConfigData
 * @package Drupal\bdo_stories\Service\Contract
 */
abstract class AbstractConfigData
{
    /**
     * @var ConfigFactory $configFactory
     */
    protected $configFactory;

    /**
     * @var Config $editable
     */
    protected $editable;

    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var String $settingsName
     */
    protected $settingsName;

    /**
     * AbstractConfigData constructor.
     * @param ConfigFactory $configFactory
     * @param String $settingsName
     */
    public function __construct(ConfigFactory $configFactory, String $settingsName)
    {
        $this->configFactory = $configFactory;
        $this->settingsName = $settingsName;
    }

    /**
     * Format the config list as you wish
     * @return mixed
     */
    abstract protected function format();

    /**
     * Insert default value on module config, if not exist
     * @return mixed
     */
    abstract protected function default();

    /**
     * Event callback after saving or updating the config
     * @return mixed
     */
    abstract protected function callback();

    /**
     * Get the module configuration
     * @return AbstractConfigData
     * @throws SettingsNameNotSetException
     */
    protected function get()
    {
        if ($this->settingsName) {
            $this->config = $this->configFactory
                ->get($this->settingsName)
                ->getRawData();

            // Add default value
            if (!$this->config) {
                $this->default();
            }

            return $this;
        }

        throw new SettingsNameNotSetException();
    }

    /**
     * Create a config, update instead if exists
     * @return AbstractConfigData
     * @throws SettingsNameNotSetException
     */
    protected function createOrUpdate()
    {
        if ($this->settingsName) {
            $this->editable = $this->configFactory->getEditable($this->settingsName);

            foreach ($this->config as $key => $config) {
                $this->editable->set($key, $config);
            }

            $this->editable->save();
            return $this;
        }

        throw new SettingsNameNotSetException();
    }

    /**
     * Delete sub-config
     * @return bool
     * @throws SettingsNameNotSetException
     */
    protected function delete()
    {
        if ($this->settingsName) {
            foreach ($this->config as $key => $config) {
                $this->configFactory
                    ->getEditable($this->settingsName)
                    ->clear($key)
                    ->save();
            }
            return true;
        }

        throw new SettingsNameNotSetException();
    }

    /**
     * Initialize an event on config factory
     * @param String $action
     * @param array $config
     * @return mixed
     * @throws SettingsNameNotSetException
     */
    public function init(String $action, array $config = [])
    {
        $this->config = $config;
        $output = null;

        switch ($action) {
            case 'get':
                $output = $this->get()->format();
                break;
            case 'createOrUpdate':
                $output = $this->createOrUpdate()->callback();
                break;
            case 'delete':
                $output = $this->delete();
                break;
        }

        return $output;
    }
}
