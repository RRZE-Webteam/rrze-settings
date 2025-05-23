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
     * Manage websites sortable columns
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
}
