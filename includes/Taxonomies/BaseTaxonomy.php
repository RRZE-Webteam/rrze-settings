<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * BaseTaxonomy
 *
 * An extensible base to register a taxonomy for a given post type,
 * attach admin list filters, and normalize numeric term ids to slugs in queries.
 *
 * Child classes should at minimum set $postType and $taxonomy and implement getLabels().
 * They may override getTaxonomyArgs(), shouldRenderDropdown(), and getDropdownArgs().
 * 
 * @package RRZE\Settings\Taxonomies
 */
abstract class BaseTaxonomy
{
    /**
     * The object type this taxonomy attaches to.
     * 
     * @var string
     */
    protected $postType = 'post';

    /**
     * The taxonomy slug.
     * 
     * @var string
     */
    protected $taxonomy = '';

    /**
     * Constructor
     * Sets up the taxonomy and admin list filtering hooks.
     * 
     * @return void
     */
    public function __construct()
    {
        add_action('init', [$this, 'set'], 0);
        add_action('init', [$this, 'register'], 5);
    }

    /**
     * Register the taxonomy itself.
     * 
     * @return void
     */
    public function set()
    {
        $labels = $this->getLabels();

        $args = wp_parse_args(
            $this->getTaxonomyArgs(),
            [
                'labels'                => $labels,
                'hierarchical'          => true,
                'public'                => true,
                'show_ui'               => true,
                'show_admin_column'     => true,
                'show_in_nav_menus'     => true,
                'show_in_rest'          => true,
                'query_var'             => true,
                'rewrite'               => ['slug' => $this->taxonomy],
                'update_count_callback' => '_update_post_term_count',
            ]
        );

        register_taxonomy($this->taxonomy, $this->postType, $args);
    }

    /**
     * Link taxonomy to object type and set admin list filtering hooks.
     * 
     * @return void
     */
    public function register()
    {
        register_taxonomy_for_object_type($this->taxonomy, $this->postType);
        add_action('restrict_manage_posts', [$this, 'filterList']);
        add_filter('parse_query', [$this, 'filtering']);
    }

    /**
     * Render the dropdown filter in list tables when appropriate.
     * 
     * @return void
     */
    public function filterList()
    {
        // Only run in admin list screens.
        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen instanceof \WP_Screen) {
            return;
        }

        if (!$this->shouldRenderDropdown($screen)) {
            return;
        }

        if (!get_terms(['taxonomy' => $this->taxonomy, 'hide_empty' => false])) {
            return;
        }

        global $wp_query;

        wp_dropdown_categories($this->getDropdownArgs($wp_query));
    }

    /**
     * Normalize query vars if taxonomy param is numeric (term_id) -> convert to slug.
     * 
     * @param \WP_Query $query
     * @return void
     */
    public function filtering($query)
    {
        if (!is_admin() || !($query instanceof \WP_Query)) {
            return;
        }

        $qv = &$query->query_vars;
        if (!empty($qv[$this->taxonomy]) && is_numeric($qv[$this->taxonomy])) {
            $term = get_term_by('id', (int) $qv[$this->taxonomy], $this->taxonomy);
            if ($term && !is_wp_error($term)) {
                $qv[$this->taxonomy] = $term->slug;
            }
        }
    }

    /**
     * Labels for register_taxonomy().
     * Child classes MUST implement.
     * 
     * @return array
     */
    abstract protected function getLabels(): array;

    /**
     * Additional/override args for register_taxonomy().
     * 
     * @return array
     */
    protected function getTaxonomyArgs(): array
    {
        return [];
    }

    /**
     * Whether the dropdown should be shown on the current screen.
     * Default: Post type list screen.
     * 
     * @param \WP_Screen $screen
     * @return bool
     */
    protected function shouldRenderDropdown(\WP_Screen $screen): bool
    {
        return ($screen->parent_file === 'edit.php?post_type=' . $this->postType);
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
            'show_option_all' => __('All Categories', 'rrze-settings'),
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
