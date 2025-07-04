<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;

/**
 * The SEO Framework class
 * 
 * @link https://wordpress.org/plugins/autodescription/
 * @package RRZE\Settings\Plugins
 */
class TheSEOFramework
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'autodescription/autodescription.php';

    /**
     * Site options
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     * 
     * @param object $siteOptions Site options
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        if (!$this->pluginExists(self::PLUGIN)) {
            return;
        }

        if ($this->siteOptions->plugins->the_seo_framework_activate) {
            $this->maybeActivatePlugin();
            if (!is_super_admin()) {
                add_filter(
                    'network_admin_plugin_action_links',
                    [$this, 'networkAdminPluginActionLinks'],
                    99,
                    2
                );
                add_filter('all_plugins', [$this, 'allPlugins'], 99);
            }
        }
    }

    /**
     * Maybe activate plugin
     * 
     * @return void
     */
    protected function maybeActivatePlugin()
    {
        if (
            $this->isPluginActive('rrze-private-site/rrze-private-site.php')
            && $this->isPluginActive(self::PLUGIN)
            && defined('\RRZE\PrivateSite\PRIVATE_SITE_OPTION')
            && get_option(\RRZE\PrivateSite\PRIVATE_SITE_OPTION)
        ) {
            deactivate_plugins(self::PLUGIN);
        } elseif (!$this->isPluginActive(self::PLUGIN)) {
            activate_plugin(self::PLUGIN);
        }
    }

    /**
     * Remove the 'Deactivate' action link for the plugin
     * 
     * @param  array  $actions    Plugin action links
     * @param  string $pluginFile Plugin file
     * @return array              Plugin action links
     */
    public function networkAdminPluginActionLinks($actions, $pluginFile)
    {
        // Check if the current plugin is the one we want to disable deactivation for
        if ($pluginFile == self::PLUGIN) {
            // Remove the 'Deactivate' action link
            unset($actions['deactivate']);
        }

        return $actions;
    }

    public function allPlugins($plugins)
    {
        // Check if the plugin is in the array of plugins
        if (isset($plugins[self::PLUGIN])) {
            // Remove the plugin from the array
            unset($plugins[self::PLUGIN]);
        }

        return $plugins;
    }

    /**
     * Check if plugin is available
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is available
     */
    protected function pluginExists($plugin)
    {
        return Helper::pluginExists($plugin);
    }

    /**
     * Check if plugin is active
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is active
     */
    protected function isPluginActive($plugin)
    {
        return Helper::isPluginActive($plugin);
    }
}
