<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;

/**
 * Contact Form 7 class
 * 
 * @link https://wordpress.org/plugins/contact-form-7
 * @package RRZE\Settings\Plugins
 */
class CF7
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'contact-form-7/wp-contact-form-7.php';

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
        if (!$this->pluginExists(self::PLUGIN) || !$this->isPluginActive(self::PLUGIN)) {
            return;
        }

        // Remove recaptcha scripts
        remove_action(
            'wp_enqueue_scripts',
            'wpcf7_recaptcha_enqueue_scripts',
            20
        );

        // Dequeue CF7 scripts
        if ($this->siteOptions->plugins->cf7_dequeue) {
            add_action('wp_enqueue_scripts', [$this, 'dequeueScripts'], 99);
        }
    }

    /**
     * Dequeue CF7 scripts on the pages where it is not needed.
     * 
     * @return void
     */
    public function dequeueScripts()
    {
        $loadScripts = false;
        if (is_singular()) {
            $post = get_post();
            if (!empty($post->post_content) && has_shortcode($post->post_content, 'contact-form-7')) {
                $loadScripts = true;
            }
        }
        if (!$loadScripts) {
            wp_dequeue_script('contact-form-7');
            wp_dequeue_style('contact-form-7');
        }
    }

    /**
     * Check if plugin is available
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is available
     */
    protected function pluginExists($plugin)
    {
        return Helper::pluginExists($plugin);
    }

    /**
     * Check if plugin is active
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is active
     */
    protected function isPluginActive($plugin)
    {
        return Helper::isPluginActive($plugin);
    }
}
