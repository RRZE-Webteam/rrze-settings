<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Menus class
 *
 * @package RRZE\Settings\Menus
 */
class Menus extends Main
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        // Expand/Collapse menu
        if ($this->siteOptions->menus->expand_collapse_menus) {
            add_filter('wp_edit_nav_menu_walker', [$this, 'navMenuWalker'], 99);
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
            $expandCollapse = new ExpandCollapse;
            $expandCollapse->loaded();
        }

        // Menu custom column
        if ($this->siteOptions->menus->menus_custom_column) {
            $customColumn = new CustomColumn;
            $customColumn->loaded();
        }

        // Filter 'menu-quick-search' results
        if ($this->siteOptions->menus->enhanced_menu_search) {
            add_action('pre_get_posts', [$this, 'preGetPosts'], 10, 2);
        }
    }

    /**
     * Filter the nav menu walker
     *
     * @param string $class Class name of the walker
     * @return string Class name of the walker
     */
    public function navMenuWalker($class)
    {
        return __NAMESPACE__ . '\WalkerNavMenuEdit';
    }

    /**
     * Enqueue scripts for admin
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        $assetFile = include(plugin()->getPath('build') . 'menus/expand-collapse.asset.php');
        wp_register_script(
            'rrze-menus-expand-collapse',
            plugins_url('build/menus/expand-collapse.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            plugin()->getVersion(),
            true
        );
        wp_localize_script(
            'rrze-menus-expand-collapse',
            'rrzeMenusL10n',
            [
                'collapse' => __('Collapse', 'rrze-settings'),
                'expand' => __('Expand', 'rrze-settings'),
            ]
        );
    }

    /**
     * Filter 'menu-quick-search' results
     * 
     * @param  object $query WP_Query object
     * @return object WP_Query object
     */
    public function preGetPosts($query)
    {
        if (isset($_POST['action']) && $_POST['action'] == "menu-quick-search" && isset($_POST['menu-settings-column-nonce'])) {
            if (is_a($query->query_vars['walker'], '\Walker_Nav_Menu_Checklist')) {
                $query->query_vars['posts_per_page'] = 100;
            }
        }
        return $query;
    }
}
