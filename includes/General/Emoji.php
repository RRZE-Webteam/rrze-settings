<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * Emoji class
 *
 * @package RRZE\Settings\General
 */
class Emoji
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options object
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
        // Disables emoji support
        if ($this->siteOptions->general->disable_emoji) {
            $this->disableEmoji();
        }
    }

    /**
     * Disable emoji support
     *
     * @return void
     */
    protected function disableEmoji()
    {
        // Remove emoji support from the front-end and admin
        add_action('wp_print_scripts', [$this, 'dequeueEmojiScript'], 99);

        // Remove emoji support from the front-end
        remove_action('wp_head', 'print_emoji_detection_script', 7);

        // Remove emoji support from the admin
        remove_action('admin_print_styles', 'print_emoji_styles', 20);

        // Remove emoji support from RSS feeds
        remove_action('wp_print_styles', 'print_emoji_styles');

        // Remove emoji support from comments
        remove_filter('comment_text_rss', 'wp_staticize_emoji');

        // Remove emoji support from email
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        // Remove emoji support from TinyMCE
        add_filter('tiny_mce_plugins', [$this, 'removeTinymceEmoji']);
    }

    /**
     * Dequeue emoji script
     *
     * @return void
     */
    public function dequeueEmojiScript()
    {
        wp_dequeue_script('emoji');
    }

    /**
     * Remove TinyMCE emoji plugin
     *
     * @param array $plugins TinyMCE plugins
     * @return array
     */
    public function removeTinymceEmoji($plugins)
    {
        if (!is_array($plugins)) {
            return [];
        }
        return array_diff($plugins, ['wpemoji']);
    }
}
