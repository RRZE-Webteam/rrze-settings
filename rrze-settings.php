<?php

/*
Plugin Name:        RRZE Settings
Plugin URI:         https://gitlab.rrze.fau.de/rrze-webteam/rrze-settings
Version:            2.0.5
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

// Composer autoloading
require_once 'vendor/autoload.php';

// Set the WS Form plugin license key if available
WSForm::setWSFormLicenseKey();

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
 * Callback function to load the plugin textdomain.
 * 
 * @return void
 */
function loadTextdomain()
{
    load_plugin_textdomain(
        'rrze-settings',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
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
    // Trigger the 'loaded' method of the main plugin instance.
    plugin()->loaded();

    // Load the plugin textdomain for translations.
    add_action(
        'init',
        __NAMESPACE__ . '\loadTextdomain'
    );

    // Check system requirements.
    if (
        ! $wpCompatibe = is_wp_version_compatible(plugin()->getRequiresWP())
            || ! $phpCompatible = is_php_version_compatible(plugin()->getRequiresPHP())
                || ! $isMultisite = is_multisite()
                    || ! $isPluginActiveForNetwork = is_plugin_active_for_network(plugin()->getBaseName())
    ) {
        // If the system requirements are not met, add an action to display an admin notice.
        add_action('init', function () use ($wpCompatibe, $phpCompatible, $isMultisite, $isPluginActiveForNetwork) {
            // Check if the current user has the capability to activate plugins.
            if (current_user_can('activate_plugins')) {
                // Determine the appropriate admin notice tag based on whether the plugin is network activated.
                $hookName = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';

                // Get the plugin name for display in the admin notice.
                $pluginName = plugin()->getName();

                $error = '';
                if (! $wpCompatibe) {
                    $error = sprintf(
                        /* translators: 1: Server WordPress version number, 2: Required WordPress version number. */
                        __('The server is running WordPress version %1$s. The plugin requires at least WordPress version %2$s.', 'rrze-settings'),
                        wp_get_wp_version(),
                        plugin()->getRequiresWP()
                    );
                } elseif (! $phpCompatible) {
                    $error = sprintf(
                        /* translators: 1: Server PHP version number, 2: Required PHP version number. */
                        __('The server is running PHP version %1$s. The plugin requires at least PHP version %2$s.', 'rrze-settings'),
                        PHP_VERSION,
                        plugin()->getRequiresPHP()
                    );
                } elseif (! $isMultisite || ! $isPluginActiveForNetwork) {
                    $error = __('The plugin can only be installed in a multisite installation and network-wide.', 'rrze-settings');
                }

                // Display the error notice in the admin area.
                // This will show a notice with the plugin name and the error message.
                add_action($hookName, function () use ($pluginName, $error) {
                    printf(
                        '<div class="notice notice-error"><p>' .
                            /* translators: 1: The plugin name, 2: The error string. */
                            esc_html__('Plugins: %1$s: %2$s', 'rrze-settings') .
                            '</p></div>',
                        $pluginName,
                        $error
                    );
                });
            }
        });

        // If the system requirements are not met, the plugin initialization will not proceed.
        return;
    }

    // If there are no errors, create an instance of the 'Main' class and trigger its 'loaded' method
    (new Main)->loaded();
}
