<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Website functions class.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class WebsiteFunctions extends Main
{
    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded(): void
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();
    }
}
