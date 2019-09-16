<?php

namespace Drupal\bdo_enews_edm\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class EnewsEdmThemeNegotiator implements ThemeNegotiatorInterface
{
    protected $theme = "bdod8_blank";

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match)
    {
        $routes = [
            'entity.node.edit_form',
            'entity.node.delete_form',
            'entity.node.canonical',
            'bdo_enews_edm.add',
            'bdo_enews_edm.dashboard',
            'bdo_enews_edm.default_settings',
            'bdo_enews_edm.delete',
            'bdo_enews_edm.settings'
        ];

        return (in_array($route_match->getRouteName(), $routes));
    }

    /**
     * {@inheritdoc}
     */
    public function determineActiveTheme(RouteMatchInterface $route_match)
    {
        return $this->theme;
    }
}
