<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * PageCategory class
 *
 * @package RRZE\Settings\Taxonomies
 */
class PageCategory
{
    /**
     * @var string
     */
    protected $postType = 'page';

    /**
     * @var string
     */
    protected $taxonomy = 'page_category';

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        add_action('init', [$this, 'set']);
        add_action('admin_init', [$this, 'register']);
    }

    /**
     * Set taxonomy
     *
     * @return void
     */
    public function set()
    {
        $labels = [
            'name' => __('Page Categories', 'rrze-settings'),
            'singular_name' => __('Page Category', 'rrze-settings'),
            'search_items' => __('Search Categories', 'rrze-settings'),
            'all_items' => __('Alle Categories', 'rrze-settings'),
            'parent_item' => __('Parent Category', 'rrze-settings'),
            'parent_item_colon' => __('Parent Category:', 'rrze-settings'),
            'edit_item' => __('Edit Category', 'rrze-settings'),
            'update_item' => __('Update Category', 'rrze-settings'),
            'add_new_item' => __('Add New Category', 'rrze-settings'),
            'new_item_name' => __('Name', 'rrze-settings'),
            'menu_name' => __('Categories', 'rrze-settings'),
        ];

        register_taxonomy(
            $this->taxonomy,
            $this->postType,
            [
                'hierarchical' => true,
                'labels' => $labels,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => $this->taxonomy],
            ]
        );
    }

    /**
     * Register taxonomy
     *
     * @return void
     */
    public function register()
    {
        register_taxonomy_for_object_type($this->taxonomy, $this->postType);
        add_action('restrict_manage_posts', [$this, 'filterList']);
        add_filter('parse_query', [$this, 'filtering']);
    }

    /**
     * Filter list
     *
     * @return void
     */
    public function filterList()
    {
        global $wp_query;
        $screen = get_current_screen();
        if ($screen->parent_file == 'edit.php?post_type=page' && get_terms($this->taxonomy)) {
            wp_dropdown_categories([
                'show_option_all' => __('All Categories', 'rrze-settings'),
                'taxonomy' => $this->taxonomy,
                'name' => $this->taxonomy,
                'orderby' => 'name',
                'selected' => (isset($wp_query->query[$this->taxonomy]) ? $wp_query->query[$this->taxonomy] : ''),
                'hierarchical' => true,
                'depth' => 6,
                'show_count' => false,
                'hide_empty' => true,
            ]);
        }
    }

    /**
     * Filter query
     *
     * @param object $query
     * @return void
     */
    public function filtering($query)
    {
        $qv = &$query->query_vars;
        if (!empty($qv[$this->taxonomy]) && is_numeric($qv[$this->taxonomy])) {
            $term = get_term_by('id', $qv[$this->taxonomy], $this->taxonomy);
            $qv[$this->taxonomy] = $term->slug;
        }
    }
}
