<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the general section of the plugin.
 *
 * @package RRZE\Settings\General
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-general-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('General', 'rrze-settings'),
            __('General', 'rrze-settings'),
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
    public function optionsValidate($input)
    {
        $input['textdomain_fallback'] = !empty($input['textdomain_fallback']) ? 1 : 0;
        $input['custom_error_page'] = !empty($input['custom_error_page']) ? 1 : 0;
        $input['white_label'] = !empty($input['white_label']) ? 1 : 0;
        $input['default_theme'] = $this->sanitizeDefaultTheme($input['default_theme'] ?? '');

        return $this->parseOptionsValidate($input, 'general');
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            $this->sectionName,
            __('General', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'textdomain_fallback',
            __('Textdomain Fallback', 'rrze-settings'),
            [$this, 'textdomainFallbackField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'custom_error_page',
            __('Custom Error Page', 'rrze-settings'),
            [$this, 'customErrorPageField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'white_label',
            __('White Label', 'rrze-settings'),
            [$this, 'whiteLabelField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'default_theme',
            __('Default Theme', 'rrze-settings'),
            [$this, 'defaultThemeField'],
            $this->menuPage,
            $this->sectionName
        );
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e('Network administrators can centrally manage general settings for every website in a multisite network on this page, with options such as textdomain fallback, custom error pages, white labeling, and the default theme for new websites.', 'rrze-settings');
    }

    /**
     * Display the textdomain_fallback field
     * 
     * @return void
     */
    public function textdomainFallbackField()
    {
        $this->renderCheckbox('rrze-settings-textdomain-fallback', sprintf('%s[textdomain_fallback]', $this->optionName), $this->siteOptions->general->textdomain_fallback, __('Sets a default language as fallback for unavailable language files', 'rrze-settings'));
    }

    /**
     * Diplay the custom_error_page field
     * 
     * @return void
     */
    public function customErrorPageField()
    {
        $this->renderCheckbox('rrze-settings-custom-error-page', sprintf('%s[custom_error_page]', $this->optionName), $this->siteOptions->general->custom_error_page, __('Enables custom error page', 'rrze-settings'));
    }

    /**
     * Display the white_label field
     * 
     * @return void
     */
    public function whiteLabelField()
    {
        $this->renderCheckbox('rrze-settings-white-label', sprintf('%s[white_label]', $this->optionName), $this->siteOptions->general->white_label, __('Ersetzt WordPress-Branding in Adminleiste und Admin-Footer, entfernt WordPress-Links, sortiert „Meine Websites“ und verwendet den Website-Absender für WordPress-E-Mails.', 'rrze-settings'));
    }

    /**
     * Display the default_theme field
     *
     * @return void
     */
    public function defaultThemeField()
    {
        $themes = wp_get_themes(['errors' => null]);
        $current = $this->siteOptions->general->default_theme ?? '';
        printf(
            '<label for="rrze-settings-default-theme" class="screen-reader-text">%1$s</label><select id="rrze-settings-default-theme" name="%2$s"><option value="">%3$s</option>',
            esc_html__('Default Theme', 'rrze-settings'),
            esc_attr(sprintf('%s[default_theme]', $this->optionName)),
            esc_html__('Use WordPress default', 'rrze-settings')
        );
        foreach ($themes as $stylesheet => $theme) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($stylesheet),
                selected($current, $stylesheet, false),
                esc_html(sprintf('%1$s (%2$s)', $theme->get('Name'), $stylesheet))
            );
        }
        echo '</select>';
        $this->renderDescription(__('Theme that is activated automatically when a new website is created. Leave empty to use the WordPress default theme.', 'rrze-settings'));
    }

    /**
     * Sanitize the default theme setting.
     *
     * @param string $stylesheet Theme stylesheet
     * @return string Sanitized theme stylesheet
     */
    protected function sanitizeDefaultTheme($stylesheet)
    {
        $stylesheet = sanitize_text_field(wp_unslash($stylesheet));

        if ($stylesheet === '') {
            return '';
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $stylesheet)) {
            return '';
        }

        $themes = wp_get_themes(['errors' => null]);
        if (!isset($themes[$stylesheet])) {
            return '';
        }

        return $stylesheet;
    }

}
