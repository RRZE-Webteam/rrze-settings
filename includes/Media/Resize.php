<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

use WP_Error;

/**
 * Class Resize
 *
 * This class handles the resizing of images in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class Resize
{
    /**
     * Site options
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Image metadata
     * 
     * @var array
     */
    protected $imageMeta = [];

    /**
     * Constructor
     *
     * @param object $siteOptions Site options
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        add_filter('wp_handle_upload', [$this, 'handleUpload']);
        add_filter('wp_update_attachment_metadata', [$this, 'updateAttachmentMetadata'], 10, 2);
    }

    /**
     * Handle the upload of images
     *
     * @param array $params Upload parameters
     * @return array|WP_Error Modified upload parameters or error
     */
    public function handleUpload($params)
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $maxWidthHeight = absint($this->siteOptions->media->max_width_height);
        if ($maxWidthHeight < 1024) {
            return $params;
        }

        if (is_wp_error($params)) {
            return $params;
        }

        if (strpos($params['file'], 'noresize') !== false) {
            return $params;
        }

        $uploadPath = $params['file'];

        if (!file_exists($uploadPath) || !in_array($params['type'], ['image/png', 'image/gif', 'image/jpeg'])) {
            return $params;
        }

        $imagesize = @getimagesize($uploadPath);
        if (!$imagesize) {
            return $params;
        }

        list($imageWidth, $imageHeight) = $imagesize;

        $maxWidth = $maxHeight = $maxWidthHeight;

        if (($imageWidth > $maxWidth && $maxWidth > 0) || ($imageHeight > $maxHeight && $maxHeight > 0)) {
            list($newWidth, $newHeight) = wp_constrain_dimensions($imageWidth, $imageHeight, $maxWidth, $maxHeight);

            $resizedImage = $this->imageResize($uploadPath, $newWidth, $newHeight);

            if (!is_wp_error($resizedImage)) {
                $newPath = $resizedImage;

                if (filesize($newPath) < filesize($uploadPath)) {
                    unlink($uploadPath);
                    rename($newPath, $uploadPath);
                } else {
                    unlink($newPath);
                }
            } else {
                unlink($uploadPath);

                $params = [
                    'error' => sprintf(
                        __('An error has occurred: %s', 'rrze-media'),
                        $resizedImage->get_error_message()
                    )
                ];
            }
        }

        return $params;
    }

    /**
     * Handle the replacement of images
     *
     * @param array $params Replacement parameters
     * @return void|WP_Error
     */
    public function handleReplace($params)
    {
        $maxWidthHeight = absint($this->siteOptions->media->max_width_height);
        if ($maxWidthHeight < 1024) {
            return;
        }

        if (strpos($params['file'], 'noresize') !== false) {
            return;
        }

        $uploadPath = $params['file'];

        if (!file_exists($uploadPath) || !in_array($params['type'], ['image/png', 'image/gif', 'image/jpeg'])) {
            return;
        }

        $imagesize = @getimagesize($uploadPath);
        if (!$imagesize) {
            return;
        }

        list($imageWidth, $imageHeight) = $imagesize;

        $maxWidth = $maxHeight = $maxWidthHeight;

        if (($imageWidth > $maxWidth && $maxWidth > 0) || ($imageHeight > $maxHeight && $maxHeight > 0)) {
            list($newWidth, $newHeight) = wp_constrain_dimensions($imageWidth, $imageHeight, $maxWidth, $maxHeight);

            $resizedImage = $this->imageResize($uploadPath, $newWidth, $newHeight);

            if (is_wp_error($resizedImage)) {
                return new WP_Error('image-resize', sprintf(__('An error has occurred: %s', 'rrze-media'), $resizedImage->get_error_message()));
            }

            $newPath = $resizedImage;

            if (filesize($newPath) < filesize($uploadPath)) {
                unlink($uploadPath);
                rename($newPath, $uploadPath);
            } else {
                unlink($newPath);
            }
        }
    }

    /**
     * Update the attachment metadata
     *
     * @param array $data Attachment metadata
     * @param int $postId Post ID
     * @return array Modified attachment metadata
     */
    public function updateAttachmentMetadata($data, $postId)
    {
        if (!wp_attachment_is_image($postId)) {
            return $data;
        }

        if (empty($this->imageMeta)) {
            return $data;
        }

        if (!empty($this->imageMeta['caption'])) {
            $attachment = array(
                'ID' => $postId,
                'post_content' => $this->imageMeta['caption']
            );

            wp_update_post($attachment);
        }

        $data['image_meta'] = $this->imageMeta;

        return $data;
    }

    /**
     * Resize the image
     *
     * @param string $file File path
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @param bool $crop Crop flag
     * @param string|null $suffix Suffix for the resized image
     * @param string|null $destPath Destination path for the resized image
     * @param int $jpegQuality JPEG quality
     * @return string|WP_Error Resized image file path or error
     */
    protected function imageResize($file, $maxWidth, $maxHeight, $crop = false, $suffix = null, $destPath = null, $jpegQuality = 90)
    {
        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor)) {
            return $editor;
        }

        $this->imageMeta = wp_read_image_metadata($file);
        if (!is_array($this->imageMeta)) {
            return $editor;
        }

        $orientation = array_key_exists('orientation', $this->imageMeta) ? $this->imageMeta['orientation'] : 0;
        switch ($orientation) {
            case 3:
                $editor->rotate(180);
                break;
            case 6:
                $editor->rotate(-90);
                break;
            case 8:
                $editor->rotate(90);
                break;
        }

        $editor->set_quality($jpegQuality);

        $resized = $editor->resize($maxWidth, $maxHeight, $crop);
        if (is_wp_error($resized)) {
            return $resized;
        }

        $destFile = $editor->generate_filename($suffix, $destPath);

        while (file_exists($destFile)) {
            $destFile = $editor->generate_filename('TMP', $destPath);
        }

        $saved = $editor->save($destFile);

        if (is_wp_error($saved)) {
            return $saved;
        }

        return $destFile;
    }
}
