<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/*
 * Users class
 * 
 * @package RRZE\Settings\Users
 */
class Users extends Main
{
    /**
     * Plugin loaded action
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        (new Roles($this->siteOptions))->loaded();

        // Users search enhanced
        if ($this->siteOptions->users->users_search) {
            (new Search)->loaded();
        }

        // Contact list page
        if ($this->siteOptions->users->contact_page) {
            (new Contact)->loaded();
        }
    }
}
