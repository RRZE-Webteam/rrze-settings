<?php

namespace RRZE\Settings\Posts;

defined('ABSPATH') || exit;

/**
 * Page class
 * 
 * This class handles the page list table in the admin area.
 * It adds a dropdown to filter the pages by parent page.
 * 
 * @package RRZE\Settings\Page
 */
class Page
{
    /**
     * @var object \RRZE\Settings\Options
     */
    protected $siteOptions;

    /**
     * Constructor
     * @param object $siteOptions
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Load page settings
     * @return void
     */
    public function loaded()
    {
        // Page list table dropdown
        if ($this->siteOptions->posts->page_list_table_dropdown && isset($_GET['post_type']) && is_post_type_hierarchical($_GET['post_type'])) {
            add_action('restrict_manage_posts', [$this, 'filterByParentPage']);
            add_filter('parse_query', [$this, 'filterThePages']);
        }
    }

    /**
     * Add dropdown for parent page in the page list table
     * @return void
     */
    public function filterByParentPage()
    {
        global $pagenow;

        if (
            $pagenow == 'edit.php' &&
            isset($_GET['post_type']) && $_GET['post_type'] == 'page'
        ) {
            if (isset($_GET['post_status']) && !in_array($_GET['post_status'], ['publish', 'all'])) {
                return;
            }

            $dropdownOptions = [];

            $parentId = !empty($_GET['parentId']) ? absint($_GET['parentId']) : 0;
            if ($parentId) {
                $dropdownOptions = [
                    'show_option_none' => __('All page levels', 'rrze-settings'),
                    'depth' => 6,
                    'hierarchical' => true,
                    'post_type' => 'page',
                    'sort_column' => 'name',
                    'selected' => $parentId,
                    'name' => 'parentId'
                ];
            } else {
                $dropdownOptions = [
                    'show_option_none' => __('All page levels', 'rrze-settings'),
                    'depth' => 6,
                    'hierarchical' => true,
                    'post_type' => 'page',
                    'sort_column' => 'name',
                    'name' => 'parentId'
                ];
            }

            wp_dropdown_pages($dropdownOptions);
        }
    }

    /**
     * Filter the pages by parent page
     * @param object $query
     * @return void
     */
    public function filterThePages($query)
    {
        $parentId = !empty($_GET['parentId']) ? absint($_GET['parentId']) : 0;
        if (!$parentId) {
            return;
        }

        if (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'page') {
            $pages = $this->getChildPages($parentId);
            $pages[] = $parentId;
            $query->set('post__in', $pages);
        }
    }

    /**
     * Get child pages of a parent page
     * @param int $parent_id
     * @return array
     */
    protected function getChildPages($parent_id)
    {
        global $wpdb;
        $child_pages = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'page'", $parent_id));
        if (!empty($child_pages)) {
            foreach ($child_pages as $child_page) {
                $child_pages = array_merge($child_pages, $this->getChildPages($child_page));
            }
        }
        return $child_pages;
    }
}
