<?php

namespace RRZE\Settings\Writing\BlockEditor;

defined('ABSPATH') || exit;

use function RRZE\Settings\plugin;

/**
 * Blocks class
 *
 * @package RRZE\Settings\Writing\BlockEditor
 */
class Blocks
{
    /**
     * Site options
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions
     * @return void
     */
    public function __construct(object $siteOptions)
    {
        $this->siteOptions = $siteOptions;

        add_filter('allowed_block_types_all', [$this, 'setAllowedBlockTypes'], 10, 2);

        // Enqueue Block Editor Assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets']);
    }

    /**
     * Set allowed block types
     *
     * @param array $allowedBlockTypes
     * @param object $blockEditorContext
     * @return array
     */
    public function setAllowedBlockTypes($allowedBlockTypes, $blockEditorContext)
    {
        if (empty($blockEditorContext->post)) {
            return $allowedBlockTypes;
        }

        if ($this->hasWebsiteException()) {
            return $allowedBlockTypes;
        }

        if ($this->hasThemeException()) {
            return $allowedBlockTypes;
        }

        $post = $blockEditorContext->post;
        $allowedPostTypes = (array) $this->siteOptions->writing->allowed_post_types;
        $postType = get_post_type($post->ID);
        if ($postType && in_array($postType, $allowedPostTypes)) {
            return $allowedBlockTypes;
        }

        $filteredBlocks = $this->getFilteredBlocks();

        return empty($filteredBlocks) ? $allowedBlockTypes : $filteredBlocks;
    }

    /**
     * Get filtered blocks
     *
     * @return array
     */
    public function getFilteredBlocks()
    {
        // Get the allowed block types
        $allowedBlocks = $this->siteOptions->writing->allowed_block_types;

        // Get the disabled block types
        $disabledBlocks = $this->siteOptions->writing->disabled_block_types;

        // Normalize allowed blocks
        $normalizedAllowedBlocks = $this->normalizeBlocks($allowedBlocks);

        // Filter disabled blocks
        $filteredBlocks = $this->filterBlocks($disabledBlocks, $normalizedAllowedBlocks);

        // Re-index the array (important!)
        $filteredBlocks = array_values($filteredBlocks);

        return $filteredBlocks;
    }

    /**
     * Normalize block types
     *
     * @param array $blocks
     * @return array
     */
    protected function normalizeBlocks($blocks): array
    {
        $normalizedBlocks = [];

        // Get the instance of the block type registry
        $blockRegistry = \WP_Block_Type_Registry::get_instance();

        // Get all registered block types
        $registeredBlocks = $blockRegistry->get_all_registered();

        if (empty($blocks) || !is_array($blocks)) {
            foreach ($registeredBlocks as $registeredBlock) {
                $normalizedBlocks[] = $registeredBlock->name;
            }
            return $normalizedBlocks;
        }

        foreach ($blocks as $block) {
            if (strpos($block, '/*') !== false) {
                // Handle wildcard pattern
                $namespace = rtrim($block, '/*');
                foreach ($registeredBlocks as $registeredBlock) {
                    if (strpos($registeredBlock->name, $namespace . '/') === 0) {
                        $normalizedBlocks[] = $registeredBlock->name;
                    }
                }
            } else {
                // Handle exact match
                foreach ($registeredBlocks as $registeredBlock) {
                    if ($registeredBlock->name === $block) {
                        $normalizedBlocks[] = $block;
                    }
                }
            }
        }

        return array_unique($normalizedBlocks);
    }

    /**
     * Filter blocks
     *
     * @param array $blocks
     * @param array $allowedBlocks
     * @return array
     */
    protected function filterBlocks($blocks, array $allowedBlocks): array
    {
        $filteredBlocks = $allowedBlocks;

        if (empty($blocks) || !is_array($blocks)) {
            return $filteredBlocks;
        }

        foreach ($blocks as $block) {
            if (strpos($block, '/*') !== false) {
                // Handle wildcard pattern
                $namespace = rtrim($block, '/*');
                foreach ($allowedBlocks as $key => $allowedBlock) {
                    if (strpos($allowedBlock, $namespace . '/') === 0) {
                        unset($filteredBlocks[$key]);
                    }
                }
            } else {
                // Handle exact match
                foreach ($allowedBlocks as $key => $allowedBlock) {
                    if ($allowedBlock === $block) {
                        unset($filteredBlocks[$key]);
                    }
                }
            }
        }

        return array_unique($filteredBlocks);
    }

    /**
     * Enqueue block editor assets
     *
     * @return void
     */
    public function enqueueBlockEditorAssets()
    {
        if ($this->hasWebsiteException()) {
            return;
        }

        if ($this->hasThemeException()) {
            return;
        }

        $allowedPostTypes = $this->siteOptions->writing->allowed_post_types;
        if (empty($allowedPostTypes) || !is_array($allowedPostTypes)) {
            return;
        }
        $postType = get_post_type();
        if ($postType && in_array($postType, $allowedPostTypes)) {
            return;
        }

        $screen = get_current_screen();
        $assetFile = include(plugin()->getPath('build') . 'writing/block-editor.asset.php');
        $dependencies = $assetFile['dependencies'] ?? [];
        // Update script dependencies based on current screen.
        if (is_object($screen)) {
            if ($screen === 'site-editor') {
                $dependencies[] = 'wp-edit-site';
            } elseif ($screen->id === 'widgets') {
                $dependencies[] = 'wp-edit-widgets';
            } else {
                $dependencies[] = 'wp-edit-post';
            }
        } else {
            $dependencies[] = 'wp-edit-post';
        }
        $dependencies = array_unique($dependencies);

        // Get the disabled block types
        $disabledBlocks = $this->siteOptions->writing->disabled_block_types ?: [];

        wp_enqueue_script(
            'rrze-settings-writing-block-editor',
            plugins_url('build/writing/block-editor.js', plugin()->getBasename()),
            $dependencies,
            plugin()->getVersion(),
            false
        );

        wp_localize_script(
            'rrze-settings-writing-block-editor',
            'blockEditorLocalize',
            [
                'blocksToHide' => $disabledBlocks,
            ]
        );

        // wp_enqueue_script(
        //     'rrze-settings-writing-block-editor-preferences',
        //     plugins_url('build/writing/block-editor-preferences.js', plugin()->getBasename()),
        //     array('wp-plugins', 'wp-edit-post', 'wp-components', 'wp-element', 'wp-data'),
        //     plugin()->getVersion(),
        //     false
        // );        
    }

    /**
     * Check whether the current website is defined as block-editor-friendly
     * 
     * @return boolean
     */
    public function hasWebsiteException(): bool
    {
        $currentSite = get_current_blog_id();
        $websitesExceptions = (array) $this->siteOptions->writing->websites_exceptions;
        if (in_array($currentSite, array_keys($websitesExceptions))) {
            return true;
        }
        return false;
    }

    /**
     * Check whether the current theme is defined as block-editor-friendly
     * 
     * @return boolean
     */
    public function hasThemeException(): bool
    {
        $theme = wp_get_theme();
        $themeName = $theme->get('Name');
        $themesExceptions = (array) $this->siteOptions->writing->themes_exceptions;
        if (in_array($themeName, $themesExceptions)) {
            return true;
        }
        return false;
    }
}
