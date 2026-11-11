<?php

namespace RRZE\Settings\Governance;

defined('ABSPATH') || exit;

/**
 * Plugin availability (allowlist) for websites of the network.
 *
 * Takes over the plugin allowlist of the former plugin RRZE Netzwerk-Audit
 * and uses the same network option ('rrze_netzwerk_audit', key
 * 'enabled_plugins'), so the stored data stays valid in both directions.
 *
 * - Site administrators can only see and activate plugins that are enabled.
 * - Super admins manage the list in the network plugin list (column and
 *   Enable/Disable links).
 *
 * Coexistence: while RRZE Netzwerk-Audit is active, it keeps providing the
 * management UI and RRZE Settings only adds the capability check (which also
 * covers the REST API). Once RRZE Netzwerk-Audit is deactivated, RRZE Settings
 * provides the complete functionality without changing any option.
 *
 * The feature is only active if the network option exists, i.e. on networks
 * that already use the allowlist.
 *
 * @package RRZE\Settings\Governance
 */
class PluginAvailability {
    /**
     * Network option shared with RRZE Netzwerk-Audit and RRZE Multisite Manager.
     */
    const OPTION_NAME = 'rrze_netzwerk_audit';

    /**
     * Key of the allowlist within the network option.
     */
    const OPTION_KEY = 'enabled_plugins';

    /**
     * admin-post action for enabling/disabling a plugin.
     */
    const ACTION = 'rrze_settings_plugin_availability';

    /**
     * Column key in the network plugin list (same key as before).
     */
    const COLUMN = 'enabled_plugins';

    /**
     * Register hooks.
     *
     * @return void
     */
    public function loaded(): void {
        if (!is_multisite()) {
            return;
        }

        add_action('admin_post_' . self::ACTION, [$this, 'handleToggle']);

        // Decide after all plugins have been loaded whether
        // RRZE Netzwerk-Audit is still active.
        add_action('init', [$this, 'boot'], 1);
    }

    /**
     * Register the enforcement and, if RRZE Netzwerk-Audit is not active,
     * the management UI.
     *
     * @return void
     */
    public function boot(): void {
        if (!self::isConfigured()) {
            return;
        }

        add_filter('map_meta_cap', [$this, 'restrictActivation'], 10, 4);

        if (self::isLegacyPluginActive()) {
            add_action('network_admin_notices', [$this, 'legacyNotice']);
            return;
        }

        add_filter('manage_plugins-network_columns', [$this, 'addColumn']);
        add_action('manage_plugins_custom_column', [$this, 'renderColumn'], 10, 2);
        add_filter('all_plugins', [$this, 'filterPlugins']);
        add_filter('plugin_action_links', [$this, 'actionLinks'], 10, 4);
        add_filter('network_admin_plugin_action_links', [$this, 'actionLinks'], 10, 4);
    }

    /**
     * Whether the allowlist is used on this network (the option exists).
     *
     * @return bool
     */
    public static function isConfigured(): bool {
        return get_site_option(self::OPTION_NAME, false) !== false;
    }

    /**
     * Whether RRZE Netzwerk-Audit (version 2019 or later) is active and
     * provides the management of the allowlist itself.
     *
     * @return bool
     */
    public static function isLegacyPluginActive(): bool {
        $active = class_exists('RRZE\\NetzwerkAudit\\Main', false)
            && class_exists('RRZE\\NetzwerkAudit\\Plugins');

        /**
         * Filters whether RRZE Netzwerk-Audit provides the plugin allowlist.
         *
         * @param bool $active
         */
        return (bool) apply_filters('rrze_settings_plugin_availability_legacy_active', $active);
    }

    /**
     * Return the enabled plugins.
     *
     * @return string[]
     */
    public static function getEnabledPlugins(): array {
        $options = self::getRawOptions();
        $plugins = $options[self::OPTION_KEY] ?? [];

        if (!is_array($plugins)) {
            return [];
        }

        return array_values(array_filter($plugins, 'is_string'));
    }

