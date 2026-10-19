<?php

namespace RRZE\Settings\AdminMenu;

defined('ABSPATH') || exit;

/**
 * Apply network priorities to the local WordPress Settings submenu.
 */
class Navigation
{
    public const OPTION = 'rrze_settings_admin_menu_order';

    /**
     * Register the runtime sorter and its network-only configuration screen.
     *
     * @return void
     */
    public function loaded(): void
    {
        if (!is_multisite()) {
            return;
        }
        add_action('admin_menu', [$this, 'sortMenu'], PHP_INT_MAX);
        if (is_network_admin()) {
            (new Settings())->loaded();
        }
    }

    /**
     * Read independently stored preferences; the catalogue is enabled by default.
     *
     * @return array{enabled:bool,order:array} Normalized network preferences.
     */
    public static function preferences(): array
    {
        $saved = get_site_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        return [
            'enabled' => !array_key_exists('enabled', $saved) || (bool) $saved['enabled'],
            'order' => is_array($saved['order'] ?? null) ? $saved['order'] : [],
        ];
    }

    /**
     * Capture new entries and reorder existing items after plugin registration.
     *
     * @return void
     */
    public function sortMenu(): void
    {
        global $submenu;

        if (is_network_admin() || is_user_admin() || empty($submenu['options-general.php'])) {
            return;
        }
        Catalog::discover($submenu['options-general.php']);
        $preferences = self::preferences();
        if (!$preferences['enabled']) {
            return;
        }
        $catalog = Catalog::entries();
        $ids = Order::ids(Catalog::defaults(), $catalog, $preferences['order']);
        $submenu['options-general.php'] = Order::apply($submenu['options-general.php'], $catalog, $ids);
    }
}
