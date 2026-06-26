<?php

namespace RRZE\Settings\Governance;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Governance class
 *
 * @package RRZE\Settings\Governance
 */
class Governance extends Main
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

        (new Websupport($this->siteOptions))->loaded();
    }
}
