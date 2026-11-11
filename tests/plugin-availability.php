<?php

/**
 * Exercise the plugin availability (allowlist) without WordPress.
 * Covers coexistence with RRZE Netzwerk-Audit and the shared option format.
 * Run: php tests/plugin-availability.php
 */

namespace {
    if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
        exit(1);
    }
    define('ABSPATH', '/');

    $GLOBALS['hooks'] = [];
    $GLOBALS['storage'] = [];
    $GLOBALS['superAdmin'] = false;
    $GLOBALS['nonceOk'] = true;
    $GLOBALS['plugins'] = [
        'akismet/akismet.php' => ['Name' => 'Akismet'],
        'enabled/enabled.php' => ['Name' => 'Enabled'],
        'active/active.php' => ['Name' => 'Active'],
        'network/network.php' => ['Name' => 'Network'],
        'hello.php' => ['Name' => 'Hello'],
    ];

    function is_multisite() {
        return true;
    }
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        $GLOBALS['hooks'][$hook][] = $callback;
        return true;
    }
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return add_filter($hook, $callback, $priority, $args);
    }
    function has_filter($hook) {
        return !empty($GLOBALS['hooks'][$hook]);
    }
    function apply_filters($hook, $value) {
        foreach ($GLOBALS['hooks'][$hook] ?? [] as $callback) {
            $value = call_user_func($callback, $value);
        }
        return $value;
    }
    function get_site_option($name, $default = false) {
        return array_key_exists($name, $GLOBALS['storage']) ? $GLOBALS['storage'][$name] : $default;
    }
    function update_site_option($name, $value) {
        $changed = get_site_option($name, null) !== $value;
        $GLOBALS['storage'][$name] = $value;
        return $changed;
    }
    function is_super_admin($userId = 0) {
        return $GLOBALS['superAdmin'];
    }
    function __($text, $domain = '') {
        return $text;
    }
    function esc_html__($text, $domain = '') {
        return $text;
    }
    function esc_html($text) {
        return $text;
    }
    function esc_url($url) {
        return $url;
    }
    function add_query_arg($args, $url) {
        return $url . '?' . http_build_query($args);
    }
    function admin_url($path = '') {
        return 'https://example.org/wp-admin/' . $path;
    }
    function wp_nonce_url($url, $action) {
        return $url . '&_wpnonce=' . md5($action);
    }
    function sanitize_text_field($text) {
        return trim((string) $text);
    }
    function wp_unslash($value) {
        return $value;
    }
    function check_admin_referer($action) {
        if (!$GLOBALS['nonceOk']) {
            throw new RuntimeException('nonce');
        }
        $GLOBALS['lastNonceAction'] = $action;
        return 1;
    }
    function wp_die($message = '', $code = 0) {
        throw new RuntimeException('die:' . $code);
    }
    function get_plugins() {
        return $GLOBALS['plugins'];
    }
    function is_network_only_plugin($file) {
        return $file === 'network/network.php';
    }
    function is_plugin_active_for_network($file) {
        return false;
    }
    function is_plugin_active($file) {
        return $file === 'active/active.php';
    }

    require dirname(__DIR__) . '/includes/Governance/PluginAvailability.php';
}

namespace Test {
    use RRZE\Settings\Governance\PluginAvailability;
    use RuntimeException;

    $checks = 0;
    $failures = 0;

    function check($label, $condition) {
        global $checks, $failures;
        $checks++;
        if (!$condition) {
            $failures++;
            fwrite(STDERR, "FAIL: $label\n");
        }
    }

