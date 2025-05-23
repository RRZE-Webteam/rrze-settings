<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

/**
 * Helper class
 * 
 * @package RRZE\Settings
 */
class Helper
{
    /**
     * Check if the plugin exists
     * 
     * @param  string  $plugin
     * @return boolean Returns true if the plugin exists otherwise false
     */
    public static function pluginExists(string $plugin): bool
    {
        return file_exists(WP_PLUGIN_DIR . '/' . untrailingslashit($plugin));
    }

    /**
     * Check if the plugin is active
     * 
     * @return boolean Returns true if the plugin is active or active for network otherwise false
     */
    public static function isPluginActive(string $plugin): bool
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        return is_plugin_active($plugin) || is_plugin_active_for_network($plugin);
    }

    /**
     * Is a valid URL domain
     * 
     * @param  string  $url The URL to check
     * @return boolean Returns true if the URL is valid otherwise false
     */
    public static function isValidDomain(string $url): bool
    {
        $parts = parse_url(filter_var($url, FILTER_SANITIZE_URL));
        if (!isset($parts['host'])) {
            $parts['host'] = $parts['path'];
        }
        if ($parts['host'] == '') {
            return false;
        }
        if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'])) {
            $parts['scheme'] = 'http';
        }

        $parts['host'] = preg_replace('/^www\./', '', $parts['host']);
        $url = $parts['scheme'] . '://' . $parts['host'] . "/";

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }
        return false;
    }

    /**
     * Check if the user can view the debug log
     * 
     * @return boolean Returns true if either is_super_admin() or the user is allowed to view the debug log
     */
    public static function userCanViewDebugLog(): bool
    {
        $allowed = false;

        if (is_super_admin()) {
            $allowed = true;
        } else {
            $siteOptions = Options::getSiteOptions();
            $currentUser = wp_get_current_user();
            if (!empty($siteOptions->users->can_view_debug_log)) {
                $allowed = in_array($currentUser->user_login, $siteOptions->users->can_view_debug_log);
            }
        }

        return $allowed;
    }

    /**
     * Check if the user can view the debug log
     * 
     * @deprecated 2.0.0 Use self::userCanViewDebugLog() instead
     * @return boolean Returns true if either is_super_admin() or the user is allowed to view the debug log
     */
    public static function isRRZEAdmin(): bool
    {
        _deprecated_function(__METHOD__, '2.0.0', 'self::userCanViewDebugLog');

        return self::userCanViewDebugLog();
    }

    /**
     * Get the BITE API key
     * 
     * @return string The API key
     */
    public static function getBiteApiKey(): string
    {
        $siteOptions = Options::getSiteOptions();
        return $siteOptions->plugins->bite_api_key ?? '';
    }

    /**
     * Get the DIP Edu API key
     * 
     * @return string The API key
     */
    public static function getDipEduApiKey(): string
    {
        $siteOptions = Options::getSiteOptions();
        return $siteOptions->plugins->dip_edu_api_key ?? '';
    }

    /**
     * Add an admin notice based on a transient
     * 
     * @param  string  $transient The transient name
     * @param  string  $message The message to display
     * @param  string  $type The type of notice (error, success, etc.)
     * @param  int     $duration The duration of the notice in seconds
     * @return void
     */
    public static function flashAdminNotice(string $transient, string $message, string $type = 'error', int $duration = 5): void
    {
        add_action('admin_notices', function () use ($transient, $message, $type, $duration) {
            if (get_transient($transient)) {
                echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible" data-duration="' . esc_attr($duration) . '">';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            }
        });
    }
}
