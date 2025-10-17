<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

/**
 * AttachmentCategoryUI
 *
 * - Adds a taxonomy dropdown (attachment_category) to the Media Modal sidebar.
 * - Saves the selected term.
 * - Assigns a default term if none selected on upload/save.
 */
class AttachmentCategoryUI
{
    protected string $taxonomy = 'attachment_category';

    // Defaults for site locale:
    protected string $default_en_slug = 'general';
    protected string $default_en_name = 'Uncategorized';
    protected string $default_de_slug = 'allgemein';
    protected string $default_de_name = 'Allgemein';

    /**
     * Constructor
     * Sets up the taxonomy and UI hooks.
     *
     * @return void
     */
    public function __construct()
    {
        // Ensure taxonomy exists before doing anything.
        add_action('init', function () {
            if (!taxonomy_exists($this->taxonomy)) {
                return;
            }
            $this->maybeEnsureDefaultTerm();
        }, 20);

        // Add field to Media Modal & attachment edit screen
        add_filter('attachment_fields_to_edit', [$this, 'addCategoryField'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'saveCategoryField'], 10, 2);

        // Assign default on insert if none set
        add_action('add_attachment', [$this, 'ensureDefaultOnAdd']);
        add_action('rest_after_insert_attachment', [$this, 'ensureDefaultOnRest'], 10, 3);
    }

    /** ---------- UI FIELD ---------- */

    /**
     * Add dropdown for attachment_category in Media Modal / Edit Media screen.
     *
     * @param array   $form_fields
     * @param \WP_Post $post
     * @return array
     */
    public function addCategoryField(array $form_fields, \WP_Post $post): array
    {
        if ($post->post_type !== 'attachment' || !taxonomy_exists($this->taxonomy)) {
            return $form_fields;
        }

        // Currently selected term IDs
        $selected = wp_get_object_terms($post->ID, $this->taxonomy, ['fields' => 'ids']);
        $selected = is_wp_error($selected) ? [] : (array) $selected;
        $selected_id = $selected ? (int) $selected[0] : 0;

        // Build dropdown HTML
        $dropdown = wp_dropdown_categories([
            'taxonomy'        => $this->taxonomy,
            'name'            => "attachments[{$post->ID}][{$this->taxonomy}]",
            'orderby'         => 'name',
            'hierarchical'    => true,
            'hide_empty'      => false,
            'show_option_none' => __('— No category —', 'rrze-settings'),
            'selected'        => $selected_id,
            'echo'            => 0,
        ]);

        $form_fields[$this->taxonomy] = [
            'label' => __('Category', 'rrze-settings'),
            'input' => 'html',
            'html'  => $dropdown,
            'helps' => __('Choose a category for this file. If left empty, a default will be assigned.', 'rrze-settings'),
        ];

        return $form_fields;
    }

    /**
     * Save dropdown selection.
     *
     * @param array $post
     * @param array $attachment
     * @return array
     */
    public function saveCategoryField(array $post, array $attachment): array
    {
        $post_id = (int) ($post['ID'] ?? 0);

        if (!$post_id || !taxonomy_exists($this->taxonomy)) {
            return $post;
        }

        if (isset($attachment[$this->taxonomy])) {
            $term_id = (int) $attachment[$this->taxonomy];
            if ($term_id > 0) {
                wp_set_object_terms($post_id, [$term_id], $this->taxonomy, false);
            } else {
                // Empty selection: clear terms (we will assign default later if needed)
                wp_set_object_terms($post_id, [], $this->taxonomy, false);
            }
        }

        // If still no terms, assign default now.
        $this->assignDefaultIfNone($post_id);

        return $post;
    }

    /** ---------- DEFAULT TERM ASSIGNMENT ---------- */

    /**
     * On classic add (non-REST) ensure default term if none was set.
     */
    public function ensureDefaultOnAdd(int $post_id): void
    {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'attachment' || !taxonomy_exists($this->taxonomy)) {
            return;
        }
        $this->assignDefaultIfNone($post_id);
    }

    /**
     * On REST insert ensure default term if none in request/body.
     */
    public function ensureDefaultOnRest(\WP_Post $post, \WP_REST_Request $request, bool $creating): void
    {
        if ($post->post_type !== 'attachment' || !taxonomy_exists($this->taxonomy)) {
            return;
        }

        // If request explicitly set taxonomy, respect it; otherwise ensure default.
        if (!$request->offsetExists($this->taxonomy)) {
            $this->assignDefaultIfNone($post->ID);
        }
    }

    /**
     * If attachment has no terms in taxonomy, assign default.
     */
    protected function assignDefaultIfNone(int $attachment_id): void
    {
        $has = wp_get_object_terms($attachment_id, $this->taxonomy, ['fields' => 'ids']);
        if (!is_wp_error($has) && !empty($has)) {
            return; // already has a term
        }

        // Ensure default exists, then assign by term_id
        $default = $this->getOrCreateDefaultTerm();
        if ($default && !is_wp_error($default)) {
            wp_set_object_terms($attachment_id, [(int)$default->term_id], $this->taxonomy, false);
        }
    }

    /** ---------- DEFAULT TERM RESOLUTION / CREATION ---------- */

    /**
     * Make sure the default term exists and return it.
     *
     * @return \WP_Term|\WP_Error|null
     */
    protected function getOrCreateDefaultTerm()
    {
        $desired = $this->getDesiredDefaultSlugAndName();
        $slug = $desired['slug'];
        $name = $desired['name'];

        // Try by slug first
        $term = get_term_by('slug', $slug, $this->taxonomy);
        if ($term && !is_wp_error($term)) {
            return $term;
        }

        // If slug not found, try by name (in case someone created it manually)
        $term = get_term_by('name', $name, $this->taxonomy);
        if ($term && !is_wp_error($term)) {
            return $term;
        }

        // Create it
        $created = wp_insert_term($name, $this->taxonomy, ['slug' => $slug]);
        if (is_wp_error($created)) {
            return $created;
        }
        return get_term($created['term_id'], $this->taxonomy);
    }

    /**
     * Decide default label/slug based on site locale.
     * de_DE* -> Allgemein / allgemein
     * others -> General / general
     */
    protected function getDesiredDefaultSlugAndName(): array
    {
        $loc = get_locale();
        if (stripos($loc, 'de_') === 0) {
            return ['slug' => $this->default_de_slug, 'name' => $this->default_de_name];
        }
        return ['slug' => $this->default_en_slug, 'name' => $this->default_en_name];
    }

    /**
     * Ensure default term exists on init.
     */
    protected function maybeEnsureDefaultTerm(): void
    {
        $this->getOrCreateDefaultTerm();
    }
}