    function throws($callback) {
        try {
            $callback();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
        return '';
    }

    function bootFresh() {
        $GLOBALS['hooks'] = array_intersect_key($GLOBALS['hooks'], ['rrze_settings_plugin_availability_legacy_active' => 1]);
        $module = new PluginAvailability();
        $module->loaded();
        $module->boot();
        return $module;
    }

    function legacyReads() {
        // Same logic as RRZE\NetzwerkAudit\Options::getOptions()
        $defaults = ['enabled_themes' => [], 'enabled_plugins' => []];
        $options = (array) get_site_option('rrze_netzwerk_audit');
        $options = array_merge($defaults, $options);
        return (object) array_intersect_key($options, $defaults);
    }

    function legacyOff($active) {
        return false;
    }

    // 1. Option missing: nothing is enforced.
    $module = bootFresh();
    check('not configured: no enforcement', !has_filter('map_meta_cap'));
    check('not configured: no column', !has_filter('manage_plugins-network_columns'));
    check('not configured: toggle handler registered', has_filter('admin_post_rrze_settings_plugin_availability'));

    // 2. Option written by an old version as object.
    $GLOBALS['storage']['rrze_netzwerk_audit'] = (object) [
        'enabled_themes' => ['fau-theme'],
        'enabled_plugins' => ['enabled/enabled.php'],
    ];
    add_filter('rrze_settings_plugin_availability_legacy_active', 'Test\\legacyOff');
    $module = bootFresh();
    check('configured: enforcement', has_filter('map_meta_cap'));
    check('configured: column', has_filter('manage_plugins-network_columns'));
    check('configured: hide plugins', has_filter('all_plugins'));
    check('configured: links', has_filter('network_admin_plugin_action_links'));
    check('read object option', PluginAvailability::getEnabledPlugins() === ['enabled/enabled.php']);

    // 3. Capability check.
    $GLOBALS['superAdmin'] = false;
    check('deny not enabled', in_array('do_not_allow', $module->restrictActivation(['activate_plugins'], 'activate_plugin', 5, ['akismet/akismet.php']), true));
    check('allow enabled', $module->restrictActivation(['activate_plugins'], 'activate_plugin', 5, ['enabled/enabled.php']) === ['activate_plugins']);
    check('other caps untouched', $module->restrictActivation(['x'], 'edit_posts', 5, []) === ['x']);
    $GLOBALS['superAdmin'] = true;
    check('super admin allowed', $module->restrictActivation(['activate_plugins'], 'activate_plugin', 1, ['akismet/akismet.php']) === ['activate_plugins']);

    // 4. Plugin list for site administrators.
    $GLOBALS['superAdmin'] = false;
    $visible = array_keys($module->filterPlugins($GLOBALS['plugins']));
    check('site admin sees enabled, active and network-only', $visible === ['enabled/enabled.php', 'active/active.php', 'network/network.php']);
    $GLOBALS['superAdmin'] = true;
    check('super admin sees all', count($module->filterPlugins($GLOBALS['plugins'])) === 5);

    // 5. Action links: position and keys.
    $links = $module->actionLinks(['activate' => 'A', 'delete' => 'D'], 'akismet/akismet.php', [], 'all');
    check('enable link after first link', array_keys($links) === ['activate', 'enable', 'delete']);
    check('enable link has nonce', strpos($links['enable'], '_wpnonce=') !== false);
    check('no link for single-file plugin', $module->actionLinks(['activate' => 'A'], 'hello.php', [], 'all') === ['activate' => 'A']);
    check('no link for network-only plugin', $module->actionLinks(['activate' => 'A'], 'network/network.php', [], 'all') === ['activate' => 'A']);

    // 6. Enable/Disable request.
    $_GET = ['plugin' => 'akismet/akismet.php', 'state' => 'enable'];
    $GLOBALS['superAdmin'] = false;
    check('non super admin rejected', throws([$module, 'processToggle']) === 'die:403');
    $GLOBALS['superAdmin'] = true;
    $GLOBALS['nonceOk'] = false;
    check('invalid nonce rejected', throws([$module, 'processToggle']) === 'nonce');
    $GLOBALS['nonceOk'] = true;
    check('enable', $module->processToggle() === true);
    check('nonce bound to plugin and state', $GLOBALS['lastNonceAction'] === 'rrze_settings_plugin_availability_enable_akismet/akismet.php');
    check('themes preserved', $GLOBALS['storage']['rrze_netzwerk_audit']['enabled_themes'] === ['fau-theme']);
    check('stored as array', is_array($GLOBALS['storage']['rrze_netzwerk_audit']));
    check('old version reads new data', legacyReads()->enabled_plugins === ['enabled/enabled.php', 'akismet/akismet.php']);
    $_GET = ['plugin' => 'enabled/enabled.php', 'state' => 'disable'];
    check('disable', $module->processToggle() === true);
    check('disable result', PluginAvailability::getEnabledPlugins() === ['akismet/akismet.php']);
    $_GET = ['plugin' => 'missing/missing.php', 'state' => 'enable'];
    check('missing plugin rejected', throws([$module, 'processToggle']) === 'die:404');
    $_GET = ['plugin' => 'hello.php', 'state' => 'enable'];
    check('single-file plugin rejected', throws([$module, 'processToggle']) === 'die:404');

    // 7. RRZE Netzwerk-Audit active: no duplicate UI, enforcement stays.
    unset($GLOBALS['hooks']['rrze_settings_plugin_availability_legacy_active']);
    eval('namespace RRZE\NetzwerkAudit; class Main {} class Plugins {}');
    $module = bootFresh();
    check('legacy detected', PluginAvailability::isLegacyPluginActive());
    check('legacy: enforcement', has_filter('map_meta_cap'));
    check('legacy: notice', has_filter('network_admin_notices'));
    check('legacy: no column', !has_filter('manage_plugins-network_columns'));
    check('legacy: no links', !has_filter('network_admin_plugin_action_links'));
    check('legacy: no plugin filter', !has_filter('all_plugins'));

    printf("%d checks, %d failures\n", $checks, $failures);
    exit($failures === 0 ? 0 : 1);
}
