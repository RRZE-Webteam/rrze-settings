<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * PageTag class
 *
 * @package RRZE\Settings\Taxonomies
 */
class PageTag
{
    /**
     * @var string
     */
    protected $postType = 'page';

    /**
     * @var string
     */
    protected $taxonomy = 'page_tag';

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
            'name' => __('Page Tags', 'rrze-settings'),
            'singular_name' => __('Page Tag', 'rrze-settings'),
            'search_items' => __('Search Tags', 'rrze-settings'),
            'popular_items' => __('Popular Tags', 'rrze-settings'),
            'all_items' => __('All Tags', 'rrze-settings'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Tag', 'rrze-settings'),
            'update_item' => __('Update Tag', 'rrze-settings'),
            'add_new_item' => __('Add New Tag', 'rrze-settings'),
            'new_item_name' => __('Name', 'rrze-settings'),
            'separate_items_with_commas' => __('Separate tags with commas', 'rrze-settings'),
            'add_or_remove_items' => __('Add', 'rrze-settings'),
            'choose_from_most_used' => __('Choose from the most used tags', 'rrze-settings'),
            'menu_name' => __('Tags', 'rrze-settings'),
        ];

        register_taxonomy(
            $this->taxonomy,
            $this->postType,
            [
                'hierarchical' => false,
                'labels' => $labels,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => true,
                'update_count_callback' => '\_update_post_term_count',
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
        if ($screen->parent_file == 'upload.php' && get_terms($this->taxonomy)) {
            wp_dropdown_categories([
                'show_option_all' => __('All Tags', 'rrze-settings'),
                'taxonomy' => $this->taxonomy,
                'name' => $this->taxonomy,
                'orderby' => 'name',
                'selected' => (isset($wp_query->query[$this->taxonomy]) ? $wp_query->query[$this->taxonomy] : ''),
                'hierarchical' => false,
                'show_count' => true,
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
