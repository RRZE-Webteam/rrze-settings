<?php

namespace RRZE\Settings\Posts;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * Posts class
 * 
 * This class handles the posts list table in the admin area.
 * 
 * @package RRZE\Settings\Posts
 */
class Posts extends Main
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

        (new Post($this->siteOptions))->loaded();

        (new Page($this->siteOptions))->loaded();
    }
}
