<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Plugins class
 * 
 * This class handles the plugins settings in the admin area.
 * 
 * @package RRZE\Settings\Plugins
 */
class Plugins extends Main
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

        (new CF7($this->siteOptions))->loaded();

        (new CMSWorkflow($this->siteOptions))->loaded();

        (new FAUdir($this->siteOptions))->loaded();

        (new Jobs($this->siteOptions))->loaded();

        (new Newsletter($this->siteOptions))->loaded();

        (new RRZESearch($this->siteOptions))->loaded();

        (new Siteimprove($this->siteOptions))->loaded();

        (new TheSEOFramework($this->siteOptions))->loaded();

        (new WebT($this->siteOptions))->loaded();

        (new WSForm($this->siteOptions))->loaded();
    }
}
