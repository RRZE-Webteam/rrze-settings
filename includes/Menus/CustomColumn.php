<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

/**
 * Custom column for the menu items in the WordPress admin.
 *
 * @package RRZE\Settings\Menus
 */
class CustomColumn
{
    /**
     * The transient name for the nav menus object
     *
     * @var string
     */
    protected $navMenusObjectTransient = 'rrze-menus-nav-menus-object';

    /**
     * The nav menus object
     *
     * @var array
     */
    protected $navMenusObject = [];

    /**
     * Plugin loaded action
     */
    public function loaded()
    {
        add_action('init', [$this, 'getNavMenusObject']);
        add_filter('manage_edit-page_columns', [$this, 'navMenuColumn']);
        add_action('manage_page_posts_custom_column', [$this, 'navMenuPostsCustomColumn'], 10, 2);
        add_filter('query_vars', [$this, 'navMenuQueryVars']);
        add_filter('posts_clauses', [$this, 'navMenuPostsClauses'], 10, 2);
        add_action('wp_update_nav_menu', [$this, 'updateNavMenu']);
    }

    /**
     * Get the nav menus object
     *
     * @return void
     */
    public function getNavMenusObject()
    {
        global $pagenow;

        if ($pagenow != 'edit.php') {
            return;
        }

        $this->navMenusObject = get_transient($this->navMenusObjectTransient);

        if ($this->navMenusObject === false || !is_array($this->navMenusObject)) {
            $this->navMenusObject = [];
        }

        if (!empty($this->navMenusObject)) {
            return;
        }

        $navMenus = get_terms('nav_menu', ['hide_empty' => true]);
        if (is_array($navMenus)) {
            foreach ($navMenus as $menu) {
                $this->navMenusObject[$menu->term_id] = [
                    'name' => $menu->name,
                    'items' => wp_get_nav_menu_items($menu->term_id)
                ];
            }
            set_transient($this->navMenusObjectTransient, $this->navMenusObject);
        }
    }

    /**
     * Add a custom column to the page list table
     *
     * @param array $columns The columns for the page list table
     * @return array The modified columns
     */
    public function navMenuColumn($columns)
    {
        if (isset($columns['comments'])) {
            $position = array_search('comments', array_keys($columns));
        } elseif (isset($columns['date'])) {
            $position = array_search('date', array_keys($columns));
        } elseif (isset($columns['last-modified'])) {
            $position = array_search('last-modified', array_keys($columns));
        }

        if ($position !== false) {
            $columns = array_slice($columns, 0, $position, true) + ['menus' => ''] + array_slice($columns, $position, count($columns) - $position, true);
        }

        $columns['menus'] = __('Menus', 'rrze-settings');
        return $columns;
    }

    /**
     * Display the custom column content
     *
     * @param string $column The column name
     * @param int $postId The post ID
     * @return void
     */
    public function navMenuPostsCustomColumn($column, $postId)
    {
        if ($column == 'menus') {
            echo $this->getMenusInObject($postId);
        }
    }

    /**
     * Get the menus in the object
     *
     * @param int $objectId The object ID
     * @return string The menus in the object
     */
    protected function getMenusInObject($objectId)
    {
        global $post_type;

        $menus = [];

        foreach ($this->navMenusObject as $termId => $menuObject) {
            if (in_array($objectId, wp_list_pluck($menuObject['items'], 'object_id'))) {
                $menuUrl = add_query_arg('post_type', $post_type, admin_url('/edit.php'));
                $menuUrl = add_query_arg('nav_menu', $termId, $menuUrl);
                $menus[] = sprintf('<a href="%1$s">%2$s</a>', $menuUrl, $menuObject['name']);
            }
        }

        if (empty($menus)) {
            $menus[] = '&#8212;';
        }

        return implode('<br>', $menus);
    }

    /**
     * Add query vars for the nav menu
     *
     * @param array $vars The query vars
     * @return array The modified query vars
     */
    public function navMenuQueryVars($vars)
    {
        $vars[] = 'nav_menu';
        return $vars;
    }

    /**
     * Modify the posts clauses for the nav menu
     *
     * @param array $clauses The clauses
     * @param object $wpQuery The WP_Query object
     * @return array The modified clauses
     */
    public function navMenuPostsClauses($clauses, $wpQuery)
    {
        global $wpdb, $pagenow;

        $objects = [];
        $navMenuTermId = absint($wpQuery->get('nav_menu', ''));

        if ($navMenuTermId && $pagenow == 'edit.php') {
            foreach ($this->navMenusObject as $termId => $menuObject) {
                if ($navMenuTermId == $termId) {
                    $objects[] = wp_list_pluck($menuObject['items'], 'object_id');
                }
            }

            if (!empty($objects[0])) {
                $objects = implode(',', $objects[0]);
                $clauses['where'] .= " AND $wpdb->posts.ID IN ($objects)";
                $clauses['groupby'] = "$wpdb->posts.ID";
            }
        }

        return $clauses;
    }

    /**
     * Update the nav menu
     *
     * @return void
     */
    public function updateNavMenu()
    {
        delete_transient($this->navMenusObjectTransient);
    }
}
