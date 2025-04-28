<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;

/**
 * CMS Workflow class
 * 
 * @link https://github.com/RRZE-Webteam/cms-workflow
 * @package RRZE\Settings\Plugins
 */
class CMSWorkflow
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'cms-workflow/cms-workflow.php';

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

        // Not allowed post types filter.
        add_filter('cms_workflow_not_allowed_post_types', [$this, 'notAllowedPostTypes']);
    }

    /**
     * Not allowed post types
     * 
     * @param array $notAllowed Not allowed post types
     * @return array Not allowed post types
     */
    public function notAllowedPostTypes(array $notAllowed)
    {
        return array_merge($this->getNotAllowedPostTypes(), $notAllowed);
    }

    protected function getNotAllowedPostTypes()
    {
        return (array) $this->siteOptions->plugins->cms_workflow_not_allowed_post_types;
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
