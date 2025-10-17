<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * PageTag
 */
class PageTag extends BaseTaxonomy
{
    protected $postType = 'page';
    protected $taxonomy = 'page_tag';

    protected function getLabels(): array
    {
        return [
            'name'                       => __('Page Tags', 'rrze-settings'),
            'singular_name'              => __('Page Tag', 'rrze-settings'),
            'search_items'               => __('Search Tags', 'rrze-settings'),
            'popular_items'              => __('Popular Tags', 'rrze-settings'),
            'all_items'                  => __('All Tags', 'rrze-settings'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Tag', 'rrze-settings'),
            'update_item'                => __('Update Tag', 'rrze-settings'),
            'add_new_item'               => __('Add New Tag', 'rrze-settings'),
            'new_item_name'              => __('Name', 'rrze-settings'),
            'separate_items_with_commas' => __('Separate tags with commas', 'rrze-settings'),
            'add_or_remove_items'        => __('Add', 'rrze-settings'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'rrze-settings'),
            'menu_name'                  => __('Tags', 'rrze-settings'),
        ];
    }

    /**
     * Additional/override args for register_taxonomy().
     * 
     * @return array
     */
    protected function getTaxonomyArgs(): array
    {
        return [
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => $this->taxonomy],
            'capabilities'      => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_pages',
            ],
        ];
    }

    /**
     * Arguments for wp_dropdown_categories().
     * 
     * @param \WP_Query $wp_query
     * @return array
     */
    protected function getDropdownArgs($wp_query): array
    {
        return [
            'show_option_all' => __('All Tags', 'rrze-settings'),
            'taxonomy'        => $this->taxonomy,
            'name'            => $this->taxonomy,
            'orderby'         => 'name',
            'selected'        => isset($wp_query->query[$this->taxonomy]) ? $wp_query->query[$this->taxonomy] : '',
            'hierarchical'    => false,
            'show_count'      => true,
            'hide_empty'      => true,
        ];
    }
}
