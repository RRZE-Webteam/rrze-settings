<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Search class
 *
 * @package RRZE\Settings\Taxonomies
 */
class Search
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        // Exclude posts tagged 'nosearch' in queries
        add_filter('posts_where', [$this, 'excludeNosearchPostsWhere']);
    }

    /**
     * Exclude posts tagged 'nosearch' in queries
     *
     * @param string $where
     * @return string
     */
    public function excludeNosearchPostsWhere($where)
    {
        global $wp_query;
        if (!is_admin() && isset($wp_query) && is_search()) {
            global $wpdb;
            $where .= " AND $wpdb->posts.ID NOT IN ";
            $where .= "(SELECT tr.object_id FROM $wpdb->term_relationships AS tr ";
            $where .= "INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id ";
            $where .= "INNER JOIN $wpdb->terms t ON t.term_id = tt.term_id ";
            $where .= "WHERE t.slug = 'nosearch')";
        }
        return $where;
    }
}
