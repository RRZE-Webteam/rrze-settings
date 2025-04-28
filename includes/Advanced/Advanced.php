<?php

namespace RRZE\Settings\Advanced;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use function RRZE\Settings\plugin;

/**
 * Advanced class
 * @package RRZE\Settings\Advanced
 */
class Advanced extends Main
{
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        if ($this->siteOptions->advanced->frontend_style) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendStyle'], 100);
        }

        if ($this->siteOptions->advanced->backend_style) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueBackendStyle'], 100);
        }
    }

    /**
     * Enqueue frontend style
     *
     * @return void
     */
    public function enqueueFrontendStyle()
    {
        wp_enqueue_style(
            'rrze-settings-advanced-frontend-style',
            plugins_url('build/advanced/placeholder.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
        wp_add_inline_style('rrze-settings-advanced-frontend-style', esc_textarea($this->siteOptions->advanced->frontend_style));
    }

    public function enqueueBackendStyle()
    {
        wp_enqueue_style(
            'rrze-settings-advanced-backend-style',
            plugins_url('build/advanced/placeholder.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
        wp_add_inline_style('rrze-settings-advanced-backend-style', esc_textarea($this->siteOptions->advanced->backend_style));
    }
}
