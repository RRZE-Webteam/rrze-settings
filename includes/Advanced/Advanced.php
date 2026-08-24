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
    private const SENTRY_MARKER_FILE = '.rrze-settings-sentry.json';

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

        if (!empty($this->siteOptions->advanced->disable_font_library_admin)) {
            add_action('admin_menu', [$this, 'hideFontLibraryPage'], PHP_INT_MAX);
            add_action('load-appearance_page_font-library', [$this, 'blockFontLibraryPageAccess']);
        }

        if (!empty($this->siteOptions->advanced->block_editor_iframe_body_class) || !empty($this->siteOptions->advanced->block_editor_auto_theme_classes)) {
            add_action('enqueue_block_editor_assets', [$this, 'loadInjectBlockEditorIframeWithBodyClassScripts']);
        }

        if (!empty($this->siteOptions->advanced->sentry_mode)) {
            add_action('upgrader_process_complete', [$this, 'recordSentryUpdate'], 10, 2);

            if (!empty($this->siteOptions->advanced->sentry_mode_monitor_deletions)) {
                add_action('deleted_plugin', [$this, 'recordSentryDeletedPlugin'], 10, 2);
                add_action('deleted_theme', [$this, 'recordSentryDeletedTheme'], 10, 2);
            }
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

    public function hideFontLibraryPage(): void
    {
        if (is_network_admin()) {
            return;
        }

        remove_submenu_page('themes.php', 'font-library.php');
    }

    public function blockFontLibraryPageAccess(): void
    {
        wp_die(
            esc_html__('This feature has been disabled.', 'rrze-settings'),
            esc_html__('Disabled', 'rrze-settings'),
            ['response' => 403, 'back_link' => true]
        );
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
            ['wp-dom-ready'],
            filemtime($script_path),
            true
        );

        wp_localize_script('custom-iframe-classes', 'iframeBodyData', [
            'classes' => $classes_string,
        ]);
    }

    /**
     * Write the sentry marker after WordPress completes an upgrade process.
     *
     * @param mixed $upgrader The upgrader instance passed by WordPress.
     * @param array $options Upgrade metadata from WordPress.
     * @return void
     */
    public function recordSentryUpdate($upgrader, array $options): void
    {
        unset($upgrader);

        $type = sanitize_key($options['type'] ?? '');
        $action = sanitize_key($options['action'] ?? '');

        if (!in_array($type, ['core', 'plugin', 'theme', 'translation'], true)) {
            return;
        }

        if (!in_array($action, ['install', 'update'], true)) {
            return;
        }

        $this->writeSentryMarkerFile([
            'last_update_unix' => time(),
            'last_update_utc' => gmdate('c'),
            'action' => $action,
            'type' => $type,
            'bulk' => !empty($options['bulk']),
            'items' => $this->getSentryUpdateItems($type, $options),
        ]);
    }

    /**
     * Write the sentry marker after WordPress successfully deletes a plugin.
     *
     * @param string $pluginFile Plugin file relative to the plugins directory.
     * @param bool   $deleted Whether WordPress successfully deleted the plugin.
     * @return void
     */
    public function recordSentryDeletedPlugin(string $pluginFile, bool $deleted): void
    {
        if (!$deleted) {
            return;
        }

        $this->writeSentryMarkerFile([
            'last_update_unix' => time(),
            'last_update_utc' => gmdate('c'),
            'action' => 'delete',
            'type' => 'plugin',
            'bulk' => false,
            'items' => $this->sanitizeSentryItems($pluginFile),
        ]);
    }

    /**
     * Write the sentry marker after WordPress successfully deletes a theme.
     *
     * @param string $stylesheet Stylesheet of the deleted theme.
     * @param bool   $deleted Whether WordPress successfully deleted the theme.
     * @return void
     */
    public function recordSentryDeletedTheme(string $stylesheet, bool $deleted): void
    {
        if (!$deleted) {
            return;
        }

        $this->writeSentryMarkerFile([
            'last_update_unix' => time(),
            'last_update_utc' => gmdate('c'),
            'action' => 'delete',
            'type' => 'theme',
            'bulk' => false,
            'items' => $this->sanitizeSentryItems($stylesheet),
        ]);
    }

    /**
     * Store the latest update metadata in the WordPress base directory.
     *
     * @param array $payload Sentry marker payload.
     * @return void
     */
    private function writeSentryMarkerFile(array $payload): void
    {
        $file = trailingslashit(ABSPATH) . self::SENTRY_MARKER_FILE;
        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!is_string($json)) {
            $json = '';
        }

        if (false === @file_put_contents($file, $json . PHP_EOL, LOCK_EX)) {
            @touch($file);
        }
    }

    /**
     * Extract the updated items WordPress reports for each upgrade type.
     *
     * @param string $type Upgrade type.
     * @param array  $options Upgrade metadata from WordPress.
     * @return array
     */
    private function getSentryUpdateItems(string $type, array $options): array
    {
        switch ($type) {
            case 'plugin':
                return $this->sanitizeSentryItems($options['plugins'] ?? ($options['plugin'] ?? []));
            case 'theme':
                return $this->sanitizeSentryItems($options['themes'] ?? ($options['theme'] ?? []));
            case 'translation':
                return $this->sanitizeSentryTranslations($options['translations'] ?? []);
            case 'core':
                return array_filter([
                    'version' => sanitize_text_field(get_bloginfo('version')),
                ]);
        }

        return [];
    }

    /**
     * Sanitize plugin or theme identifiers for the marker payload.
     *
     * @param mixed $items Plugin basenames or theme slugs.
     * @return array
     */
    private function sanitizeSentryItems($items): array
    {
        $items = is_array($items) ? $items : [$items];

        return array_values(array_filter(array_map(
            static fn($item) => is_scalar($item) ? sanitize_text_field((string) $item) : '',
            $items
        )));
    }

    /**
     * Sanitize translation metadata for the marker payload.
     *
     * @param mixed $translations Translation metadata from WordPress.
     * @return array
     */
    private function sanitizeSentryTranslations($translations): array
    {
        if (!is_array($translations)) {
            return [];
        }

        $sanitizedTranslations = [];
        foreach ($translations as $translation) {
            if (!is_array($translation)) {
                continue;
            }

            $sanitizedTranslations[] = array_filter([
                'type' => sanitize_key($translation['type'] ?? ''),
                'slug' => sanitize_text_field((string) ($translation['slug'] ?? '')),
                'language' => sanitize_text_field((string) ($translation['language'] ?? '')),
                'version' => sanitize_text_field((string) ($translation['version'] ?? '')),
            ]);
        }

        return $sanitizedTranslations;
    }
}
