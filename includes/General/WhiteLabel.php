<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * WhiteLabel class
 *
 * @package RRZE\Settings\General
 */
class WhiteLabel
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
        if ($this->siteOptions->general->white_label) {
            // Filter the "Thank you" text displayed in the admin footer.
            // https://developer.wordpress.org/reference/hooks/admin_footer_text/
            add_filter('admin_footer_text', [$this, 'adminFooterText']);

            // Custom Admin-Bar for logged in users (admin area)
            add_action('admin_head', [$this, 'loggedInAdminBarStyle']);
            // Custom Admin-Bar for logged in users (public area)
            add_action('wp_head', [$this, 'loggedInAdminBarStyle']);
            add_action('wp_before_admin_bar_render', [$this, 'beforeAdminBarRender']);
            add_action('admin_bar_menu', [$this, 'sortUserSites']);

            // Set WP-Mail
            add_filter('wp_mail_from', [$this, 'wpMailFrom']);
            add_filter('wp_mail_from_name', [$this, 'wpMailFromName']);
        }
    }

    /**
     * Custom admin footer text
     *
     * @return void
     */
    public function adminFooterText()
    {
        switch_to_blog(1);
        if (get_bloginfo('description')) {
            printf('%1$s | %2$s', get_bloginfo('name'), get_bloginfo('description'));
        } else {
            echo get_bloginfo('name');
        }
        restore_current_blog();
    }

    /**
     * Custom admin bar style for logged in users
     *
     * @return void
     */
    public function loggedInAdminBarStyle()
    {
?>
        <style type="text/css">
            #wpadminbar #wp-admin-bar-wp-logo>.ab-item .ab-icon:before,
            #wpadminbar .quicklinks li .blavatar:before {
                content: "\f319";
                top: 2px;
            }
        </style>
<?php
    }

    /**
     * Custom admin bar render
     *
     * @return void
     */
    public function beforeAdminBarRender()
    {
        global $wp_admin_bar;

        $wp_admin_bar->remove_menu('about');
        $wp_admin_bar->remove_menu('wporg');
        $wp_admin_bar->remove_menu('documentation');
        $wp_admin_bar->remove_menu('support-forums');
        $wp_admin_bar->remove_menu('feedback');

        $startSite = get_blog_details(1);
        $siteUrl = $startSite->siteurl;
        $siteTitle = wp_html_excerpt($startSite->blogname, 40, '&hellip;');

        $wp_admin_bar->add_menu([
            'id'    => 'wp-logo',
            'title' => sprintf('<span class="ab-icon"></span><span class="screen-reader-text">%s</span>', $siteTitle),
            'href'  => $siteUrl,
        ]);

        $wp_admin_bar->add_menu([
            'parent' => 'wp-logo',
            'id'     => 'cms-website',
            'title'  => $siteTitle,
            'href'   => $siteUrl,
        ]);
    }

    /**
     * Sort user websites in the admin bar
     *
     * @param object $wp_admin_bar WP Admin Bar object
     * @return void
     */
    public function sortUserSites(&$wp_admin_bar)
    {
        if (!is_user_logged_in()) {
            return;
        }
        foreach ($wp_admin_bar->user->blogs as $key => $blog) {
            if ($blog->blogname == '') {
                $wp_admin_bar->user->blogs[$key]->blogname = $blog->domain;
            }
        }
        usort($wp_admin_bar->user->blogs, function ($a, $b) {
            return strnatcasecmp($a->blogname, $b->blogname);
        });
    }

    /**
     * Set the "From" email address for WordPress emails
     *
     * @param string $fromMail The original "From" email address
     * @return string The modified "From" email address
     */
    public function wpMailFrom($fromMail)
    {
        $adminMail = get_option('admin_email');

        if (strpos($fromMail, 'wordpress@') === 0 && filter_var($adminMail, FILTER_VALIDATE_EMAIL)) {
            return $adminMail;
        }

        return $fromMail;
    }

    /**
     * Set the "From" name for WordPress emails
     *
     * @param string $formName The original "From" name
     * @return string The modified "From" name
     */
    public function wpMailFromName($formName)
    {
        $siteName = get_bloginfo('name');

        if ($formName == 'WordPress' && $siteName) {
            return $siteName;
        }

        return $formName;
    }
}
