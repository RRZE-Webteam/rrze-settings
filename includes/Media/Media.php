<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use RRZE\Settings\Media\{
    SVG\SVG,
    Replace\Replace
};
use function RRZE\Settings\plugin;

/**
 * Class Media
 *
 * This class handles the media settings in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class Media extends Main
{
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

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);

        // Sanitize filename
        if ($this->siteOptions->media->sanitize_filename) {
            (new Sanitize)->loaded();
        }

        // Custom mime types support
        (new MimeTypes($this->siteOptions))->loaded();

        // Filters the image sizes generated for non-image mime types.
        if ($this->siteOptions->media->filter_nonimages_mimetypes) {
            add_filter('fallback_intermediate_image_sizes', function () {
                return [];
            });
        }

        // Resize images
        if ($this->siteOptions->media->enable_image_resize) {
            (new Resize($this->siteOptions))->loaded();
        }

        // Sharp images
        if ($this->siteOptions->media->enable_sharpen_jpg_images) {
            (new ImageMagick)->loaded();
        }

        // SVG support
        if ($this->siteOptions->media->enable_svg_support) {
            (new SVG)->loaded();
        }

        // File size column
        if ($this->siteOptions->media->enable_filesize_column) {
            (new Columns)->loaded();
        }

        // File replacement
        if ($this->siteOptions->media->enable_file_replace) {
            (new Replace($this->siteOptions))->loaded();
        }
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page hook
     * @return void
     */
    public function adminEnqueueScripts($hook)
    {
        wp_register_style(
            'rrze-media-columns',
            plugins_url('build/media/columns.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        wp_register_style(
            'rrze-media-replace',
            plugins_url('build/media/replace.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        wp_register_style(
            'rrze-media-svg',
            plugins_url('build/media/svg.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        wp_register_style(
            'rrze-media-svg-edit-post',
            plugins_url('build/media/svg-edit-post.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        $assetFile = include(plugin()->getPath('build') . 'media/replace.asset.php');
        wp_register_script(
            'rrze-media-replace',
            plugins_url('build/media/replace.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            plugin()->getVersion(),
            true
        );

        if (in_array($hook, ['upload.php', 'tools_page_media-duplicates'])) {
            wp_enqueue_style('rrze-media-columns');
        }
    }
}
