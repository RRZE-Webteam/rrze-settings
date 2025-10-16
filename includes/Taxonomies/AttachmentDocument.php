<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use RRZE\Settings\Taxonomies\AttachmentDocumentDropdown;

/**
 * AttachmentDocument class
 *
 * @package RRZE\Settings\Taxonomies
 */
class AttachmentDocument extends BaseTaxonomy
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
    protected $taxonomy = 'attachment_document';

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
            'name' => __('Documents', 'rrze-settings'),
            'singular_name' => __('Document', 'rrze-settings'),
            'search_items' => __('Search Documents', 'rrze-settings'),
            'all_items' => __('All Documents', 'rrze-settings'),
            'parent_item' => __('Parent Document', 'rrze-settings'),
            'parent_item_colon' => __('Parent Document:', 'rrze-settings'),
            'edit_item' => __('Edit Category', 'rrze-settings'),
            'update_item' => __('Update Category', 'rrze-settings'),
            'add_new_item' => __('Add New Category', 'rrze-settings'),
            'new_item_name' => __('Name', 'rrze-settings'),
            'menu_name' => __('Documents', 'rrze-settings')
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
            'hierarchical'          => true,
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
     * Default: Page list screen (to match the original two classes).
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
     * Child classes may override to tweak labels/hierarchy.
     * 
     * @param \WP_Query $wp_query
     * @return array
     */
    protected function getDropdownArgs($wp_query): array
    {
        return [
            'show_option_all' => __('All Documents', 'rrze-settings'),
            'taxonomy'        => $this->taxonomy,
            'name'            => $this->taxonomy,
            'orderby'         => 'name',
            'selected'        => isset($wp_query->query[$this->taxonomy]) ? $wp_query->query[$this->taxonomy] : '',
            'hierarchical'    => true,
            'depth'           => 6,
            'show_count'      => false,
            'hide_empty'      => true,
        ];
    }
}
