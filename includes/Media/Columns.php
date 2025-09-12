<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

/**
 * Class Columns
 *
 * This class handles the addition of a file size column to the media library in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class Columns
{
    /**
     * Meta key for file size
     * 
     * @var string
     */
    const metaFileSize = 'filesize';

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        // Add a file size column
        add_action('added_post_meta', [$this, 'addMediaMetadata'], 10, 3);
        add_filter('manage_media_columns', [$this, 'mediaColumnsFilesize']);
        add_action('manage_media_custom_column', [$this, 'mediaCustomColumnFilesize'], 10, 2);
        add_filter('manage_upload_sortable_columns', [$this, 'sortableColumns']);
        add_filter('manage_tools_page_media-duplicates_sortable_columns', [$this, 'sortableColumns']);
        add_action('pre_get_posts', [$this, 'preGetPosts']);
    }

    /**
     * Add file size metadata to media items
     *
     * @param int $metaId Meta ID
     * @param int $postId Post ID
     * @param string $metaKey Meta key
     * @return void
     */
    public function addMediaMetadata($metaId, $postId, $metaKey)
    {
        if ('_wp_attached_file' == $metaKey) {
            $attachedFile = get_attached_file($postId);
            if (is_readable($attachedFile)) {
                $fileSize = $this->fileSize($attachedFile);
                $this->updateMediaMeta($postId, self::metaFileSize, $fileSize);
            }
        }
    }

    /**
     * Add a file size column to the media library
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function mediaColumnsFilesize($columns)
    {
        $position = array_search('date', array_keys($columns));
        $columns = array_slice($columns, 0, $position, true) + [self::metaFileSize => ''] + array_slice($columns, $position, count($columns) - $position, true);
        $columns[self::metaFileSize] = __('Size', 'rrze-settings');
        return $columns;
    }

    /**
     * Display the file size in the custom column
     *
     * @param string $columnName Column name
     * @param int $postId Post ID
     * @return void
     */
    public function mediaCustomColumnFilesize($columnName, $postId)
    {
        if (self::metaFileSize !== $columnName) {
            return;
        }

        if (!$fileSize = get_post_meta($postId, self::metaFileSize, true)) {
            $attachedFile = get_attached_file($postId);
            if (!is_readable($attachedFile)) {
                $fileSize = 0;
            } else {
                $fileSize = $this->fileSize($attachedFile);
                $this->updateMediaMeta($postId, self::metaFileSize, $fileSize);
            }
        }

        echo size_format($fileSize, 0);
    }

    /**
     * Make the file size column sortable
     *
     * @param array $columns Existing sortable columns
     * @return array Modified sortable columns
     */
    public function sortableColumns($columns)
    {
        $columns[self::metaFileSize] = self::metaFileSize;
        return $columns;
    }

    /**
     * Modify the query to sort by file size
     *
     * @param \WP_Query $query The current query
     * @return void
     */
    public function preGetPosts($query)
    {
        global $pagenow;

        $pt = (array) $query->get('post_type');
        if (in_array('customize_changeset', $pt, true)) {
            return;
        }

        if (is_admin() && $query->is_main_query() && in_array($pagenow, ['upload.php', 'tools.php'], true)) {
            $orderby = $query->get('orderby');
            if (! is_array($orderby)) {
                $orderby = [$orderby => $query->get('order')];
            }

            if (array_key_exists(self::metaFileSize, $orderby)) {
                $metaQuery = $query->get('meta_query') ?: [];
                $metaQuery[self::metaFileSize] = [
                    'key'     => self::metaFileSize,
                    'type'    => 'NUMERIC',
                    'compare' => 'EXISTS',
                ];
                $query->set('meta_query', $metaQuery);
            }
        }
    }

    /**
     * Get the file size of a file
     *
     * @param string $file File path
     * @return int File size in bytes
     */
    protected function fileSize($file)
    {
        return filesize($file);
    }

    /**
     * Update the media meta data
     *
     * @param int $postId Post ID
     * @param string $metaKey Meta key
     * @param mixed $value Meta value
     * @return bool True on success, false on failure
     */
    protected function updateMediaMeta($postId, $metaKey, $value)
    {
        return update_post_meta($postId, $metaKey, $value);
    }
}
