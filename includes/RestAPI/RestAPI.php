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
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        if (!$this->siteOptions->rest->disabled) {
            return;
        }

        (new API($this->siteOptions))->loaded();
    }
}
