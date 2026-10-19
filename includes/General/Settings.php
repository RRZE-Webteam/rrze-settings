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
            $this->menuPage,
            [
                'before_section' => '<div class="rrze-settings-general">',
                'after_section' => '</div>',
            ]
        );

        add_settings_field(
            'textdomain_fallback',
            __('Translation Fallback', 'rrze-settings'),
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
        echo '<p>';
        esc_html_e('Manage translations, error messages and the WordPress administration interface across the network. The default theme applies only to newly created websites. Changes take effect after saving.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Display the translation fallback control and explain when it applies.
     * 
     * @return void
     */
    public function textdomainFallbackField()
    {
        $this->renderCheckbox('rrze-settings-textdomain-fallback', sprintf('%s[textdomain_fallback]', $this->optionName), $this->siteOptions->general->textdomain_fallback, __('Use available translations when a language file is missing', 'rrze-settings'));
        $this->renderDescription(__('Uses another available language variant when a translation file is missing. Website content remains unchanged.', 'rrze-settings'));
        $this->renderSettingDetails(__('Translation Fallback', 'rrze-settings'), [
            __('If a translation file is missing or unreadable, look for an available variant of the same language first, then an English translation. For example, German can replace German (formal). This also applies to translations in the block editor.', 'rrze-settings'),
            __('Useful when plugins or themes do not provide every language variant. Does not translate website content, download language packs or fill individual gaps in an existing translation file. Disabled: WordPress handles translations without this additional fallback.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the custom error page control, scope and contact information.
     * 
     * @return void
     */
    public function customErrorPageField()
    {
        $this->renderCheckbox('rrze-settings-custom-error-page', sprintf('%s[custom_error_page]', $this->optionName), $this->siteOptions->general->custom_error_page, __('Use the RRZE error page for WordPress error messages', 'rrze-settings'));
        $this->renderDescription(__('Shows WordPress error messages in the RRZE layout. The website administration email address is displayed publicly as a contact; check that it is suitable before enabling this option.', 'rrze-settings'));
        $this->renderSettingDetails(__('Custom Error Page', 'rrze-settings'), [
            __('For example, denied access is shown with the website name and a contact address. The layout links to /impressum, /datenschutz and /barrierefreiheit; check that these pages are available.', 'rrze-settings'),
            __('Applies to regular browser requests handled by wp_die(). Does not replace theme 404 pages, server error pages or AJAX, JSON and XML responses. If the admin page header has already been output, only the message is shown. Disabled: the WordPress error display is used.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the white label control and its administration and email effects.
     * 
     * @return void
     */
    public function whiteLabelField()
    {
        $this->renderCheckbox('rrze-settings-white-label', sprintf('%s[white_label]', $this->optionName), $this->siteOptions->general->white_label, __('Customize WordPress branding, dashboard and email sender', 'rrze-settings'));
        $this->renderDescription(__('Customizes the admin bar, footer and dashboard together. Also replaces default WordPress email sender details with the website title and administration email address.', 'rrze-settings'));
        $this->renderSettingDetails(__('White Label', 'rrze-settings'), [
            __('Links the admin bar logo to the main website (site ID 1), removes WordPress information and support links, and shows the main website name and tagline in the admin footer. Sorts My Sites alphabetically.', 'rrze-settings'),
            __('Replaces the WordPress news widget with RRZE maintenance messages and, on website dashboards, Webmaster News. Adds counts for additional content types to At a Glance.', 'rrze-settings'),
            __('For outgoing emails, replaces sender addresses beginning with wordpress@ with the current website administration email address, if valid, and the sender name WordPress with the website title. Other sender values are preserved. Disabled: these customizations are not applied.', 'rrze-settings'),
        ]);
    }

    /**
     * Display installed themes and explain the scope of the default selection.
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
        $this->renderDescription(__('Activates the selected theme only for newly created websites. Existing websites keep their current theme.', 'rrze-settings'));
        $this->renderSettingDetails(__('Default Theme', 'rrze-settings'), [
            __('Select Use WordPress default to keep the theme chosen by WordPress during website creation. If the selected theme has been removed, this setting is skipped. Selecting a theme here does not enable it for selection across the network.', 'rrze-settings'),
            __('For example, after changing the default from theme A to theme B, only new websites start with theme B. Existing websites using theme A are not switched.', 'rrze-settings'),
        ]);
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
