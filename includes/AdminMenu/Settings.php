<?php

namespace RRZE\Settings\AdminMenu;

defined('ABSPATH') || exit;

use function RRZE\Settings\plugin;

/**
 * Network administrator controls for the shared Settings submenu order.
 */
class Settings
{
    public const PAGE = 'rrze-settings-admin-navigation';

    /** @var string Screen hook returned by WordPress for scoped asset loading. */
    private string $hook = '';

    /**
     * Register the network screen, POST handler and its progressive enhancement.
     *
     * @return void
     */
    public function loaded(): void
    {
        // The RRZE Settings parent must exist before WordPress creates this hook.
        add_action('network_admin_menu', [$this, 'register'], 20);
        add_action('network_admin_edit_' . self::PAGE, [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Restrict the central navigation configuration to network administrators.
     *
     * @return void
     */
    public function register(): void
    {
        $this->hook = (string) add_submenu_page(
            'rrze-settings',
            __('Admin Navigation', 'rrze-settings'),
            __('Admin Navigation', 'rrze-settings'),
            'manage_network_options',
            self::PAGE,
            [$this, 'render']
        );
    }

    /**
     * Load sortable controls only on this screen; numeric fields work without JS.
     *
     * @param string $hook Current WordPress screen hook.
     * @return void
     */
    public function enqueue(string $hook): void
    {
        if ($hook !== $this->hook || $this->hook === '') {
            return;
        }
        $asset = require plugin()->getPath('build') . 'admin-navigation/settings.asset.php';
        wp_enqueue_script(
            self::PAGE,
            plugins_url('build/admin-navigation/settings.js', plugin()->getBasename()),
            array_merge($asset['dependencies'], ['jquery-ui-sortable']),
            $asset['version'],
            true
        );
        wp_localize_script(self::PAGE, 'rrzeAdminNavigation', [
            /* translators: 1: Menu label, 2: New position, 3: Number of entries. */
            'moved' => __('%1$s: position %2$s of %3$s. Save changes to apply the order.', 'rrze-settings'),
            'changed' => __('Unsaved changes', 'rrze-settings'),
        ]);
    }

    /**
     * Render catalogue ordering and an explicit opt-in for newly observed items.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can('manage_network_options')) {
            wp_die(esc_html__('You are not allowed to manage network navigation.', 'rrze-settings'));
        }
        $preferences = Navigation::preferences();
        $defaults = Catalog::defaults();
        $catalog = Catalog::entries();
        $ids = Order::ids($defaults, $catalog, $preferences['order']);
        $pending = array_diff_key($catalog, array_fill_keys($ids, true));
        $activeSlugs = [];
        foreach ($ids as $id) {
            $activeSlugs = array_merge($activeSlugs, $catalog[$id]['slugs']);
        }
        foreach ($pending as $id => $entry) {
            if (array_intersect($entry['slugs'], $activeSlugs)) {
                unset($pending[$id]);
            }
        }
        require __DIR__ . '/view.php';
    }

    /**
     * Save only known identifiers after capability, request method and nonce checks.
     *
     * @return void
     */
    public function save(): void
    {
        if (!is_network_admin() || !current_user_can('manage_network_options') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die(esc_html__('You are not allowed to manage network navigation.', 'rrze-settings'));
        }
        check_admin_referer(self::PAGE);
        $input = wp_unslash($_POST);
        $catalog = Catalog::entries();
        $defaults = Catalog::defaults();
        $preferences = Navigation::preferences();
        $ids = Order::ids($defaults, $catalog, $preferences['order']);
        if (($input['intent'] ?? '') === 'reset') {
            // Retain explicitly accepted discoveries after the restored defaults.
            $preferences['order'] = Order::ids($defaults, $catalog, array_merge(array_keys($defaults), $ids));
        } else {
            $positions = is_array($input['positions'] ?? null) ? $input['positions'] : [];
            $ids = Order::fromPositions($ids, $positions);
            $include = is_array($input['include'] ?? null) ? $input['include'] : [];
            $preferences = [
                'enabled' => ($input['enabled'] ?? '') === '1',
                'order' => Order::ids($defaults, $catalog, array_merge($ids, $include)),
            ];
        }
        update_site_option(Navigation::OPTION, $preferences);
        wp_safe_redirect(add_query_arg(['page' => self::PAGE, 'updated' => '1'], network_admin_url('admin.php')));
        exit;
    }
}
