<?php

namespace RRZE\Settings\Writing;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use RRZE\Settings\Options;
use RRZE\Settings\Helper;
use RRZE\Settings\Writing\BlockEditor\Editor as BlockEditor;
use RRZE\Settings\Writing\BlockEditor\Blocks as Blocks;

/*
 * Writing class
 *
 * @package RRZE\Settings\Writing
 */

class Writing extends Main
{
    /**
     * @var int
     */
    protected $minPostLock = 5;

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

        add_filter('manage_sites-network_columns', [$this, 'addSitesColumns']);
        add_action('manage_sites_custom_column', [$this, 'manageSitesColumn'], 10, 3);

        // Add a filter dropdown to filter websites by editor in Network → Sites
        add_action('manage_sites_extra_tablenav', [$this, 'addSitesEditorFilter'], 10, 1);
        add_filter('ms_sites_list_table_query_args', [$this, 'filterSitesByEditor'], 10, 1);

        // Preserve current query args (e.g., site_editor, search, orderby) in status view links
        add_filter('views_sites-network', [$this, 'preserveQueryArgsInStatusViews'], 10, 1);

        // Make the "Editor" column sortable in Network → Sites
        add_filter('manage_sites-network_sortable_columns', [$this, 'manageSitesSortableColumns'], 10, 1);

        // Custom sort implementation for our virtual "site_editor" orderby
        add_filter('sites_pre_query', [$this, 'maybeSortSitesByEditor'], 10, 2);

        // Enable/Disable post lock networkwide
        if ($this->siteOptions->writing->enable_post_lock) {
            // Filter the post lock window duration.
            // https://developer.wordpress.org/reference/hooks/wp_wp_check_post_lock_window/
            add_filter('wp_wp_check_post_lock_window', [$this, 'filterPostLock']);
        }

        if ($this->siteOptions->writing->disable_custom_fields_metabox) {
            add_action('admin_menu', function () {
                $postTypes = get_post_types([], 'names');
                foreach ($postTypes as $postType) {
                    remove_meta_box('postcustom', $postType, 'normal');
                }
            });
        }

        // Enable/Disable the block editor
        $this->maybeLoadBlockEditor();

        // Disables loading of the block directory assets
        if ($this->siteOptions->writing->disable_block_directory_assets) {
            remove_action(
                'enqueue_block_editor_assets',
                'wp_enqueue_editor_block_directory_assets'
            );
        }

        // Disable remote block patterns
        if ($this->siteOptions->writing->disable_remote_block_patterns) {
            add_filter('should_load_remote_block_patterns', '__return_false');
        }

