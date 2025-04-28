<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;

/**
 * RRZE Newsletter class
 * 
 * @link https://github.com/RRZE-Webteam/rrze-newsletter
 * @package RRZE\Settings\Plugins
 */
class Newsletter
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    const PLUGIN = 'rrze-newsletter/rrze-newsletter.php';

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

        if (
            $this->siteOptions->plugins->rrze_newsletter_global_settings
            && !$this->hasException()
        ) {
            add_filter('rrze_newsletter_sender_allowed_domains', function () {
                return $this->siteOptions->plugins->rrze_newsletter_sender_allowed_domains;
            });

            add_filter('rrze_newsletter_hide_section_mail_queue', '__return_true');
            add_filter('rrze_newsletter_mail_queue_send_limit', function () {
                return $this->siteOptions->plugins->rrze_newsletter_mail_queue_send_limit;
            });
            add_filter('rrze_newsletter_mail_queue_max_retries', function () {
                return $this->siteOptions->plugins->rrze_newsletter_mail_queue_max_retries;
            });

            if ($this->siteOptions->plugins->rrze_newsletter_disable_subscription) {
                add_filter('rrze_newsletter_hide_section_subscription', '__return_true');
                add_filter('rrze_newsletter_disable_subscription', '__return_true');
                add_filter('rrze_newsletter_hide_section_mailing_list', '__return_true');
                add_filter('rrze_newsletter_disable_mailing_list', '__return_true');
            }

            add_filter('rrze_newsletter_recipient_allowed_domains', function () {
                return $this->siteOptions->plugins->rrze_newsletter_recipient_allowed_domains;
            });
        }
    }

    /**
     * Check if network-wide the plugin has exceptions
     * 
     * @return bool
     */
    protected function hasException()
    {
        $exceptions = $this->siteOptions->plugins->rrze_newsletter_exceptions;
        if (!empty($exceptions) && is_array($exceptions)) {
            foreach ($exceptions as $row) {
                $aryRow = explode(' - ', $row);
                $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
                if (absint($blogId) == get_current_blog_id()) {
                    return true;
                }
            }
        }
        return false;
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
