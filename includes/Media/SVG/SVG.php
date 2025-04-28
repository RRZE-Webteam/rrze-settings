<?php

namespace RRZE\Settings\Media\SVG;

defined('ABSPATH') || exit;

use RRZE\Settings\Media\SVG\AllowedAttributes;
use RRZE\Settings\Media\SVG\AllowedTags;
use enshrined\svgSanitize\Sanitizer;

/**
 * Class SVG
 *
 * This class handles the SVG media type in WordPress.
 *
 * @package RRZE\Settings\Media\SVG
 */
class SVG
{
    /**
     * Sanitizer instance
     * 
     * @var Sanitizer
     */
    protected $sanitizer;

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        $this->sanitizer = new Sanitizer;
        $this->sanitizer->minify(true);

        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);

        add_filter('wp_prepare_attachment_for_js', [$this, 'prepareAttachment'], 10, 3);

        add_filter('wp_handle_upload_prefilter', [$this, 'uploadPrefilter']);

        add_filter('wp_get_attachment_image_src', [$this, 'attachmentImageSrc'], 10, 4);

        add_action('get_image_tag', [$this, 'imageTagOverride'], 10, 6);

        add_filter('wp_generate_attachment_metadata', [$this, 'generateAttachmentMetadata'], 10, 2);

        add_filter('wp_get_attachment_metadata', [$this, 'attachmentMetadata'], 10, 2);

        add_filter('wp_get_attachment_image_attributes', [$this, 'attachmentImageAttributes'], 10, 3);
    }

    /**
     * Add SVG support to the media library
     *
     * @param array $attr Image attributes
     * @param object $attachment Attachment object
     * @param string $size Image size
     * @return array Modified image attributes
     */
    public function attachmentImageAttributes($attr, $attachment, $size)
    {
        $mime = get_post_mime_type($attachment->ID);
        if ('image/svg+xml' === $mime) {
            $defaultHeight = 100;
            $defaultWidth  = 100;

            $dimensions = $this->getDimensions(get_attached_file($attachment->ID));

            if ($dimensions) {
                $defaultHeight = $dimensions['height'];
                $defaultWidth  = $dimensions['width'];
            }

            $attr['height'] = $defaultHeight;
            $attr['width']  = $defaultWidth;
        }

        return $attr;
    }

    /**
     * Update attachment metadata
     *
     * @param array $data Metadata data
     * @param int $postId Post ID
     * @return array Updated metadata data
     */
    public function attachmentMetadata($data, $postId)
    {
        if (is_wp_error($data)) {
            $data = wp_generate_attachment_metadata($postId, get_attached_file($postId));
            wp_update_attachment_metadata($postId, $data);
        }

        return $data;
    }

    /**
     * Generate attachment metadata
     *
     * @param array $metadata Metadata data
     * @param int $attachmentId Attachment ID
     * @return array Updated metadata data
     */
    public function generateAttachmentMetadata($metadata, $attachmentId)
    {
        if ('image/svg+xml' === get_post_mime_type($attachmentId)) {
            // @todo
        }

        return $metadata;
    }

    /**
     * Override the image tag for SVG images
     *
     * @param string $html Image HTML
     * @param int $id Attachment ID
     * @param string $alt Alt text
     * @param string $title Title text
     * @param string $align Alignment
     * @param string|array $size Image size
     * @return string Modified image HTML
     */
    public function imageTagOverride($html, $id, $alt, $title, $align, $size)
    {
        $mime = get_post_mime_type($id);

        if ('image/svg+xml' === $mime) {
            if (is_array($size)) {
                $width  = $size[0];
                $height = $size[1];
            } elseif ('full' == $size && $dimensions = $this->getDimensions(get_attached_file($id))) {
                $width  = $dimensions['width'];
                $height = $dimensions['height'];
            } else {
                $width  = get_option("{$size}_size_w", false);
                $height = get_option("{$size}_size_h", false);
            }

            if ($height && $width) {
                $html = str_replace('width="1" ', sprintf('width="%s" ', $width), $html);
                $html = str_replace('height="1" ', sprintf('height="%s" ', $height), $html);
            } else {
                $html = str_replace('width="1" ', '', $html);
                $html = str_replace('height="1" ', '', $html);
            }

            $html = str_replace('/>', ' role="img" />', $html);
        }

        return $html;
    }

    /**
     * Add SVG support to the image src
     *
     * @param array $image Image data
     * @param int $attachmentId Attachment ID
     * @param string $size Image size
     * @param bool $icon Icon flag
     * @return array Modified image data
     */
    public function attachmentImageSrc($image, $attachmentId, $size, $icon)
    {
        if (get_post_mime_type($attachmentId) == 'image/svg+xml') {
            $image['1'] = false;
            $image['2'] = false;
        }

        return $image;
    }

    /**
     * Sanitize SVG files before upload
     *
     * @param array $file File data
     * @return array Modified file data
     */
    public function uploadPrefilter($file)
    {
        if ($file['type'] === 'image/svg+xml') {
            if (!$this->sanitize($file['tmp_name'])) {
                $file['error'] = __("This file couldn't be sanitized so for security reasons wasn't uploaded.", 'rrze-media');
            }
        }

        return $file;
    }

    /**
     * Sanitize the SVG file
     * 
     * Sanitizes the SVG file by removing any potentially XML content.
     *
     * @param string $file File path
     * @return bool True on success, false on failure
     */
    protected function sanitize($file)
    {
        $content = file_get_contents($file);

        if ($isGzCompressed = $this->isGzCompressed($content)) {
            $content = gzdecode($content);

            if ($content === false) {
                return false;
            }
        }

        $this->sanitizer->setAllowedAttrs(new AllowedAttributes);
        $this->sanitizer->setAllowedTags(new AllowedTags);

        $sanitizedContent = $this->sanitizer->sanitize($content);

        if ($sanitizedContent === false) {
            return false;
        }

        if ($isGzCompressed) {
            $sanitizedContent = gzencode($sanitizedContent);
        }

        file_put_contents($file, $sanitizedContent);

        return true;
    }

    /**
     * Check if the content is gzip compressed
     *
     * @param string $content Content to check
     * @return bool True if is gzip compressed, false otherwise
     */
    protected function isGzCompressed($content)
    {
        return 0 === mb_strpos($content, "\x1f" . "\x8b" . "\x08");
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @return void
     */
    public function adminEnqueueScripts()
    {
        if ($this->isAllowedScreen()) {
            wp_enqueue_style('rrze-media-svg');
        }

        if ($this->isEditPage()) {
            wp_enqueue_style('rrze-media-svg-edit-post');
        }
    }

    /**
     * Prepare the attachment for display
     *
     * @param array $response Attachment response
     * @param object $attachment Attachment object
     * @param array $meta Metadata
     * @return array Modified response
     */
    public function prepareAttachment($response, $attachment, $meta)
    {
        if ($response['mime'] == 'image/svg+xml') {
            $possibleSizes = apply_filters('image_size_names_choose', [
                'full'      => __('Full Size'),
                'thumbnail' => __('Thumbnail'),
                'medium'    => __('Medium'),
                'large'     => __('Large'),
            ]);

            $sizes = array();

            foreach ($possibleSizes as $size => $label) {
                $defaultHeight = 2000;
                $defaultWidth = 2000;

                if ('full' === $size) {
                    $dimensions = $this->getDimensions(get_attached_file($attachment->ID));

                    if ($dimensions) {
                        $defaultHeight = $dimensions['height'];
                        $defaultWidth  = $dimensions['width'];
                    }
                }

                $sizes[$size] = [
                    'height' => get_option("{$size}_size_w", $defaultHeight),
                    'width' => get_option("{$size}_size_h", $defaultWidth),
                    'url' => $response['url'],
                    'orientation' => 'portrait',
                ];
            }

            $response['sizes'] = $sizes;
            $response['icon']  = $response['url'];
        }

        return $response;
    }

    /**
     * Get the dimensions of the SVG file
     *
     * @param string $svgPath Path to the SVG file
     * @return array|bool Dimensions array or false on failure
     */
    protected function getDimensions($svgPath)
    {
        $svg = simplexml_load_file($svgPath);
        $width = 0;
        $height = 0;

        if ($svg) {
            $attributes = $svg->attributes();
            if (isset($attributes->width, $attributes->height)) {
                $width  = floatval($attributes->width);
                $height = floatval($attributes->height);
            } elseif (isset($attributes->viewBox)) {
                $sizes = explode(' ', $attributes->viewBox);
                if (isset($sizes[2], $sizes[3])) {
                    $width  = floatval($sizes[2]);
                    $height = floatval($sizes[3]);
                }
            } else {
                return false;
            }
        }

        return array('width' => $width, 'height' => $height);
    }

    /**
     * Check if the current screen is allowed
     *
     * @return bool True if allowed, false otherwise
     */
    protected function isAllowedScreen()
    {
        $allowed_screens = apply_filters('rrze_media_svg_allowed_screens', ['upload']);
        $screen = get_current_screen();

        return is_object($screen) && in_array($screen->id, $allowed_screens);
    }

    /**
     * Check if the current page is an edit or new page
     *
     * @param string|null $newEdit Edit or new page
     * @return bool True if edit or new page, false otherwise
     */
    protected function isEditPage($newEdit = null)
    {
        global $pagenow;

        if (!is_admin()) {
            return false;
        }

        if ($newEdit == 'edit') {
            return in_array($pagenow, ['post.php']);
        } elseif ($newEdit == "new") {
            return in_array($pagenow, ['post-new.php']);
        } else {
            return in_array($pagenow, ['post.php', 'post-new.php']);
        }
    }
}
