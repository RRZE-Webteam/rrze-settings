<?php

namespace RRZE\Settings\Posts;

defined('ABSPATH') || exit;

/**
 * Posts class
 * 
 * This class handles the posts list table in the admin area.
 * It adds a custom column for the last modified date and makes it sortable.
 * 
 * @package RRZE\Settings\Posts
 */
class Post
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
     * @param object $siteOptions Site options
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
        // Last modified custom column
        if ($this->siteOptions->posts->last_modified_custom_column) {
            $postTypes = ['post', 'page'];
            foreach ($postTypes as $postType) {
                add_filter("manage_edit-{$postType}_columns", [$this, 'lastModifiedColumn']);
                add_action("manage_{$postType}_posts_custom_column", [$this, 'lastModifiedPostsCustomColumn'], 10, 2);
                add_filter("manage_edit-{$postType}_sortable_columns", [$this, 'lastModifiedSortableColumn']);
            }
            add_filter('admin_init', [$this, 'adminInit']);
        }
    }

    /**
     * Load admin init
     * 
     * @return void
     */
    public function adminInit()
    {
        add_filter('request', [$this, 'sortColumnByModified']);
    }

    /**
     * Add last modified column to the posts list table
     * 
     * @param array $columns
     * @return array
     */
    public function lastModifiedColumn($columns)
    {
        global $post_status;

        if (in_array($post_status, ['trash'])) {
            return $columns;
        }

        $newColumns = [];

        foreach ($columns as $key => $value) {
            $newColumns[$key] = $value;

            if ($key == 'date') {
                $newColumns['last-modified'] = __('Date');
            }

            if ($key == 'date') {
                unset($newColumns[$key]);
            }
        }

        return $newColumns;
    }

    /**
     * Add last modified date to the posts list table
     * 
     * @param string $column
     * @param int $postId
     * @return void
     */
    public function lastModifiedPostsCustomColumn($column, $postId)
    {
        if ($column == 'last-modified') {
            $post = get_post($postId);
            echo $this->column_date($post);
        }
    }

    /**
     * Add last modified sortable column to the posts list table
     * @param array $columns
     * @return array
     */
    public function lastModifiedSortableColumn($columns)
    {
        $columns['last-modified'] = 'last-modified';

        return $columns;
    }

    /**
     * Add last modified date to the posts list table
     * 
     * @param object $post
     * @return string
     */
    protected function column_date($post)
    {
        global $mode;

        if ('0000-00-00 00:00:00' === $post->post_date) {
            $postTime = $postHumanTime = __('Unpublished');
            $timeDiff = 0;
        } else {
            $postTime = get_the_time(__('Y/m/d g:i:s a'));
            $postModifiedTime = $post->post_date;
            $time = get_post_time('G', true, $post);

            $timeDiff = time() - $time;

            if ($timeDiff > 0 && $timeDiff < DAY_IN_SECONDS) {
                $postHumanTime = sprintf(__('%s ago'), human_time_diff($time));
            } else {
                $postHumanTime = mysql2date(__('Y/m/d'), $postModifiedTime);
            }
        }

        $output = '';

        if ('publish' === $post->post_status) {
            $output .= __('Published');
        } elseif ('future' === $post->post_status) {
            if ($timeDiff > 0) {
                $output .= '<strong class="error-message">' . __('Missed schedule') . '</strong>';
            } else {
                $output .= __('Scheduled');
            }
        } else {
            $output .= __('Last Modified');
        }
        $output .= '<br>';
        if ('excerpt' === $mode) {
            $output .= apply_filters('post_date_column_time', $postTime, $post, 'date', $mode);
        } else {
            if (in_array($post->post_status, ['publish', 'future'])) {
                $output .= '<abbr title="' . $postTime . '">' . apply_filters('post_date_column_time', $postHumanTime, $post, 'date', $mode) . '</abbr>';
                $output .= '<br>' . __('Last Modified') . '<br>';
            }

            $output .=  $this->lastModified($post);
        }

        return $output;
    }

    /**
     * Add last modified date to the posts list table
     * 
     * @param object $post
     * @return string
     */
    protected function lastModified($post)
    {
        $postTime = date(__('Y/m/d g:i:s a'), get_post_modified_time('U', true, $post));
        $postModifiedTime = $post->post_modified;
        $time = get_post_modified_time('G', true, $post);

        $timeDiff = time() - $time;

        if ($timeDiff > 0 && $timeDiff < DAY_IN_SECONDS) {
            $postHumanTime = sprintf(__('%s ago'), human_time_diff($time));
        } else {
            $postHumanTime = mysql2date(__('Y/m/d'), $postModifiedTime);
        }

        return '<abbr title="' . $postTime . '">' . $postHumanTime . '</abbr>';
    }

    /**
     * Add filter to the pages list table
     * 
     * @return void
     */
    public function sortColumnByModified($vars)
    {
        $screen = get_current_screen();

        $postTypes = ['post', 'page'];
        $postType = $screen->post_type;

        if (!in_array($postType, $postTypes)) {
            return $vars;
        }

        if (isset($vars['orderby']) && 'last-modified' == $vars['orderby']) {
            $vars = array_merge(
                $vars,
                ['orderby' => 'modified']
            );
        }

        return $vars;
    }
}
