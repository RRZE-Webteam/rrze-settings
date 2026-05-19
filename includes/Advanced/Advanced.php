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
    public function loaded(): void
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        if (!empty($this->siteOptions->advanced->frontend_style)) {
            add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendStyle'], 100);
        }

        if (!empty($this->siteOptions->advanced->backend_style)) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueBackendStyle'], 100);
        }

        if (!empty($this->siteOptions->advanced->disable_ai_functionality)) {
            add_filter('wp_supports_ai', '__return_false', PHP_INT_MAX);
            add_filter('wp_ai_client_prevent_prompt', '__return_true', PHP_INT_MAX);
        }

        if (!empty($this->siteOptions->advanced->hide_ai_connector_page)) {
            add_action('admin_menu', [$this, 'hideAIConnectorPage'], PHP_INT_MAX);
            add_action('admin_init', [$this, 'blockAIConnectorPageAccess'], 0);
        }

        if (!empty($this->siteOptions->advanced->block_editor_iframe_body_class) || !empty($this->siteOptions->advanced->block_editor_auto_theme_classes)) {
            add_action('enqueue_block_editor_assets', [$this, 'loadInjectBlockEditorIframeWithBodyClassScripts']);
        }
    }

    /**
     * Enqueue frontend style
     *
     * @return void
     */
    public function enqueueFrontendStyle(): void
    {
        wp_enqueue_style(
            'rrze-settings-advanced-frontend-style',
            plugins_url('build/advanced/placeholder.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
        wp_add_inline_style('rrze-settings-advanced-frontend-style', esc_textarea($this->siteOptions->advanced->frontend_style));
    }

    public function enqueueBackendStyle(): void
    {
        wp_enqueue_style(
            'rrze-settings-advanced-backend-style',
            plugins_url('build/advanced/placeholder.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
        wp_add_inline_style('rrze-settings-advanced-backend-style', esc_textarea($this->siteOptions->advanced->backend_style));
    }

    public function hideAIConnectorPage(): void
    {
        if (is_network_admin()) {
            return;
        }

        remove_submenu_page('options-general.php', 'options-connectors.php');
    }

    public function blockAIConnectorPageAccess(): void
    {
        global $pagenow;

        if (is_network_admin()) {
            return;
        }

        $is_connectors_page = 'options-connectors.php' === $pagenow;
        $is_legacy_connectors_page = 'options-general.php' === $pagenow
            && isset($_GET['page'])
            && 'options-connectors' === sanitize_key(wp_unslash($_GET['page']));

        if ($is_connectors_page || $is_legacy_connectors_page) {
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    public function loadInjectBlockEditorIframeWithBodyClassScripts(): void
    {
        // 1. Check for theme exceptions
        $theme_exceptions = array_map('trim', explode(',', $this->siteOptions->advanced->block_editor_theme_exceptions ?? ''));
        $current_theme = get_stylesheet();
        if (in_array($current_theme, $theme_exceptions)) {
            return;
        }

        $classes_to_inject = [];

        // 2. Get manually added classes
        $manual_classes = $this->siteOptions->advanced->block_editor_iframe_body_class ?? '';
        if (!empty($manual_classes)) {
            $classes_to_inject = array_merge($classes_to_inject, array_map('trim', explode(',', $manual_classes)));
        }

        // 3. Auto-generate theme class if enabled
        if (!empty($this->siteOptions->advanced->block_editor_auto_theme_classes)) {
            $classes_to_inject[] = 'wp-theme-' . $current_theme;
        }

        // 4. Enqueue script only if there are classes to inject
        if (empty($classes_to_inject)) {
            return;
        }

        $classes_string = implode(' ', array_filter(array_unique($classes_to_inject)));
        
        $script_path = plugin_dir_path(dirname(__FILE__, 2)) . 'build/advanced/block-editor-iframe-body-class-injection.js';
        $script_url = plugins_url('build/advanced/block-editor-iframe-body-class-injection.js', plugin()->getBasename());

        wp_enqueue_script(
            'custom-iframe-classes',
            $script_url,
            ['wp-data', 'wp-editor', 'wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
            filemtime($script_path),
            true
        );

        wp_localize_script('custom-iframe-classes', 'iframeBodyData', [
            'classes' => $classes_string,
        ]);
    }
}
