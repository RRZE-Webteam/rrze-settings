<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Class General
 *
 * This class serves as the main entry point for the general settings of the plugin. 
 * It initializes and loads all the related features such as dashboard modifications, 
 * emoji handling, Google Fonts management, custom error pages, textdomain fallbacks, 
 * white labeling, XMLRPC disabling, and the admin role threshold warning.
 *
 * @package RRZE\Settings\General
 */
class General extends Main
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

        // Base::loaded();

        (new Dashboard($this->siteOptions))->loaded();

        (new Emoji($this->siteOptions))->loaded();

        (new GoogleFonts($this->siteOptions))->loaded();

        (new ErrorPage($this->siteOptions))->loaded();

        (new Textdomain($this->siteOptions))->loaded();

        (new WhiteLabel($this->siteOptions))->loaded();

        (new XMLRPC($this->siteOptions))->loaded();

        (new AdminRoleThresholdWarning($this->siteOptions))->loaded();
    }
}
