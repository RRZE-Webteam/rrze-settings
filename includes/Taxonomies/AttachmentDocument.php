<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use RRZE\Settings\Taxonomies\AttachmentDocumentDropdown;

/**
 * AttachmentDocument class
 *
 * @package RRZE\Settings\Taxonomies
 */
class AttachmentDocument
{
    /**
     * @var string
     */
    protected $postType = 'attachment';

    /**
     * @var string
     */
    protected $taxonomy = 'attachment_document';

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

        register_taxonomy(
            $this->taxonomy,
            $this->postType,
            [
                'hierarchical' => true,
                'labels' => $labels,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => false,
                'query_var' => true,
                'rewrite' => ['slug' => $this->taxonomy],
                'update_count_callback' => '_update_generic_term_count'
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

        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
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
                'show_option_all' => __('All Documents', 'rrze-settings'),
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

    /**
     * Enqueue admin scripts
     *
     * @return void
     */
    public function adminEnqueueScripts()
    {
        global $pagenow, $wp_query;

        if (wp_script_is('media-editor') && 'upload.php' == $pagenow) {
            $dropdownOptions = [
                'taxonomy' => $this->taxonomy,
                'hide_empty' => true,
                'hierarchical' => true,
                'orderby' => 'name',
                'selected' => isset($wp_query->query[$this->taxonomy]) ? $wp_query->query[$this->taxonomy] : '',
                'show_count' => false,
                'walker' => new AttachmentDocumentDropdown(),
                'value' => 'id',
                'echo' => false
            ];

            $attachmentTerms = wp_dropdown_categories($dropdownOptions);
            $attachmentTerms = preg_replace(['/<select([^>]*)>/', '/<\/select>/'], '', $attachmentTerms);

            echo PHP_EOL;
            echo '<script type="text/javascript">' . PHP_EOL;
            echo 'var attachment_document = {"' . $this->taxonomy . '":{"list_title":"' . html_entity_decode(__('All Documents', 'rrze-settings'), ENT_QUOTES, 'UTF-8') . '","term_list":[' . substr($attachmentTerms, 2) . ']}};' . PHP_EOL;
            echo '</script>' . PHP_EOL;
        }
    }
}