    /**
     * Whether a plugin is enabled.
     *
     * @param string $pluginFile Plugin file, e.g. my-plugin/my-plugin.php
     * @return bool
     */
    public static function isEnabled(string $pluginFile): bool {
        return in_array($pluginFile, self::getEnabledPlugins(), true);
    }

    /**
     * Enable or disable a plugin. All other keys of the network option
     * (e.g. 'enabled_themes') are preserved.
     *
     * @param string $pluginFile Plugin file
     * @param bool $enabled New state
     * @return bool Whether the option was changed
     */
    public static function setEnabled(string $pluginFile, bool $enabled): bool {
        $options = self::getRawOptions();
        $plugins = self::getEnabledPlugins();
        $isEnabled = in_array($pluginFile, $plugins, true);

        if ($enabled === $isEnabled) {
            return false;
        }

        if ($enabled) {
            $plugins[] = $pluginFile;
        } else {
            $plugins = array_diff($plugins, [$pluginFile]);
        }

        $options[self::OPTION_KEY] = array_values(array_unique($plugins));

        return (bool) update_site_option(self::OPTION_NAME, $options);
    }

    /**
     * Deny the activation of plugins that are not enabled to everybody
     * except super admins. Applies to the admin, the REST API and any
     * other code that checks the 'activate_plugin' capability.
     *
     * @param string[] $caps Primitive capabilities
     * @param string $cap Requested capability
     * @param int $userId User ID
     * @param array $args Arguments, $args[0] is the plugin file
     * @return string[]
     */
    public function restrictActivation($caps, $cap, $userId, $args) {
        if ($cap !== 'activate_plugin' || is_super_admin($userId)) {
            return $caps;
        }

        $pluginFile = isset($args[0]) && is_string($args[0]) ? $args[0] : '';

        if ($pluginFile !== '' && !self::isEnabled($pluginFile)) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

    /**
     * Hide plugins that are not enabled from site administrators.
     * Plugins that are already active stay visible.
     *
     * @param array $plugins All plugins
     * @return array
     */
    public function filterPlugins($plugins) {
        if (is_super_admin() || !is_array($plugins)) {
            return $plugins;
        }

        self::loadPluginFunctions();
        $enabled = self::getEnabledPlugins();

        foreach (array_keys($plugins) as $pluginFile) {
            if (
                is_network_only_plugin($pluginFile)
                || is_plugin_active_for_network($pluginFile)
                || is_plugin_active($pluginFile)
            ) {
                continue;
            }
            if (!in_array($pluginFile, $enabled, true)) {
                unset($plugins[$pluginFile]);
            }
        }

        return $plugins;
    }

    /**
     * Add the column to the network plugin list.
     *
     * @param array $columns
     * @return array
     */
    public function addColumn($columns) {
        $columns[self::COLUMN] = __('Enabled', 'rrze-settings');
        return $columns;
    }

    /**
     * Render the column in the network plugin list.
     *
     * @param string $columnName
     * @param string $pluginFile
     * @return void
     */
    public function renderColumn($columnName, $pluginFile) {
        if ($columnName !== self::COLUMN || !self::isManageable((string) $pluginFile)) {
            return;
        }

        if (self::isEnabled($pluginFile)) {
            printf(
                '<span class="dashicons dashicons-yes" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
                esc_html__('Enabled', 'rrze-settings')
            );
        } else {
            printf(
                '<span class="dashicons dashicons-no" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
                esc_html__('Not enabled', 'rrze-settings')
            );
        }
    }

    /**
     * Add the Enable/Disable link to the plugin action links.
     *
     * @param array $actions
     * @param string $pluginFile
     * @param array $pluginData
     * @param string $context
     * @return array
     */
    public function actionLinks($actions, $pluginFile, $pluginData, $context) {
        if (!is_array($actions) || !self::isManageable((string) $pluginFile)) {
            return $actions;
        }

        $enabled = self::isEnabled($pluginFile);
        $state = $enabled ? 'disable' : 'enable';
        $label = $enabled ? __('Disable', 'rrze-settings') : __('Enable', 'rrze-settings');

        $link = sprintf(
            '<a href="%1$s" class="edit">%2$s</a>',
            esc_url(self::getToggleUrl($pluginFile, $state)),
            esc_html($label)
        );

        // Insert after the first link and keep the array keys.
        $first = array_slice($actions, 0, 1, true);
        $rest = array_slice($actions, 1, null, true);

        return $first + [$state => $link] + $rest;
    }

    /**
     * Handle the Enable/Disable request and redirect back.
     *
     * @return void
     */
    public function handleToggle(): void {
        $this->processToggle();

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = network_admin_url('plugins.php');
        }

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Validate the Enable/Disable request and update the allowlist.
     *
     * @return bool Whether the option was changed
     */
    public function processToggle(): bool {
        if (!is_super_admin()) {
            wp_die(esc_html__('Sorry, you are not allowed to change the plugin availability.', 'rrze-settings'), 403);
        }

        $pluginFile = isset($_GET['plugin']) ? sanitize_text_field(wp_unslash($_GET['plugin'])) : '';
        $state = isset($_GET['state']) && $_GET['state'] === 'enable' ? 'enable' : 'disable';

        check_admin_referer(self::nonceAction($pluginFile, $state));

        if (!self::isConfigured()) {
            wp_die(esc_html__('The plugin availability is not used on this network.', 'rrze-settings'), 400);
        }

        if (!self::isManageable($pluginFile) || !self::pluginFileExists($pluginFile)) {
            wp_die(esc_html__('The plugin file does not exist.', 'rrze-settings'), 404);
        }

        return self::setEnabled($pluginFile, $state === 'enable');
    }

    /**
     * Inform super admins that RRZE Netzwerk-Audit can be deactivated.
     *
     * @return void
     */
    public function legacyNotice(): void {
        if (!is_super_admin() || !function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins-network') {
            return;
        }

        printf(
            '<div class="notice notice-info"><p>%s</p></div>',
            esc_html__('The plugin availability is still provided by RRZE Netzwerk-Audit. You can deactivate RRZE Netzwerk-Audit: RRZE Settings will then provide the plugin availability with the existing settings.', 'rrze-settings')
        );
    }

    /**
     * Whether the availability of a plugin can be managed. Network-only
     * plugins, network-activated plugins and single-file plugins are excluded
     * (same rules as before).
     *
     * @param string $pluginFile
     * @return bool
     */
    public static function isManageable(string $pluginFile): bool {
        if (!is_super_admin() || $pluginFile === '' || strpos($pluginFile, '/') === false) {
            return false;
        }

        self::loadPluginFunctions();

        return !is_network_only_plugin($pluginFile) && !is_plugin_active_for_network($pluginFile);
    }

    /**
     * Build the URL for enabling/disabling a plugin.
     *
     * @param string $pluginFile
     * @param string $state 'enable' or 'disable'
     * @return string
     */
    public static function getToggleUrl(string $pluginFile, string $state): string {
        $url = add_query_arg(
            [
                'action' => self::ACTION,
                'plugin' => rawurlencode($pluginFile),
                'state' => $state,
            ],
            admin_url('admin-post.php')
        );

        return wp_nonce_url($url, self::nonceAction($pluginFile, $state));
    }

    /**
     * Nonce action for a plugin and state.
     *
     * @param string $pluginFile
     * @param string $state
     * @return string
     */
    protected static function nonceAction(string $pluginFile, string $state): string {
        return self::ACTION . '_' . $state . '_' . $pluginFile;
    }

    /**
     * Read the network option as array. Earlier versions of
     * RRZE Netzwerk-Audit stored it as object.
     *
     * @return array
     */
    protected static function getRawOptions(): array {
        $options = get_site_option(self::OPTION_NAME, []);

        if (is_object($options)) {
            return get_object_vars($options);
        }

        return is_array($options) ? $options : [];
    }

    /**
     * Whether a plugin file exists.
     *
     * @param string $pluginFile
     * @return bool
     */
    protected static function pluginFileExists(string $pluginFile): bool {
        self::loadPluginFunctions();
        $plugins = get_plugins();

        return isset($plugins[$pluginFile]);
    }

    /**
     * Make sure the plugin API functions are available.
     *
     * @return void
     */
    protected static function loadPluginFunctions(): void {
        if (!function_exists('get_plugins') || !function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }
}
