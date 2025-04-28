<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

class General extends Main
{
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
    }
}
