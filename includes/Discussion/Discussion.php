<?php

namespace RRZE\Settings\Discussion;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Handles hardening/streamlining of WordPress Discussion settings.
 * 
 * @package RRZE\Settings\Discussion
 */
class Discussion extends Main
{
    /**
     * Bootstrap plugin feature.
     */
    public function loaded(): void
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        $discussion = $this->siteOptions->discussion ?? null;

        if (!empty($discussion) && !empty($discussion->default_settings)) {
            // Apply discussion defaults only when a new site is created (multisite).
            add_action('wp_initialize_site', [$this, 'setDefaultSettingsForNewSite'], 10, 2);

            // Close comments by default only when creating new posts/pages.
            add_filter('wp_insert_post_data', [$this, 'disableCommentsByDefault'], 10, 2);

            // Only logged-in users can comment.
            add_filter('comments_open', [$this, 'allowCommentsLoggedInOnly'], 10, 2);
        }

        if (!empty($discussion) && !empty($discussion->disable_avatars)) {
            // Hide avatar settings only on the Discussion settings screen.
            add_action('admin_enqueue_scripts', [$this, 'enqueueHideAvatarSettings']);

            // Disable avatars globally.
            if (get_option('show_avatars') !== '0') {
                update_option('show_avatars', 0);
            }

            // Set default avatar to 'mystery'.
            if (get_option('avatar_default') !== 'mystery') {
                update_option('avatar_default', 'mystery');
            }
        }
    }

    /**
     * Close comments by default ONLY when creating new posts/pages.
     *
     * @param array<string,mixed> $data    Sanitized post data to be inserted/updated.
     * @param array<string,mixed> $postarr Raw post array (may include 'ID' when updating).
     * @return array<string,mixed>
     */
    public function disableCommentsByDefault(array $data, array $postarr): array
    {
        $is_new = empty($postarr['ID']);

        if (
            $is_new
            && !empty($data['post_type'])
            && post_type_supports((string) $data['post_type'], 'comments')
            && in_array((string) $data['post_type'], ['post', 'page'], true)
        ) {
            // Respect an explicit editor choice if provided in $postarr.
            if (!isset($postarr['comment_status'])) {
                $data['comment_status'] = 'closed';
            }
        }

        return $data;
    }

    /**
     * Allow comments only for logged-in users.
     *
     * @param bool $open    Whether the comments are open.
     * @param int  $postId  Post ID.
     * @return bool
     */
    public function allowCommentsLoggedInOnly(bool $open, int $postId): bool
    {
        return is_user_logged_in() ? $open : false;
    }

    /**
     * Apply discussion defaults only when a new site is created (multisite).
     *
     * @param WP_Site $site The site being initialized.
     * @param array    $args  Array of arguments for the new site.
     * @return void
     */
    public function setDefaultSettingsForNewSite(\WP_Site $site, array $args = []): void
    {
        switch_to_blog((int) $site->blog_id);

        update_option('default_pingback_flag', 0);
        update_option('default_ping_status', 0);
        update_option('default_comment_status', 'closed');
        update_option('comment_registration', 1);

        restore_current_blog();
    }

    /**
     * Enqueue a small admin script to hide avatar UI on Discussion settings.
     *
     * @param string $hookSuffix Current admin page hook.
     * @return void
     */
    public function enqueueHideAvatarSettings(string $hookSuffix): void
    {
        if ($hookSuffix !== 'options-discussion.php') {
            return;
        }

        $buildDir  = trailingslashit(plugin()->getPath('build')) . 'discussion/';
        $assetPath = $buildDir . 'avatars.asset.php';
        $scriptUrl = plugins_url('build/discussion/avatars.js', plugin()->getBasename());

        $deps = [];
        $ver  = plugin()->getVersion();

        if (file_exists($assetPath)) {
            /** @var array{dependencies?:string[],version?:string} $asset */
            $asset = include $assetPath;
            $deps  = $asset['dependencies'] ?? [];
            $ver   = $asset['version'] ?? $ver;
        }

        wp_enqueue_script('rrze-settings-discussion-avatars', $scriptUrl, $deps, $ver, true);
    }
}
