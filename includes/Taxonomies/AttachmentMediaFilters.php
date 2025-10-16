<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use function RRZE\Settings\plugin;

/**
 * AttachmentMediaFilters
 *
 * Provides Media Modal (grid) dropdown filters for:
 * - attachment_document (hierarchical)
 * - attachment_category (hierarchical)
 * - attachment_tag      (non-hierarchical)
 *
 * It enqueues a single JS/CSS bundle and localizes two payloads:
 *   - window.RRZE_AttachmentCategory
 *   - window.RRZE_AttachmentTag
 *
 * It also augments ajax_query_attachments_args to apply the chosen filters.
 */
class AttachmentMediaFilters
{
    /**
     * Site options.
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Script/style handle for the unified bundle.
     * @var string
     */
    protected $handle = 'rrze-attachment-media-filters';

    /**
     * Taxonomies configuration.
     * - key: taxonomy name
     * - value: [
     *      'param'         => request param name used by the JS (and sent by WP collection.props),
     *      'hierarchical'  => bool (controls include_children),
     *      'i18n_all'      => string (localized “All …” label),
     *      'localize_name' => string (global var name for wp_localize_script)
     *   ]
     *
     * @var array<string, array<string, mixed>>
     */
    protected $taxonomies = [
        'attachment_document' => [
            'param'         => 'attachment_document',
            'hierarchical'  => true,
            'i18n_all'      => 'All Documents',
            'localize_name' => 'RRZE_AttachmentDocument',
        ],
        'attachment_category' => [
            'param'         => 'attachment_category',
            'hierarchical'  => true,
            'i18n_all'      => 'All Categories',
            'localize_name' => 'RRZE_AttachmentCategory',
        ],
        'attachment_tag' => [
            'param'         => 'attachment_tag',
            'hierarchical'  => false,
            'i18n_all'      => 'All Tags',
            'localize_name' => 'RRZE_AttachmentTag',
        ],
    ];

    /**
     * Constructor
     * Sets up the taxonomy and admin list filtering hooks.
     * 
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;

        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_filter('ajax_query_attachments_args', [$this, 'filterAjaxQuery']);
    }

    /**
     * Enqueue a single JS/CSS bundle and localize both taxonomies' terms.
     *
     * @param string $hook
     * @return void
     */
    public function enqueue($hook)
    {
        // Ensure Media assets are present
        wp_enqueue_media();

        // Enqueue the unified built assets
        $assetFile = include(plugin()->getPath('build') . 'taxonomies/attachment-media-filters.asset.php');

        wp_enqueue_style(
            $this->handle,
            plugins_url('build/taxonomies/attachment-media-filters.css', plugin()->getBasename()),
            [],
            $assetFile['version'] ?? plugin()->getVersion()
        );

        wp_enqueue_script(
            $this->handle,
            plugins_url('build/taxonomies/attachment-media-filters.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            $assetFile['version'] ?? plugin()->getVersion(),
            true
        );

        // Localize payloads per taxonomy (only if taxonomy exists)
        foreach ($this->taxonomies as $taxonomy => $cfg) {
            $option = 'taxonomy_' . $taxonomy;
            if (!$this->siteOptions->taxonomies->$option) {
                continue;
            }

            // Build terms payload
            if ($cfg['hierarchical']) {
                $termsPayload = $this->getFlatTermsWithDepth($taxonomy);
            } else {
                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]);
                $termsPayload = array_values(array_map(function ($t) {
                    return [
                        'term_id' => (int) $t->term_id,
                        'slug'    => $t->slug,
                        'name'    => $t->name,
                        'count'   => (int) $t->count,
                    ];
                }, is_array($terms) ? $terms : []));
            }

            wp_localize_script($this->handle, $cfg['localize_name'], [
                'taxonomy' => $taxonomy,
                'terms'    => $termsPayload,
                'i18n'     => [
                    'all' => __($cfg['i18n_all'], 'rrze-settings'),
                ],
            ]);
        }
    }

    /**
     * Apply filters coming from the Media Modal to the attachments AJAX query.
     * Supports attachment_documents, attachment_category (hierarchical) and attachment_tag (flat).
     *
     * @param array $query
     * @return array
     */
    public function filterAjaxQuery($query)
    {
        foreach ($this->taxonomies as $taxonomy => $cfg) {
            $paramName = $cfg['param'];

            // Read request param (sent by media collection props)
            $param = isset($_REQUEST[$paramName]) ? sanitize_text_field(wp_unslash($_REQUEST[$paramName])) : '';

            if ($param === '' || !taxonomy_exists($taxonomy)) {
                continue;
            }
            if ($param === '0' || strtolower($param) === 'all') {
                continue;
            }

            // Accept slug or numeric ID
            $term = ctype_digit($param)
                ? get_term((int) $param, $taxonomy)
                : get_term_by('slug', $param, $taxonomy);

            if (!$term || is_wp_error($term)) {
                continue;
            }

            $taxQuery = isset($query['tax_query']) ? (array) $query['tax_query'] : [];
            $taxQuery[] = [
                'taxonomy'         => $taxonomy,
                'field'            => 'term_id',
                'terms'            => [(int) $term->term_id],
                'include_children' => (bool) $cfg['hierarchical'], // true for document or category, false for tag
                'operator'         => 'IN',
            ];
            $query['tax_query'] = $taxQuery;
        }

        return $query;
    }

    /**
     * Flatten hierarchical terms with depth for UI indentation.
     *
     * @param string $taxonomy
     * @return array<int, array{term_id:int,slug:string,name:string,depth:int,count:int}>
     */
    protected function getFlatTermsWithDepth(string $taxonomy): array
    {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'parent'     => 0,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $out = [];
        $walker = function ($parentTerms, $depth) use (&$walker, &$out, $taxonomy) {
            foreach ((array) $parentTerms as $t) {
                $out[] = [
                    'term_id' => (int) $t->term_id,
                    'slug'    => $t->slug,
                    'name'    => $t->name,
                    'depth'   => (int) $depth,
                    'count'   => (int) $t->count,
                ];
                $children = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'parent'     => (int) $t->term_id,
                ]);
                if (!is_wp_error($children) && !empty($children)) {
                    $walker($children, $depth + 1);
                }
            }
        };

        $walker($terms, 0);
        return $out;
    }
}
