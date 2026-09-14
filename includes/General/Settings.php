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
        $input['disable_xmlrpc'] = !empty($input['disable_xmlrpc']) ? 1 : 0;
        $input['disable_admin_email_verification'] = !empty($input['disable_admin_email_verification']) ? 1 : 0;
        $input['disable_google_fonts'] = !empty($input['disable_google_fonts']) ? 1 : 0;
        $input['disable_emoji'] = !empty($input['disable_emoji']) ? 1 : 0;
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
            'disable_xmlrpc',
            __('XML-RPC', 'rrze-settings'),
            [$this, 'xmlrpcField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'disable_admin_email_verification',
            __('Admin email verification', 'rrze-settings'),
            [$this, 'adminEmailVerificationField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'disable_emoji',
            __('Emoji', 'rrze-settings'),
            [$this, 'emojiField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'disable_google_fonts',
            __('Google Fonts', 'rrze-settings'),
            [$this, 'googleFontsField'],
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
        esc_html_e('Network administrators can centrally manage general settings for every website in a multisite network on this page, with options to enable or disable features such as textdomain fallback, XML-RPC, admin email verification, Google Fonts, emojis, custom error pages, white labeling, and the default theme for new websites.', 'rrze-settings');
    }

    /**
     * Display the textdomain_fallback field
     * 
     * @return void
     */
    public function textdomainFallbackField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-textdomain-fallback" name="<?php printf('%s[textdomain_fallback]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->textdomain_fallback, 1); ?>>
            <?php _e("Sets a default language as fallback for unavailable language files", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the disable_xmlrpc field
     * 
     * @return void
     */
    public function xmlrpcField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-xmlrpc" name="<?php printf('%s[disable_xmlrpc]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->disable_xmlrpc, 1); ?>>
            <?php _e("Disables the XML-RPC API", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the disable_admin_email_verification field
     * 
     * @return void
     */
    public function adminEmailVerificationField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-admin-email-verification" name="<?php printf('%s[disable_admin_email_verification]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->disable_admin_email_verification, 1); ?>>
            <?php _e("Disables the admin email verification check", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the disable_emoji field
     * 
     * @return void
     */
    public function emojiField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-emoji" name="<?php printf('%s[disable_emoji]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->disable_emoji, 1); ?>>
            <?php _e("Disables Emoji graphics", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the disable_google_fonts field
     * 
     * @return void
     */
    public function googleFontsField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-google-fonts" name="<?php printf('%s[disable_google_fonts]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->disable_google_fonts, 1); ?>>
            <?php _e("Disables loading of Google Fonts", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Diplay the custom_error_page field
     * 
     * @return void
     */
    public function customErrorPageField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-custom-error-page" name="<?php printf('%s[custom_error_page]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->custom_error_page, 1); ?>>
            <?php _e("Enables custom error page", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the white_label field
     * 
     * @return void
     */
    public function whiteLabelField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-white-label" name="<?php printf('%s[white_label]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->general->white_label, 1); ?>>
            <?php _e("Enables white label mode", 'rrze-settings'); ?>
        </label>
    <?php
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
    ?>
        <label for="rrze-settings-default-theme" class="screen-reader-text">
            <?php esc_html_e('Default Theme', 'rrze-settings'); ?>
        </label>
        <select id="rrze-settings-default-theme" name="<?php printf('%s[default_theme]', $this->optionName); ?>">
            <option value=""><?php esc_html_e('Use WordPress default', 'rrze-settings'); ?></option>
            <?php foreach ($themes as $stylesheet => $theme) { ?>
                <option value="<?php echo esc_attr($stylesheet); ?>" <?php selected($current, $stylesheet); ?>>
                    <?php echo esc_html(sprintf('%1$s (%2$s)', $theme->get('Name'), $stylesheet)); ?>
                </option>
            <?php } ?>
        </select>
        <p class="description">
            <?php esc_html_e('Theme that is activated automatically when a new website is created. Leave empty to use the WordPress default theme.', 'rrze-settings'); ?>
        </p>
    <?php
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
