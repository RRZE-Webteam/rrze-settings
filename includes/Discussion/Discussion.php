<?php

namespace RRZE\Settings\Discussion;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Class Discussion
 *
 * This class handles the discussion settings in WordPress.
 *
 * @package RRZE\Settings\Discussion
 */
class Discussion extends Main
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

        if ($this->siteOptions->discussion->default_settings) {
            // Do not attempt to notify any blogs linked to from the post
            update_option('default_pingback_flag', 0);

            // Do not allow link notifications from other blogs (pingbacks and trackbacks) on new posts
            update_option('default_ping_status', 0);

            // Users must be registered and logged in to comment (Signup has been disabled. Only members of this site can comment.)
            update_option('comment_registration', 1);

            // Disable comments for new posts/pages by default.
            add_filter('wp_insert_post_data', [$this, 'disableCommentsByDefault'], 10, 2);

            // Allow comments only for logged-in users.
            add_filter('comments_open', [$this, 'allowCommentsLoggedInOnly'], 10, 2);

            // Fires when a new site's initialization routine should be executed.
            add_action('wp_initialize_site', [$this, 'setDefaultSettingsForNewSite']);
        }

        if ($this->siteOptions->discussion->disable_avatars) {
            // Hide the avatar settings on the Discussion Settings page
            add_action('admin_footer', [$this, 'hideAvatarSettings']);

            // Disable show avatars
            $this->disableShowAvatars();

            // Set the default avatar to 'mystery'
            $this->setDefaultAvatar();
        }
    }

    /**
     * Disable comments by default for new posts/pages
     *
     * @param array $data Post data
     * @param array $postarr Post array
     * @return array Modified post data
     */
    public function disableCommentsByDefault($data, $postarr)
    {
        // If the post type is a post/page, disable comments by default
        if (in_array($data['post_type'], ['post', 'page'])) {
            $data['comment_status'] = 'closed';
        }

        return $data;
    }

    /**
     * Allow comments only for logged-in users
     *
     * @param bool $open Current comment status
     * @param int $postId Post ID
     * @return bool Modified comment status
     */
    public function allowCommentsLoggedInOnly($open, $postId)
    {
        if (is_user_logged_in()) {
            return $open; // Keep comment status as is.
        }
        return false; // Disable comments for non-logged-in users.
    }

    /**
     * Set default settings for new websites
     *
     * @param int|WP_Site $blogId Blog ID or WP_Site object
     * @return void
     */
    public function setDefaultSettingsForNewSite($blogId)
    {
        if (is_a($blogId, 'WP_Site')) {
            $blogId = $blogId->blog_id;
        }

        // Switch to the new site
        switch_to_blog($blogId);

        // Do not attempt to notify any blogs linked to from the post
        update_option('default_pingback_flag', 0);
        // Do not allow link notifications from other blogs (pingbacks and trackbacks) on new posts
        update_option('default_ping_status', 0);
        // Do not allow people to submit comments on new posts
        update_option('default_comment_status', 0);
        // Users must be registered and logged in to comment (Signup has been disabled. Only members of this site can comment.)
        update_option('comment_registration', 1);

        // Restore the original site
        restore_current_blog();
    }

    /**
     * Hide the avatar settings on the Discussion Settings page
     *
     * @return void
     */
    public function hideAvatarSettings()
    {
        // Only load this script on the Discussion Settings page
        $screen = get_current_screen();
        if ($screen && $screen->base === 'options-discussion') {
            // Enqueue the script to hide avatar settings
            $assetFile = include(plugin()->getPath('build') . 'discussion/avatars.asset.php');
            wp_enqueue_script(
                'rrze-settings-discussion-avatars',
                plugins_url('build/discussion/avatars.js', plugin()->getBasename()),
                $assetFile['dependencies'] ?? [],
                $assetFile['version'] ?? plugin()->getVersion(),
                true
            );
        }
    }

    /**
     * Disable show avatars
     *
     * @return void
     */
    public function disableShowAvatars()
    {
        // Set the 'show_avatars' option to 0
        update_option('show_avatars', 0);
    }

    /**
     * Set the default avatar to 'mystery'
     *
     * @return void
     */
    public function setDefaultAvatar()
    {
        // Set the 'avatar_default' option to 'mystery'
        update_option('avatar_default', 'mystery');
    }
}
