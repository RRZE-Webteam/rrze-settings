<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * AttachmentTag
 * 
 * @package RRZE\Settings\Taxonomies
 */
class AttachmentTag extends BaseTaxonomy
{
    /**
     * The object type this taxonomy attaches to.
     * 
     * @var string
     */
    protected $postType = 'attachment';

    /**
     * The taxonomy slug.
     * 
     * @var string
     */
    protected $taxonomy = 'attachment_tag';

    /**
     * Constructor
     * Sets up the taxonomy and recount hooks.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Recount hooks
        add_action('set_object_terms', ['RRZE\\Settings\\Helper', 'recountOnSet'], 10, 4);
        add_action('deleted_term_relationships', ['RRZE\\Settings\\Helper', 'recountOnDelete'], 10, 2);
        add_action('rest_after_insert_attachment', ['RRZE\\Settings\\Helper', 'recountOnRest'], 10, 3);
    }

    /**
     * Labels for register_taxonomy().
     * 
     * @return array
     */
    protected function getLabels(): array
    {
        return [
            'name'                       => __('Attachment Tags', 'rrze-settings'),
            'singular_name'              => __('Attachment Tag', 'rrze-settings'),
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
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => false,
            'show_in_rest'          => true,
            'query_var'             => true,
            'update_count_callback' => ['RRZE\\Settings\\Helper', 'updateAttachmentTermCount'],
        ];
    }

    /**
     * Whether the dropdown should be shown on the current screen.
     * 
     * @param \WP_Screen $screen
     * @return bool
     */
    protected function shouldRenderDropdown(\WP_Screen $screen): bool
    {
        // Media Library list table.
        return ($screen->parent_file === 'upload.php');
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
