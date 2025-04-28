<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

/**
 * Expand and collapse menu items in the WordPress admin menu.
 *
 * @package RRZE\Settings\Menus
 */
class ExpandCollapse
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        add_action('load-nav-menus.php', [$this, 'loadNavMenus']);
    }

    /**
     * Enqueue scripts for the admin area
     *
     * @return void
     */
    public function loadNavMenus()
    {
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    /**
     * Enqueue scripts for the menus page
     *
     * @return void
     */
    public function adminEnqueueScripts()
    {
        wp_enqueue_script('rrze-menus-expand-collapse');
    }
}
