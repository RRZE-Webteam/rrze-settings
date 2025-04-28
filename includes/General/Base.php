<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * Base class for general settings
 *
 * @package RRZE\Settings\General
 */
class Base
{
    /**
     * Set different Hooks that prevent non-superadmin users
     * from viewing notices and accessing menus in a WP Multisite environment
     *
     * @return void
     */
    public static function loaded()
    {
        add_action('admin_head', [__CLASS__, 'removeUpdateNotifications']);
        add_action('admin_menu', [__CLASS__, 'removeUpdateMenus'], 999);
        add_filter('map_meta_cap', [__CLASS__, 'updateMapMetaCap'], 10, 3);
        add_filter('site_transient_update_plugins', [__CLASS__, 'siteTransientUpdatePlugins']);
        add_filter('site_transient_update_themes', [__CLASS__, 'siteTransientUpdateThemes']);
    }

    /**
     * Removes update notifications for non-superadmin users
     *
     * @return void
     */
    public static function removeUpdateNotifications()
    {
        if (!current_user_can('update_core')) {
            remove_action('admin_notices', 'update_nag', 3);
        }
    }

    /**
     * Removes update menus for non-superadmin users
     *
     * @return void
     */
    public static function removeUpdateMenus()
    {
        if (!current_user_can('update_core')) {
            remove_submenu_page('index.php', 'update-core.php');
        }
    }

    /**
     * Prevents non-superadmin users from viewing or installing updates
     *
     * @param array $caps
     * @param string $cap
     * @param int $userId
     * @return array
     */
    public static function updateMapMetaCap($caps, $cap, $userId)
    {
        if (in_array($cap, ['update_core', 'update_plugins', 'update_themes'])) {
            $user = new \WP_User($userId);
            if (!$user->has_cap('manage_network')) {
                $caps[] = 'do_not_allow';
            }
        }
        return $caps;
    }

    /**
     * Removes plugin update notifications for non-superadmin users
     *
     * @param object $transient
     * @return object
     */
    public static function siteTransientUpdatePlugins($transient)
    {
        if (current_user_can('manage_network')) {
            return $transient;
        }

        if (is_object($transient) && property_exists($transient, 'response')) {
            $transient->response = [];
        }

        return $transient;
    }

    /**
     * Removes theme update notifications for non-superadmin users
     *
     * @param object $transient
     * @return object
     */
    public static function siteTransientUpdateThemes($transient)
    {
        if (current_user_can('manage_network')) {
            return $transient;
        }

        if (is_object($transient) && property_exists($transient, 'response')) {
            $transient->response = [];
        }

        return $transient;
    }
}
