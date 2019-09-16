<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/12/2019
 * Time: 2:41 PM
 */

namespace Drupal\bdo_stories\Service;

use Drupal\bdo_stories\Service\Contract\AbstractConfigData;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Url;

class UrlMappingService extends AbstractConfigData
{
    /**
     * @var AliasStorage $aliasStorage
     */
    protected $aliasStorage;

    /**
     * UrlMappingService constructor.
     * @param ConfigFactory $configFactory
     * @param AliasStorage $aliasStorage
     */
    public function __construct(ConfigFactory $configFactory, AliasStorage $aliasStorage)
    {
        $this->aliasStorage = $aliasStorage;
        parent::__construct($configFactory, 'bdo_stories.settings');
    }

    /**
     * Format URL mapping entry for table display
     *
     * @return array
     */
    protected function format()
    {
        $results = [];

        foreach ($this->config as $key => $item) {
            $results[$key] = [
                ['data' => $item['name']],
                ['data' => $item['url']],
                ['data' => [
                        $this->generateLink('edit', $key),
                        $this->generateLink('delete', $key)
                    ]
                ]
            ];
        }

        return $results;
    }

    /**
     * Create default entry for URL Mapping
     *
     * @throws \Drupal\bdo_stories\Exception\SettingsNameNotSetException
     */
    protected function default()
    {
        $default['default'] = [
            'name' => 'Default',
            'url' => 'foundation/stories/view'
        ];

        $default['3274'] = [
            'name' => 'BDO Foundation',
            'url' => 'foundation/stories2'
        ];

        $this->init('createOrUpdate', $default);
    }

    /**
     * Generate link for URL mapping operations
     *
     * @param String $action
     * @param $mapId
     * @return array|mixed[]
     */
    private function generateLink(String $action, $mapId)
    {
        $link = null;

        if ($action !== 'delete' || $mapId > 0) {
            $url = Url::fromRoute(
                'bdo_stories.url_mapping',
                [
                    'mapId' => $mapId,
                    'action' => $action
                ]
            );

            $link = Link::fromTextAndUrl($action . ' ', $url)->toRenderable();
        }

        return $link;
    }

    /**
     * Event callback after saving or updating the config
     * This will update the node URL alias
     */
    protected function callback()
    {
        try {
            // This array is representing Story Type ID as array key
            // and its corresponding page Node ID as array value
            $pageNode = [
                3273 => 39040, // Homepage
                3274 => 45999 // BDO Foundation
            ];

            foreach ($this->config as $key => $config) {
                if (in_array($key, $pageNode)) {
                    $this->aliasStorage->save(
                        "/node/" . $pageNode[$key],
                        '/' . $config['url'],
                        "en"
                    );
                }
            }
        } catch (\Exception $e) {
            \Drupal::logger('bdo_stories')->error($e->getMessage());
        }
    }
}
