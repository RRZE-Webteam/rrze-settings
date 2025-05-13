<?php

/*
Plugin Name:        RRZE Settings
Plugin URI:         https://gitlab.rrze.fau.de/rrze-webteam/rrze-settings
Version:            2.0.1
Description:        General settings and enhancements for a WordPress multisite installation.
Author:             RRZE Webteam
Author URI:         https://blogs.fau.de/webworking/
License:            GNU General Public License Version 3
License URI:        https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:        rrze-settings
Domain Path:        /languages
Requires at least:  6.8
Requires PHP:       8.2
Network:            true
*/

namespace RRZE\Settings;

defined('ABSPATH') || exit;

use RRZE\Settings\Plugins\WSForm;
use WP_Error;

// Composer autoloading
require_once 'vendor/autoload.php';

// Set the WS Form plugin license key if available
WSForm::setWSFormLicenseKey();

// Load the plugin's text domain for localization
// Since WP 6.7.0, the text domain must be loaded using the 'init' action hook
add_action('init', fn() => load_plugin_textdomain('rrze-settings', false, dirname(plugin_basename(__FILE__)) . '/languages'));

// Register activation hook for the plugin
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');

// Register deactivation hook for the plugin
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

/**
 * Add an action hook for the 'plugins_loaded' hook
 *
 * This code hooks into the 'plugins_loaded' action hook to execute a callback function when
 * WordPress has fully loaded all active plugins and the theme's functions.php file.
 */
add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Handle the activation of the plugin
 *
 * This function is called when the plugin is activated.
 * 
 * @param bool $networkWide Indicates if the plugin is activated network-wide
 * @return void
 */
function activation($networkWide)
{
    //
}

/**
 * Handle the deactivation of the plugin
 *
 * This function is called when the plugin is deactivated.
 * 
 * @return void
 */
function deactivation()
{
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Singleton pattern for initializing and accessing the main plugin instance
 *
 * This method ensures that only one instance of the Plugin class is created and returned.
 *
 * @return Plugin The main instance of the Plugin class
 */
function plugin()
{
    // Declare a static variable to hold the instance
    static $instance;

    // Check if the instance is not already created
    if (null === $instance) {
        // Add a new instance of the Plugin class, passing the current file (__FILE__) as a parameter
        $instance = new Plugin(__FILE__);
    }

    // Return the main instance of the Plugin class
    return $instance;
}

/**
 * Check system requirements for the plugin
 *
 * This method checks if the server environment meets the minimum WordPress and PHP version requirements
 * for the plugin to function properly.
 *
 * @return object|string An object containing an error message if the requirements are not met
 */
function systemRequirements()
{
    // Get the global WordPress version
    global $wp_version;

    // Get the PHP version
    $phpVersion = phpversion();

    // Initialize an error message string
    $error = '';

    // Check if the WordPress version is compatible with the plugin's requirement
    if (!is_wp_version_compatible(plugin()->getRequiresWP())) {
        $error = sprintf(
            /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
            __('The server is running WordPress version %1$s. The plugin requires at least WordPress version %2$s.', 'rrze-settings'),
            $wp_version,
            plugin()->getRequiresWP()
        );
    } elseif (!is_php_version_compatible(plugin()->getRequiresPHP())) {
        // Check if the PHP version is compatible with the plugin's requirement
        $error = sprintf(
            /* translators: 1: Server PHP version number, 2: Required PHP version number. */
            __('The server is running PHP version %1$s. The plugin requires at least PHP version %2$s.', 'rrze-settings'),
            $phpVersion,
            plugin()->getRequiresPHP()
        );
    } elseif (!is_multisite() || !is_plugin_active_for_network(plugin()->getBaseName())) {
        // Set an error message indicating that the plugin can only be installed in a multisite installation and network-wide
        $error = __('The plugin can only be installed in a multisite installation and network-wide.', 'rrze-settings');
    }

    // Return an error object if there is an error, or an empty string if there are no errors
    return $error ? new WP_Error('rrze-settings', $error) : '';
}

/**
 * Handle the loading of the plugin
 *
 * This function is responsible for initializing the plugin, loading text domains for localization,
 * checking system requirements, and displaying error notices if necessary.
 * 
 * @return void
 */
function loaded()
{
    // Trigger the 'loaded' method of the main plugin instance
    plugin()->loaded();

    // Check system requirements.
    $checkRequirements = systemRequirements();
    if (is_wp_error($checkRequirements)) {
        // If there is an error, add an action to display an admin notice with the error message
        add_action('admin_init', function () use ($checkRequirements) {
            // Check if the current user has the capability to activate plugins
            if (current_user_can('activate_plugins')) {
                // Get plugin data to retrieve the plugin's name
                $pluginName = plugin()->getName();

                // Determine the admin notice tag based on network-wide activation
                $tag = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';

                // Add an action to display the admin notice
                add_action($tag, function () use ($pluginName, $checkRequirements) {
                    printf(
                        '<div class="notice notice-error"><p>' .
                            /* translators: 1: The plugin name, 2: The error string. */
                            esc_html__('Plugins: %1$s: %2$s', 'rrze-settings') .
                            '</p></div>',
                        $pluginName,
                        $checkRequirements->get_error_message()
                    );
                });
            }
        });

        // Return to prevent further initialization if there is an error
        return;
    }

    // If there are no errors, create an instance of the 'Main' class and trigger its 'loaded' method
    (new Main)->loaded();
}
