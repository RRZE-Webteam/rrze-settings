<?php

namespace RRZE\Settings\Writing\BlockEditor;

defined('ABSPATH') || exit;

class Editor
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
    }

    /**
     * Check if the block editor can be loaded
     * 
     * @param  boolean $canEdit Whether the post can be edited or not
     * @param  object $post (\WP_Post) The post being checked
     * @return boolean
     */
    public function canEdit(bool $canEdit, \WP_Post $post): bool
    {
        if (false === $canEdit) {
            return false;
        }

        if ($this->hasWebsiteException()) {
            return true;
        }

        if ($this->hasThemeException()) {
            return true;
        }

        if ($this->isAllowedPostType($post->ID)) {
            return true;
        }

        return false;
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

    /**
     * Check whether current post type is defined as block-editor-friendly
     *
     * @param integer $postId The post ID
     * @return boolean
     */
    public function isAllowedPostType(int $postId): bool
    {
        $allowedPostTypes = $this->getSupportedPostTypes();

        if (empty($allowedPostTypes)) {
            return false;
        }

        $currentPostType = get_post_type($postId);

        if (false === $currentPostType) {
            return false;
        }

        return isset($allowedPostTypes[$currentPostType]);
    }

    /**
     * Get post types that can be supported by the block editor
     *
     * This will get all registered post types and remove post types:
     *        * that aren't shown in the admin menu
     *        * like attachment, revision, etc.
     *        * that don't support native editor UI
     *
     * Also removes post types that don't support 'show_in_rest':
     *
     * @return array of formatted post types as ['slug' => 'label']
     */
    protected function getSupportedPostTypes(): array
    {
        if (0 === did_action('init') && !doing_action('init')) {
            _doing_it_wrong(__METHOD__, 'getSupportedPostTypes() was called before the init hook. Some post types might not be registered yet.', '1.21.0');
        }

        $postTypes = get_post_types(
            [
                'show_ui' => true,
                'show_in_rest' => true,
            ],
            'object'
        );

        $allowedPostTypes = (array) $this->siteOptions->writing->allowed_post_types;

        $availablePostTypes = [];

        // Remove post types that don't want an editor
        foreach ($postTypes as $name => $postTypeObject) {
            if (
                post_type_supports($name, 'editor')
                && !empty($postTypeObject->label)
                && in_array($name, $allowedPostTypes)
            ) {
                $availablePostTypes[$name] = $postTypeObject->label;
            }
        }

        return $availablePostTypes;
    }
}
