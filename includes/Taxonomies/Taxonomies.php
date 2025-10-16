<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Taxonomies class
 *
 * @package RRZE\Settings\Taxonomies
 */
class Taxonomies extends Main
{
    /**
     * @var array
     */
    protected $emptyTaxonomies = [];

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        // Register taxonomy attachment_document
        if ($this->siteOptions->taxonomies->taxonomy_attachment_document) {
            new AttachmentDocument();
        }

        $isRRZEDownloadsActive = is_plugin_active('rrze-downloads/rrze-downloads.php');

        if (!$isRRZEDownloadsActive) {
            // Register taxonomy attachment_category
            if ($this->siteOptions->taxonomies->taxonomy_attachment_category) {
                new AttachmentCategory();
                // new AttachmentCategoryUI();
            }

            // Register taxonomy attachment_tag
            if ($this->siteOptions->taxonomies->taxonomy_attachment_tag) {
                new AttachmentTag();
            }

            // Set taxonomy media filters
            if (
                $this->siteOptions->taxonomies->taxonomy_attachment_document
                || $this->siteOptions->taxonomies->taxonomy_attachment_category
                || $this->siteOptions->taxonomies->taxonomy_attachment_tag
            ) {
                new AttachmentMediaFilters($this->siteOptions);
            }
        }

        // Register taxonomy page_category
        if ($this->siteOptions->taxonomies->taxonomy_page_category) {
            new PageCategory();
        }

        // Register taxonomy page_tag
        if ($this->siteOptions->taxonomies->taxonomy_page_tag) {
            new PageTag();
        }

        // Exclude posts tagged 'nosearch' in queries
        if ($this->siteOptions->taxonomies->exclude_nosearch_posts) {
            new Search();
        }
    }

    /**
     * Set taxonomy row actions
     *
     * @return void
     */
    public function setTaxonomyRowActions()
    {
        global $pagenow;

        if ($pagenow != 'edit-tags.php') {
            return;
        }

        $taxonomies = get_taxonomies();

        foreach ($taxonomies as $taxonomy) {
            add_filter($taxonomy . '_row_actions', [$this, 'taxonomyRowActions'], 10, 2);
        }

        $this->setEmptyTaxonomies();

        add_filter('check_admin_referer', [$this, 'taxonomyCheckReferrer']);
        add_filter('check_ajax_referer', [$this, 'taxonomyCheckReferrer']);
    }

    /**
     * Update empty taxonomies
     *
     * @return void
     */
    public function updateEmptyTaxonomies()
    {
        $transient = 'empty-taxonomies';
        delete_transient($transient);
    }

    /**
     * Set taxonomy row actions
     *
     * @param array $actions
     * @param object $tag
     * @return array
     */
    public function taxonomyRowActions($actions, $tag)
    {
        if (!in_array($tag->term_id, $this->emptyTaxonomies)) {
            unset($actions['delete']);
        }

        return $actions;
    }

    /**
     * Check taxonomy referrer
     *
     * @param string $action
     * @return void
     */
    public function taxonomyCheckReferrer($action)
    {
        if (!$this->isTaxonomyDeleteRequest()) {
            return;
        }

        $prefix = 'delete-tag_';
        if (strpos($action, $prefix) !== 0) {
            return;
        }

        $termId = max(0, (int) substr($action, strlen($prefix)));
        if (!in_array($termId, $this->emptyTaxonomies)) {
            wp_die(__('The term can not be deleted.', 'rrze-settings'));
        }
    }

    /**
     * Set empty taxonomies
     *
     * @return void
     */
    protected function setEmptyTaxonomies()
    {
        $transient = 'empty-taxonomies';
        $cacheTime = 0; // never expires
        $emptyTaxonomies = get_transient($transient);

        if ($emptyTaxonomies === false || !is_array($emptyTaxonomies)) {
            $emptyTaxonomies = [];
        }

        if (empty($emptyTaxonomies)) {
            $taxonomies = get_taxonomies('', 'names');
            foreach ($taxonomies as $taxonomy) {
                $terms = get_terms(
                    $taxonomy,
                    [
                        'pad_counts' => true,
                        'hide_empty' => 0
                    ]
                );

                foreach ((array) $terms as $term) {
                    if ($term->count == 0) {
                        $emptyTaxonomies[] = $term->term_id;
                    }
                }
            }

            set_transient($transient, $emptyTaxonomies, $cacheTime);
        }

        $this->emptyTaxonomies = $emptyTaxonomies;
    }

    /**
     * Check if taxonomy delete request
     *
     * @return bool
     */
    protected function isTaxonomyDeleteRequest()
    {
        $notRequest = empty($_REQUEST['taxonomy']) || empty($_REQUEST['action']) || !($_REQUEST['action'] === 'delete' || $_REQUEST['action'] === 'delete-tag');

        $isRequest = !$notRequest;

        return $isRequest;
    }
}
