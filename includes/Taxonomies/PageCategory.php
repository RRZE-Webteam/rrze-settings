<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * PageCategory
 * 
 * @package RRZE\Settings\Taxonomies
 */
class PageCategory extends BaseTaxonomy
{
    /**
     * The object type this taxonomy attaches to.
     * 
     * @var string
     */
    protected $postType = 'page';

    /**
     * The taxonomy slug.
     * 
     * @var string
     */
    protected $taxonomy = 'page_category';

    /**
     * Labels for register_taxonomy().
     * 
     * @return array
     */
    protected function getLabels(): array
    {
        return [
            'name'               => __('Page Categories', 'rrze-settings'),
            'singular_name'      => __('Page Category', 'rrze-settings'),
            'search_items'       => __('Search Categories', 'rrze-settings'),
            'all_items'          => __('Alle Categories', 'rrze-settings'),
            'parent_item'        => __('Parent Category', 'rrze-settings'),
            'parent_item_colon'  => __('Parent Category:', 'rrze-settings'),
            'edit_item'          => __('Edit Category', 'rrze-settings'),
            'update_item'        => __('Update Category', 'rrze-settings'),
            'add_new_item'       => __('Add New Category', 'rrze-settings'),
            'new_item_name'      => __('Name', 'rrze-settings'),
            'menu_name'          => __('Categories', 'rrze-settings'),
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
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => $this->taxonomy],
            'default_term'       => [
                'name' => (get_locale() === 'de_DE' ? 'Allgemein' : 'Uncategorized'),
                'slug' => (get_locale() === 'de_DE' ? 'allgemein' : 'general'),
            ],
            'capabilities'      => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_pages',
            ],
        ];
    }
}
