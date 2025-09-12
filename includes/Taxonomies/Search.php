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
        add_filter('posts_where', [$this, 'excludeNosearchPostsWhere'], 10, 2);
    }

    /**
     * Exclude posts tagged 'nosearch' in queries
     *
     * @param string $where
     * @param \WP_Query $q
     * @return string
     */
    public function excludeNosearchPostsWhere($where, $q)
    {
        $pt = (array) $q->get('post_type');
        if (in_array('customize_changeset', $pt, true)) {
            return $where;
        }

        if (is_admin() || ! $q->is_main_query() || ! $q->is_search()) {
            return $where;
        }

        global $wpdb;

        $where .= " AND {$wpdb->posts}.ID NOT IN (
        SELECT tr.object_id
        FROM {$wpdb->term_relationships} AS tr
        INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
        WHERE t.slug = 'nosearch'
    )";

        return $where;
    }
}
