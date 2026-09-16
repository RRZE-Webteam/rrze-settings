<?php

namespace RRZE\Settings\Advanced;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;
use RRZE\Settings\Advanced\Sanitizer;

/**
 * Class Settings
 *
 * This class handles the settings for the advanced section of the plugin.
 *
 * @package RRZE\Settings\Advanced
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-advanced';

    /**
     * Settings section name
     *
     * @var string
     */
    protected $sectionName = 'rrze-settings-advanced-section';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu(): void
    {
        add_submenu_page(
            'rrze-settings',
            __('Advanced', 'rrze-settings'),
            __('Advanced', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            [$this, 'optionsPage']
        );
    }

    /**
     * Validate the options
     *
     * @param array $input The input data
     * @return object The validated options
     */
    public function optionsValidate($input): object
    {
        // FIELDS FOR GLOBAL CSS INJECTION START
        $input['frontend_style'] = trim(wp_strip_all_tags($input['frontend_style']));
        $input['backend_style'] = trim(wp_strip_all_tags($input['backend_style']));
        // FIELDS FOR GLOBAL CSS INJECTION END

        // FIELDS FOR IFRAME CLASS INJECTION START
        $input['block_editor_iframe_body_class'] = Sanitizer::sanitize_css_classes($input['block_editor_iframe_body_class'] ?? '');
        $input['block_editor_theme_exceptions'] = Sanitizer::sanitize_theme_slugs($input['block_editor_theme_exceptions'] ?? '');
        $input['block_editor_auto_theme_classes'] = isset($input['block_editor_auto_theme_classes']) ? 1 : 0;
        // FIELDS FOR IFRAME CLASS INJECTION END

        $input['sentry_mode'] = isset($input['sentry_mode']) ? 1 : 0;
        $input['sentry_mode_monitor_deletions'] = isset($input['sentry_mode_monitor_deletions']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'advanced');
    }

    /**
     * Adds sections and fields to the settings page
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        add_settings_section(
            $this->sectionName,
            __('Advanced', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'frontend_style',
            __('Frontend Style', 'rrze-settings'),
            [$this, 'frontendStyleField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'backend_style',
            __('Backend Style', 'rrze-settings'),
            [$this, 'backendStyleField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'block_editor_iframe_body_class',
            __('BlockEditor iFrame Body Class', 'rrze-settings'),
            [$this, 'BEIframeBodyClassField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'block_editor_theme_exceptions',
            __('Theme Exceptions', 'rrze-settings'),
            [$this, 'themeExceptionsField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'block_editor_auto_theme_classes',
            __('Auto-generate Theme Classes', 'rrze-settings'),
            [$this, 'autoThemeClassesField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'sentry_mode',
            __('Sentry Mode', 'rrze-settings'),
            [$this, 'sentryModeField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'sentry_mode_monitor_deletions',
            __('Monitor Deleted Themes and Plugins', 'rrze-settings'),
            [$this, 'sentryModeMonitorDeletionsField'],
            $this->menuPage,
            $this->sectionName
        );
    }

    /**
     * Display the main section description
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        esc_html_e('This page enables network administrators to centrally manage custom CSS styles for both the frontend and backend of every website in a multisite network, offering fields to input and organize CSS code and ensure advanced customization and consistent branding across the entire network.', 'rrze-settings');
    }

    /**
     * Display the frontend_style field
     *
     * @return void
     */
    public function frontendStyleField(): void
    {
        $this->renderTextarea(
            'rrze-settings-advanced-frontend-style',
            sprintf('%s[frontend_style]', $this->optionName),
            $this->siteOptions->advanced->frontend_style,
            5,
            55
        );
        $this->renderDescription(__('Enter the CSS code which will be added to the frontend of all websites.', 'rrze-settings'));
    }

    /**
     * Display the backend_style field
     *
     * @return void
     */
    public function backendStyleField(): void
    {
        $this->renderTextarea(
            'rrze-settings-advanced-backend-style',
            sprintf('%s[backend_style]', $this->optionName),
            $this->siteOptions->advanced->backend_style,
            5,
            55
        );
        $this->renderDescription(__('Enter the CSS code which will be added to the backend of all websites.', 'rrze-settings'));
    }

    /**
     * Display the block_editor_iframe_body_class field
     *
     * @return void
     * @since 2.1.1
     */
    public function BEIframeBodyClassField(): void
    {
        $this->renderTextarea(
            'rrze-settings-advanced-blockeditor-iframe-body-class',
            sprintf('%s[block_editor_iframe_body_class]', $this->optionName),
            $this->siteOptions->advanced->block_editor_iframe_body_class,
            5,
            55
        );
        $this->renderDescription(__('Enter a comma-separated list of CSS classes which will be injected into the body tag within the iFrame of the Block Editor.', 'rrze-settings'));
    }

    /**
     * Display the block_editor_theme_exceptions field
     *
     * @return void
     */
    public function themeExceptionsField(): void
    {
        $this->renderTextarea(
            'rrze-settings-advanced-block-editor-theme-exceptions',
            sprintf('%s[block_editor_theme_exceptions]', $this->optionName),
            $this->siteOptions->advanced->block_editor_theme_exceptions,
            5,
            55
        );
        $this->renderDescription(__('Enter a comma-separated list of theme slugs (e.g., twentytwentyone, astra) where the Block Editor Body Class Injection should not be active.', 'rrze-settings'));
    }

    /**
     * Display the block_editor_auto_theme_classes field
     *
     * @return void
     */
    public function autoThemeClassesField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-block-editor-auto-theme-classes',
            sprintf('%s[block_editor_auto_theme_classes]', $this->optionName),
            $this->siteOptions->advanced->block_editor_auto_theme_classes,
            __('Automatically generate theme classes and inject them into the iFrame body tag.', 'rrze-settings')
        );
    }

    /**
     * Display the sentry_mode field
     *
     * @return void
     */
    public function sentryModeField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-sentry-mode',
            sprintf('%s[sentry_mode]', $this->optionName),
            $this->siteOptions->advanced->sentry_mode,
            __('Write a sentry marker file in the WordPress base directory whenever WordPress completes a core, plugin, theme, or translation install/update.', 'rrze-settings')
        );
    }

    /**
     * Display the sentry_mode_monitor_deletions field
     *
     * @return void
     */
    public function sentryModeMonitorDeletionsField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-sentry-mode-monitor-deletions',
            sprintf('%s[sentry_mode_monitor_deletions]', $this->optionName),
            $this->siteOptions->advanced->sentry_mode_monitor_deletions,
            __('When Sentry Mode is active, also update the sentry marker file after successful plugin or theme deletions.', 'rrze-settings')
        );
    }
}
