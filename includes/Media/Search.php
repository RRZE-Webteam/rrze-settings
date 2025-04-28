<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

/**
 * Class Search
 *
 * This class handles the search functionality for media items in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class Search
{
    /**
     * Plugin loaded action
     */
    public function loaded()
    {
        // Media search enhanced
        add_filter('posts_where', [$this, 'mediaPostsWhere']);
        add_filter('posts_join', [$this, 'mediaPostsJoin']);
        add_filter('posts_distinct', [$this, 'mediaPostsDistinct']);
    }

    /**
     * Modify the WHERE clause for media posts
     *
     * @param string $where The current WHERE clause
     * @return string The modified WHERE clause
     */
    public function mediaPostsWhere($where)
    {
        global $wp_query, $wpdb;

        $vars = $wp_query->query_vars;
        if (empty($vars)) {
            $vars = (isset($_REQUEST['query'])) ? $_REQUEST['query'] : [];
        }

        if (!empty($vars['s']) && ((isset($_REQUEST['action']) && 'query-attachments' == $_REQUEST['action']) || 'attachment' == $vars['post_type'])) {
            $where .= " AND $wpdb->posts.post_type = 'attachment' AND ($wpdb->posts.post_status = 'inherit' OR $wpdb->posts.post_status = 'private')";

            if (!empty($vars['post_parent'])) {
                $where .= " AND $wpdb->posts.post_parent = " . $vars['post_parent'];
            }


            $where .= " AND ( ($wpdb->posts.post_title LIKE '%" . $vars['s'] . "%') OR ($wpdb->posts.guid LIKE '%" . $vars['s'] . "%') OR ($wpdb->posts.post_content LIKE '%" . $vars['s'] . "%') OR ($wpdb->posts.post_excerpt LIKE '%" . $vars['s'] . "%')";
            $where .= " OR ($wpdb->postmeta.meta_key = '_wp_attachment_image_alt' AND $wpdb->postmeta.meta_value LIKE '%" . $vars['s'] . "%')";
            $where .= " OR ($wpdb->postmeta.meta_key = '_wp_attached_file' AND $wpdb->postmeta.meta_value LIKE '%" . $vars['s'] . "%')";

            // Get taxonomies for attachements
            $taxes = get_object_taxonomies('attachment');
            if (!empty($taxes)) {
                $where .= " OR (tter.slug LIKE '%" . $vars['s'] . "%')";
                $where .= " OR (ttax.description LIKE '%" . $vars['s'] . "%')";
                $where .= " OR (tter.name LIKE '%" . $vars['s'] . "%')";
            }

            $where .= " )";
        }

        return $where;
    }

    /**
     * Modify the JOIN clause for media posts
     *
     * @param string $join The current JOIN clause
     * @return string The modified JOIN clause
     */
    public function mediaPostsJoin($join)
    {
        global $wp_query, $wpdb;
        $vars = $wp_query->query_vars;
        if (empty($vars)) {
            $vars = (isset($_REQUEST['query'])) ? $_REQUEST['query'] : [];
        }

        if (!empty($vars['s']) && ((isset($_REQUEST['action']) && 'query-attachments' == $_REQUEST['action']) || 'attachment' == $vars['post_type'])) {
            $join .= " LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id";

            // Get taxonomies for attachements
            $taxes = get_object_taxonomies('attachment');
            if (!empty($taxes)) {
                $on = [];
                foreach ($taxes as $tax) {
                    $on[] = "ttax.taxonomy = '$tax'";
                }
                $on = '( ' . implode(' OR ', $on) . ' )';

                $join .= " LEFT JOIN $wpdb->term_relationships AS trel ON ($wpdb->posts.ID = trel.object_id) LEFT JOIN $wpdb->term_taxonomy AS ttax ON (" . $on . " AND trel.term_taxonomy_id = ttax.term_taxonomy_id) LEFT JOIN $wpdb->terms AS tter ON (ttax.term_id = tter.term_id) ";
            }
        }

        return $join;
    }

    /**
     * Modify the DISTINCT clause for media posts
     *
     * @return string The DISTINCT clause
     */
    public function mediaPostsDistinct()
    {
        global $wp_query;
        $vars = $wp_query->query_vars;
        if (empty($vars)) {
            $vars = (isset($_REQUEST['query'])) ? $_REQUEST['query'] : [];
        }

        if (!empty($vars['s'])) {
            return 'DISTINCT';
        }
        return '';
    }
}
