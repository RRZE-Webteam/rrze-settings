<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * Handles the network-wide default theme for newly created sites.
 *
 * @package RRZE\Settings\General
 */
class DefaultTheme
{
    /**
     * Site options.
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor.
     *
     * @param object $siteOptions Site options
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded()
    {
        add_action('wp_initialize_site', [$this, 'switchDefaultTheme'], 20, 2);
    }

    /**
     * Switch the theme on a newly created site.
     *
     * @param \WP_Site $newSite New site
     * @param array $args Site initialization arguments
     * @return void
     */
    public function switchDefaultTheme(\WP_Site $newSite, array $args = [])
    {
        $stylesheet = $this->siteOptions->general->default_theme ?? '';
        if ($stylesheet === '') {
            return;
        }

        $theme = wp_get_theme($stylesheet);
        if (!$theme->exists()) {
            return;
        }

        switch_to_blog((int) $newSite->blog_id);
        switch_theme($stylesheet);
        restore_current_blog();
    }
}