        // Disable block editor settings
        add_filter('block_editor_settings_all', [$this, 'filterBlockEditorSettings'], 10, 2);
    }

    /**
     * Filter the post lock window duration.
     *
     * @param int $interval The post lock window duration in seconds.
     * @return int The filtered post lock window duration in seconds.
     */
    public function filterPostLock($interval)
    {
        $postLock = $this->options->writing->post_lock;
        if (absint($postLock) >= $this->minPostLock) {
            $interval = $postLock;
        }

        return $interval;
    }

    /**
     * Maybe load the block editor
     *
     * @return void
     */
    protected function maybeLoadBlockEditor()
    {
        if (!$this->isBlockEditorEnabled()) {
            // Disable Block Editor Widgets
            add_filter('use_widgets_block_editor', '__return_false');

            // Dequeue Block Editor Styles            
            add_action('wp_enqueue_scripts', [$this, 'dequeueBlockEditorStyles'], 100);

            // Remove Block Editor Hooks
            add_action('init', [$this, 'removeBlockEditorHooks']);

            // Returns whether the post can be edited in the block editor.
            add_filter('use_block_editor_for_post', function ($canEdit, $post) {
                $blockEditor = new BlockEditor($this->siteOptions);
                return $blockEditor->canEdit($canEdit, $post);
            }, 10, 2);
        } else {
            add_action('init', fn() => new Blocks($this->siteOptions));
            $deactivatedPlugins = $this->siteOptions->writing->deactivated_plugins;
            if (!empty($deactivatedPlugins) && is_array($deactivatedPlugins)) {
                foreach ($deactivatedPlugins as $plugin) {
                    add_action('admin_init', function () use ($plugin) {
                        if (is_plugin_active($plugin) && !is_plugin_active_for_network($plugin)) {
                            $pluginFile = WP_PLUGIN_DIR . '/' . $plugin;
                            $pluginData = get_plugin_data($pluginFile);
                            $pluginName = $pluginData['Name'];
                            // $silent = true, $network_wide = false
                            deactivate_plugins($plugin, true, false);
                            $transient = 'rrze-settings-writing-' . sanitize_title($pluginName);
                            set_transient($transient, true, 30);
                            Helper::flashAdminNotice(
                                $transient,
                                sprintf(
                                    /* translators: %s: Plugin name */
                                    __('The plugin "%s" has been deactivated because the block editor is enabled on this website.', 'rrze-settings'),
                                    $pluginName
                                ),
                                'error'
                            );
                        }
                    }, 10, 0);
                }
            }
        }
    }

    /**
     * Check whether the block editor is enabled
     *
     * @return boolean
     */
    protected function isBlockEditorEnabled()
    {
        if ($this->siteOptions->writing->enable_block_editor) {
            return true;
        } elseif (!$this->options->writing->enable_classic_editor) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether the current website is defined as code-editor-friendly.
     * 
     * @return boolean
     */
    public function hasCodeEditorWebsiteException(): bool
    {
        $currentSite = get_current_blog_id();
        $codeEditorWebsitesExceptions = (array) $this->siteOptions->writing->code_editor_websites_exceptions;
        if (in_array($currentSite, array_keys($codeEditorWebsitesExceptions))) {
            return true;
        }
        return false;
    }

    /**
     * Dequeue Block Editor Styles
     *
     * @return void
     */
    public function dequeueBlockEditorStyles()
    {
        global $post;
        $blockEditor = new BlockEditor($this->siteOptions);
        if (empty($post->ID) || !$blockEditor->isAllowedPostType($post->ID)) {
            // Remove CSS on the front end.
            wp_dequeue_style('wp-block-library');

            // Remove CSS block library theme.
            wp_dequeue_style('wp-block-library-theme');

            // Remove inline global CSS on the front end.
            wp_dequeue_style('global-styles');
        }
    }

    /**
     * Remove Block Editor Hooks
     *
     * @return void
     */
    public function removeBlockEditorHooks()
    {
        global $post;
        $blockEditor = new BlockEditor($this->siteOptions);
        if (empty($post->ID) || !$blockEditor->isAllowedPostType($post->ID)) {
            // Remove the global styles defined via theme.json
            remove_action(
                'wp_enqueue_scripts',
                'wp_enqueue_global_styles'
            );

            // Remove the SVG filters supplied by theme.json
            remove_action(
                'wp_body_open',
                'wp_global_styles_render_svg_filters'
            );
        }
    }

    /**
     * Filter Block Editor Settings
     *
     * @param array $settings
     * @param object $context
     * @return array
     */
    public function filterBlockEditorSettings($settings, $context)
    {
        $postId = $context->post->ID ?? null;
        $blockEditor = new BlockEditor($this->siteOptions);

        if (!empty($this->siteOptions->writing->sync_autosave)) {
            // Sync: autosaveInterval = max(60, 2 × heartbeat->editor_interval)
            $settings['autosaveInterval'] = max(60, 2 * (int) $this->siteOptions->heartbeat->editor_interval);
        } else {
            $settings['autosaveInterval'] = max(60, (int) $this->siteOptions->writing->autosave_interval);
        }

        // Disable Openverse
        if ($this->siteOptions->writing->disable_openverse_media) {
            $settings['enableOpenverseMediaCategory'] = false;
        }

        // Disable Font Library UI
        if ($this->siteOptions->writing->disable_font_library_ui) {
            $settings['fontLibraryEnabled'] = false;
        }

        if (empty($postId) || !$blockEditor->isAllowedPostType($postId)) {
            // Disable the Code Editor option from the Block Editor settings
            if (
                $this->siteOptions->writing->disable_code_editor && !is_super_admin()
                && !$this->hasCodeEditorWebsiteException()
            ) {
                $settings['codeEditingEnabled'] = false;
            }
        }

        return $settings;
    }

    /**
     * Add websites columns
     *
     * @param array $columns
     * @return array
     */
    public function addSitesColumns($columns)
    {
        $columns['site_editor'] = __('Editor', 'rrze-settings');

        return $columns;
    }

    /**
     * Manage websites column
     *
     * @param string $columnName
     * @param int $blogId
     * @return void
     */
    public function manageSitesColumn($columnName, $blogId)
    {
        if ($columnName != 'site_editor') {
            return;
        }

        $isBlockEditorEnabled = false;
        $blogId = absint($blogId);

        switch_to_blog($blogId);
        $options = (array) get_option(Options::OPTION_NAME);
        $options = Options::parseOptions($options);
        if ($this->siteOptions->writing->enable_block_editor) {
            $isBlockEditorEnabled = true;
        }
        if (
            $options->writing->try_enable_block_editor &&
            !$options->writing->enable_classic_editor
        ) {
            $isBlockEditorEnabled = true;
        }
        restore_current_blog();

        echo $isBlockEditorEnabled ? __('Block', 'rrze-settings') : __('Classic', 'rrze-settings');
    }

    /**
     * Render Block/Classic filter dropdown on Network → Sites.
     *
     * @param string $which 'top' or 'bottom'
     * @return void
     */
    public function addSitesEditorFilter($which)
    {
        if ($which !== 'top') {
            return;
        }

        $current = isset($_GET['site_editor']) ? sanitize_text_field(wp_unslash($_GET['site_editor'])) : '';
?>
        <div class="alignleft actions">
            <label for="filter-by-site-editor" class="screen-reader-text">
                <?php esc_html_e('Filter by editor', 'rrze-settings'); ?>
            </label>
            <select name="site_editor" id="filter-by-site-editor">
                <option value=""><?php esc_html_e('All editors', 'rrze-settings'); ?></option>
                <option value="block" <?php selected($current, 'block'); ?>>
                    <?php esc_html_e('Block', 'rrze-settings'); ?>
                </option>
                <option value="classic" <?php selected($current, 'classic'); ?>>
                    <?php esc_html_e('Classic', 'rrze-settings'); ?>
                </option>
            </select>
            <?php submit_button(__('Filter'), 'secondary', 'filter_action', false); ?>
        </div>
<?php
    }

    /**
     * Filter the sites query by editor type using site__in.
     *
     * @param array $args
     * @return array
     */
    public function filterSitesByEditor($args)
    {
        if (empty($_GET['site_editor'])) {
            return $args;
        }

        $filter = sanitize_text_field(wp_unslash($_GET['site_editor']));
        if (!in_array($filter, ['block', 'classic'], true)) {
            return $args;
        }

        if (!empty($args['site__in']) && is_array($args['site__in'])) {
            $baseIds = array_map('absint', $args['site__in']);
        } else {
            $queryArgs = [
                'fields' => 'ids',
                'number' => 0,
                'count'  => false, // important
            ];
            $baseIds = get_sites($queryArgs);
            if (is_int($baseIds)) {
                $baseIds = [];
            }
        }

        if (empty($baseIds)) {
            $args['site__in'] = [0];
            return $args;
        }

        $map = $this->getEditorMapCached($baseIds);
        $wanted = [];
        foreach ($baseIds as $id) {
            if (($map[$id] ?? null) === $filter) {
                $wanted[] = $id;
            }
        }

        $args['site__in'] = !empty($wanted) ? $wanted : [0];
        return $args;
    }

    /**
     * Preserve current query args (e.g., site_editor, search, orderby) in status view links.
     *
     * @param array $views Array of HTML links (status filters) keyed by view slug.
     * @return array
     */
    public function preserveQueryArgsInStatusViews(array $views): array
    {
        // Build a whitelist of current GET params to preserve across status switches.
        $preserve = [];
        foreach ($_GET as $key => $value) {
            // Skip WP internals and params that views will overwrite (status/pagination/actions).
            if (in_array($key, ['status', 'paged', 'action', 'action2', 'filter_action', '_wpnonce', '_wp_http_referer'], true)) {
                continue;
            }
            if (is_scalar($value) && $value !== '') {
                $preserve[$key] = sanitize_text_field(wp_unslash($value));
            }
        }

        if (empty($preserve)) {
            return $views;
        }

        // Inject preserved args into each <a href="..."> in the views array.
        foreach ($views as $slug => $html) {
            if (preg_match('/href="([^"]+)"/', $html, $m)) {
                $url = $m[1];
                $url = add_query_arg($preserve, $url);
                // Replace the href in the original HTML.
                $views[$slug] = str_replace($m[1], esc_url($url), $html);
            }
        }

        return $views;
    }

    /**
     * Build (and cache) a map of site_id => 'block'|'classic' for a given ID set.
     *
     * @param int[] $siteIds
     * @param int   $ttl Cache TTL in seconds
     * @return array<int,string> Map of blog_id => 'block'|'classic'
     */
    protected function getEditorMapCached(array $siteIds, int $ttl = 120): array
    {
        if (!is_array($siteIds)) {
            $siteIds = [];
        }

        // Normalize key: stable across order; bump $version to invalidate globally if logic changes.
        sort($siteIds, SORT_NUMERIC);
        $version = 'v1';
        $key     = 'rrze_sites_editor_map_' . $version . '_' . md5(implode(',', $siteIds));

        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }

        $map = $this->computeEditorMap($siteIds);
        set_transient($key, $map, $ttl);
        return $map;
    }

    /**
     * Compute a map of site_id => 'block'|'classic' using per-site options.
     *
     * @param int[] $siteIds
     * @return array<int,string>
     */
    protected function computeEditorMap(array $siteIds): array
    {
        $map = [];

        foreach ($siteIds as $blogId) {
            $blogId = absint($blogId);
            if ($blogId <= 0) {
                continue;
            }

            switch_to_blog($blogId);

            // Mirror the same logic used in the column.
            $optionsArr = (array) get_option(Options::OPTION_NAME);
            $opts       = Options::parseOptions($optionsArr);

            $isBlock = false;

            if ($this->siteOptions->writing->enable_block_editor) {
                $isBlock = true;
            }
            if ($opts->writing->try_enable_block_editor && !$opts->writing->enable_classic_editor) {
                $isBlock = true;
            }

            restore_current_blog();

            $map[$blogId] = $isBlock ? 'block' : 'classic';
        }

        return $map;
    }

    /**
     * Declare the "Editor" column sortable.
     *
     * @param array $columns
     * @return array
     */
    public function manageSitesSortableColumns($columns)
    {
        $columns['site_editor'] = 'site_editor';
        return $columns;
    }

    /**
     * Implement custom sorting for orderby=site_editor via sites_pre_query short-circuit.
     * Asc: Block first, then Classic. Desc: Classic first, then Block.
     *
     * @param null|array     $results If non-null, short-circuits WP_Site_Query and returns these results.
     * @param \WP_Site_Query $query   The ongoing site query.
     * @return null|array
     */
    public function maybeSortSitesByEditor($results, $query)
    {
        // Re-entrancy guard: avoid infinite recursion when we call get_sites() below.
        static $inProgress = false;
        if ($inProgress) {
            return $results;
        }

        // WP_Site_Query doesn't have ->get(); read from ->query_vars.
        $vars = (is_object($query) && isset($query->query_vars) && is_array($query->query_vars)) ? $query->query_vars : [];

        // Do not touch COUNT queries — the list table needs an integer.
        if (!empty($vars['count'])) {
            return $results;
        }

        $orderby = isset($vars['orderby']) ? $vars['orderby'] : '';
        if ($orderby !== 'site_editor') {
            return $results; // Not our custom order; do nothing.
        }

        $order = strtolower(isset($vars['order']) ? (string) $vars['order'] : 'asc');
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'asc';

        // Base args: remove our virtual orderby so core doesn't choke; fetch IDs to sort in PHP.
        $base = $vars;
        unset($base['orderby'], $base['order']);
        $base['fields'] = 'ids';

        // Capture pagination then remove it; we'll slice after sorting.
        $number = isset($base['number']) ? (int) $base['number'] : 100;
        $offset = isset($base['offset']) ? (int) $base['offset'] : 0;
        unset($base['number'], $base['offset']);

        // Force non-count result (avoid integer).
        unset($base['count']);
        $base['count'] = false;

        // Prevent re-entry of this filter when calling get_sites().
        $inProgress = true;
        $candidateIds = get_sites($base);
        $inProgress = false;

        if (is_int($candidateIds) || !is_array($candidateIds) || empty($candidateIds)) {
            return []; // Return empty list of WP_Site objects if anything odd happens.
        }

        // Use cached editor map and sort by groups.
        $map = $this->getEditorMapCached($candidateIds);

        // Stable partition
        $blockIds   = [];
        $classicIds = [];
        foreach ($candidateIds as $id) {
            $type = $map[$id] ?? 'classic';
            if ($type === 'block') {
                $blockIds[] = $id;
            } else {
                $classicIds[] = $id;
            }
        }

        $sortedIds = ($order === 'asc')
            ? array_merge($blockIds, $classicIds)
            : array_merge($classicIds, $blockIds);

        // Apply pagination after sorting
        $sortedPaged = array_slice($sortedIds, $offset, $number);

        // Convert IDs to WP_Site objects.
        $sites = [];
        foreach ($sortedPaged as $id) {
            $site = get_site($id);
            if ($site instanceof \WP_Site) {
                $sites[] = $site;
            }
        }

        return $sites; // Short-circuit: final array of WP_Site objects.
    }
}
