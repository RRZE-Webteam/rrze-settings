<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Search
 *
 * Exclude any object from front-end main search when it has the term slug
 * "nosearch" in any of the configured taxonomies.
 * 
 * @package RRZE\Settings\Taxonomies
 */
class Search
{
    /**
     * Taxonomies where "nosearch" should have effect.
     *
     * @var string[]
     */
    protected $taxonomies = ['post_tag', 'page_tag', 'attachment_tag'];

    /**
     * Term slug to exclude.
     * @var string
     */
    protected $excludeSlug = 'nosearch';

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        add_action('pre_get_posts', [$this, 'excludeNosearch']);
    }

    /**
     * Add NOT IN tax_query clauses for the configured taxonomies.
     *
     * @param \WP_Query $q
     * @return void
     */
    public function excludeNosearch($q)
    {
        if (!($q instanceof \WP_Query)) {
            return;
        }

        // Do not affect admin, non-main queries, or non-search requests.
        if (is_admin() || !$q->is_main_query() || !$q->is_search()) {
            return;
        }

        // Avoid Customizer changesets and similar virtual types.
        $pt = (array) $q->get('post_type');
        if (in_array('customize_changeset', $pt, true)) {
            return;
        }

        // Build NOT IN clauses per taxonomy (only for existing taxonomies).
        $tax_query = (array) $q->get('tax_query');
        $added = 0;

        foreach ($this->taxonomies as $tax) {
            if (!taxonomy_exists($tax)) {
                continue;
            }

            // Find the term by slug in this taxonomy.
            $term = get_term_by('slug', $this->excludeSlug, $tax);
            if ($term && !is_wp_error($term)) {
                $tax_query[] = [
                    'taxonomy'         => $tax,
                    'field'            => 'term_id',
                    'terms'            => [(int) $term->term_id],
                    'operator'         => 'NOT IN',
                    'include_children' => true, // usual behavior for hierarchical tax.
                ];
                $added++;
            }
        }

        if ($added > 0) {
            // If there are existing conditions, leave them ANDed by default.
            // You can set relation explicitly if you need advanced logic.
            $q->set('tax_query', $tax_query);
        }
    }
}
