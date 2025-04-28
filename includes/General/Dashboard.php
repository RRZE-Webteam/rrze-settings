<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use RRZE\Settings\Feed;

/**
 * Dashboard class
 *
 * @package RRZE\Settings\General
 */
class Dashboard
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
        // Disables the welcome panel that introduces users to WordPress.
        if ($this->siteOptions->general->disable_welcome_panel) {
            remove_action('welcome_panel', 'wp_welcome_panel');
        }

        if ($this->siteOptions->general->white_label) {
            add_action('wp_dashboard_setup', [$this, 'removeDashboardPrimary']);
            add_action('wp_network_dashboard_setup', [$this, 'removeNetworkDashboardPrimary']);

            add_action('wp_dashboard_setup', [$this, 'addCustomDashboard']);
            add_action('wp_network_dashboard_setup', [$this, 'addNetworkCustomDashboard']);

            // Dashboard Widget 'At a Glance'.
            add_action('dashboard_glance_items', [$this, 'dashboardGlanceItems']);
        }

        // Disables the admin email verification check.
        if ($this->siteOptions->general->disable_admin_email_verification) {
            add_filter('admin_email_check_interval', '__return_false');
        }
    }

    /**
     * Removes the dashboard primary widget
     *
     * @return void
     */
    public function removeDashboardPrimary()
    {
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
    }

    /**
     * Removes the network dashboard primary widget
     *
     * @return void
     */
    public function removeNetworkDashboardPrimary()
    {
        remove_meta_box('dashboard_primary', 'dashboard-network', 'side');
    }

    /**
     * Adds custom dashboard widgets
     *
     * @return void
     */
    public function addCustomDashboard()
    {
        wp_add_dashboard_widget(
            'dashboard_custom_primary',
            __('RRZE Maintenance and fault messages', 'rrze-settings'),
            [$this, 'customDashboardPrimary']
        );

        wp_add_dashboard_widget(
            'dashboard_custom_secondary',
            __('Webworking News', 'rrze-settings'),
            [$this, 'customDashboardSecondary']
        );
    }

    /**
     * Adds custom network dashboard widgets
     *
     * @return void
     */
    public function addNetworkCustomDashboard()
    {
        wp_add_dashboard_widget(
            'dashboard_custom_primary',
            __('RRZE Maintenance and fault messages', 'rrze-settings'),
            [$this, 'customDashboardPrimary']
        );
    }

    /**
     * Custom dashboard primary widget output
     *
     * @return void
     */
    public function customDashboardPrimary()
    {
        echo '<div class="rss-widget">';
        $this->feedOutput('https://www.rrze.fau.de/category/wartungsmeldung/feed/', 10, [
            'items' => 8,
            'show_summary' => 0,
            'show_date' => 0
        ]);
        echo "</div>";
    }

    /**
     * Custom network dashboard primary widget output
     *
     * @return void
     */
    public function customDashboardSecondary()
    {
        echo '<div class="rss-widget">';
        $this->feedOutput('https://blogs.fau.de/webworking/feed/', 180, [
            'items' => 4,
            'show_summary' => 0,
            'show_date' => 0
        ]);
        echo "</div>";
    }

    /**
     * Adds custom dashboard glance items
     *
     * @param array $elements Dashboard glance items
     * @return array Modified dashboard glance items
     */
    public function dashboardGlanceItems($elements)
    {
        $postTypes = array_merge($this->buildinPostTypes(), $this->customPostTypes());

        foreach ($postTypes as $postType) {
            if (in_array($postType->name, ['post', 'page'])) {
                continue;
            }

            $countPosts = wp_count_posts($postType->name);
            $count = ($postType->name != 'attachment') ? number_format_i18n($countPosts->publish) : number_format_i18n($this->countAttachments());

            $label = _n($postType->labels->singular_name, $postType->labels->name, absint($count));

            if (
                $postType->name != 'attachment' && current_user_can($postType->cap->edit_posts)
                || ($postType->name == 'attachment' && current_user_can($postType->cap->create_posts))
            ) {
                $elements[] = sprintf('<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s %3$s</a>', $postType->name, $count, $label);
            } else {
                $elements[] = sprintf('<span class="%1$s-count">%2$s %3$s</span>', $postType->name, $count, $label);
            }
        }

        return $elements;
    }

    /**
     * Counts the number of attachments
     *
     * @return int Number of attachments
     */
    protected function countAttachments()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash'");
    }

    /**
     * Returns the built-in post types
     *
     * @return array Built-in post types
     */
    protected function buildinPostTypes()
    {
        $args = [
            '_builtin' => true,
            'public' => true,
        ];

        return get_post_types($args, 'objects');
    }

    /**
     * Returns the custom post types
     *
     * @return array Custom post types
     */
    protected function customPostTypes()
    {
        $args = [
            '_builtin' => false,
            'public' => true,
            'show_ui' => true,
        ];

        return get_post_types($args, 'objects');
    }

    /**
     * Outputs the RSS feed
     *
     * @param string $url Feed URL
     * @param int $cache Cache duration in minutes
     * @param array $args Additional arguments
     * @return void
     */
    protected function feedOutput($url, $cache = 10, $args = [])
    {
        Feed::$transient = 'rrze_settings_feed_' . md5($url);
        Feed::$cacheExpire = absint($cache) * MINUTE_IN_SECONDS;

        $rss = Feed::load($url);

        if (is_wp_error($rss)) {
            if (current_user_can('manage_options')) {
                return '<p>' . sprintf(
                    /* translators: %s: RSS error message. */
                    __('RSS-Error: %s', 'rrze-settings'),
                    $rss->get_error_message()
                ) . '</p>';
            }
            return;
        }

        $output = '';

        $default_args = [
            'items'        => 0,
            'show_summary' => 0,
            'show_date'    => 0
        ];

        $args = wp_parse_args($args, $default_args);

        $maxitems = (int) $args['items'];
        if ($maxitems < 1 || 20 < $maxitems) {
            $maxitems = 10;
        }
        $show_summary = (int) $args['show_summary'];
        $show_date = (int) $args['show_date'];

        echo '<ul>';

        $i = 0;
        foreach ($rss->item as $item) {
            $i++;
            if ($i > $maxitems) {
                break;
            }
            if (empty($item->link)) {
                continue;
            }
            $link = $item->link;
            while (stristr($link, 'http') != $link) {
                $link = substr($link, 1);
            }
            $link = $link ? esc_url($link) : '';

            $title = esc_attr($item->title);
            if (empty($title)) {
                $title = __('Untitled', 'rrze-settings');
            }

            $description = html_entity_decode($item->description, ENT_QUOTES, get_option('blog_charset'));
            $description = esc_attr(wp_trim_words($description, 55, ' [&hellip;]'));

            $summary = '';
            if ($show_summary) {
                $summary = $description;

                // Change existing [...] to [&hellip;].
                if ('[...]' == substr($summary, -5)) {
                    $summary = substr($summary, 0, -5) . '[&hellip;]';
                }

                $summary = '<div class="rssSummary">' . esc_html($summary) . '</div>';
            }

            $date = '';
            if (!$show_date && $item->timestamp) {
                $date = ' <span class="rss-date">' . date_i18n(get_option('date_format'), (int) $item->timestamp) . '</span>';
            }

            if ($link == '') {
                echo "<li>$title{$date}{$summary}</li>";
            } elseif ($show_summary) {
                echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$summary}</li>";
            } else {
                echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}</li>";
            }
        }
        echo '</ul>';
        unset($rss);
    }
}
