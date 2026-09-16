<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

/**
 * Admin email verification class.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class AdminEmailVerification
{
    /**
     * Site options object.
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor.
     *
     * @param object $siteOptions Site options object.
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
    public function loaded(): void
    {
        if (!empty($this->siteOptions->general->disable_admin_email_verification)) {
            add_filter('admin_email_check_interval', '__return_false');
        }
    }
}
