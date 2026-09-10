<?php

namespace RRZE\Settings\RestAPI;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * RestAPI class
 *
 * @package RRZE\Settings\RestAPI
 */
class RestAPI extends Main
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        add_action('wp_loaded', [PublicEndpoints::class, 'discover']);

        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        (new API($this->siteOptions))->loaded();
    }
}
