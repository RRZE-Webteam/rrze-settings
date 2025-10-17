<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Options;
use RRZE\Settings\Helper;

/**
 * WebT class
 * 
 * @link https://github.com/RRZE-Webteam/rrze-webt
 * @package RRZE\Settings\Plugins
 */
class WebT
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'rrze-webt/rrze-webt.php';

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
        if (!$this->pluginExists(self::PLUGIN) || !$this->isPluginActive(self::PLUGIN)) {
            return;
        }

        if (
            !$this->hasException()
        ) {
            add_filter('rrze_webt_credentials', [$this, 'getCredentials']);
        }
    }

    /**
     * Get WebT credentials
     * 
     * @param  array  $credentials Credentials
     * @return array Updated credentials
     */
    public function getCredentials($credentials)
    {
        if (!empty($this->siteOptions->plugins->rrze_webt_api_url)) {
            $credentials['api_url'] = esc_url_raw($this->siteOptions->plugins->rrze_webt_api_url);
        }

        if (!empty($this->siteOptions->plugins->rrze_webt_application_name)) {
            $credentials['application_name'] = sanitize_text_field($this->siteOptions->plugins->rrze_webt_application_name);
        }

        if (!empty($this->siteOptions->plugins->rrze_webt_password)) {
            $credentials['password'] = sanitize_text_field($this->siteOptions->plugins->rrze_webt_password);
        }

        return $credentials;
    }

    /**
     * Check if network-wide the plugin has exceptions
     * 
     * @return bool
     */
    protected function hasException()
    {
        $exceptions = $this->siteOptions->plugins->rrze_webt_exceptions;
        if (!empty($exceptions) && is_array($exceptions)) {
            foreach ($exceptions as $row) {
                $aryRow = explode(' - ', $row);
                $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
                if (absint($blogId) == get_current_blog_id()) {
                    return true;
                }
            }
        }
        return false;
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
