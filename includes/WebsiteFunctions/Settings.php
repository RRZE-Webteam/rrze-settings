<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Settings for website-related network functions.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug.
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-website-functions';

    /**
     * Other settings section name.
     *
     * @var string
     */
    protected $otherSectionName = 'rrze-settings-website-functions-other-section';

    /**
     * General settings section name.
     *
     * @var string
     */
    protected $generalSectionName = 'rrze-settings-website-functions-general-section';

    /**
     * Discussion settings section name.
     *
     * @var string
     */
    protected $discussionSectionName = 'rrze-settings-website-functions-discussion-section';

    /**
     * Metatags settings section name.
     *
     * @var string
     */
    protected $metatagsSectionName = 'rrze-settings-website-functions-metatags-section';

    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded(): void
    {
        if (is_network_admin()) {
            add_action('network_admin_menu', [$this, 'networkAdminMenu']);
            add_action('network_admin_menu', [$this, 'settingsUpdate']);
            add_action('network_admin_menu', [$this, 'networkAdminPage']);
            return;
        }

        add_action('admin_init', [$this, 'adminPage']);
    }

    /**
     * Adds a submenu page to the network admin menu.
     *
     * @return void
     */
    public function networkAdminMenu(): void
    {
        add_submenu_page(
            'rrze-settings',
            __('Website Functions', 'rrze-settings'),
            __('Website Functions', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            [$this, 'optionsPage']
        );
    }

    /**
     * Validate the options.
     *
     * @param array $input The input data.
     * @return object The validated options.
     */
    public function optionsValidate($input): object
    {
        $input = is_array($input) ? $input : [];

        if (is_network_admin()) {
            $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
            $this->siteOptions->tools->disable_delete_site = !empty($input['disable_delete_site']) ? 1 : 0;
            $this->siteOptions->tools->disable_privacy_options = !empty($input['disable_privacy_options']) ? 1 : 0;
            $this->siteOptions->general->disable_xmlrpc = !empty($input['disable_xmlrpc']) ? 1 : 0;
            $this->siteOptions->general->disable_admin_email_verification = !empty($input['disable_admin_email_verification']) ? 1 : 0;
            $this->siteOptions->general->disable_emoji = !empty($input['disable_emoji']) ? 1 : 0;
            $this->siteOptions->general->disable_google_fonts = !empty($input['disable_google_fonts']) ? 1 : 0;
            $this->siteOptions->advanced->disable_ai_functionality = !empty($input['disable_ai_functionality']) ? 1 : 0;
            $this->siteOptions->advanced->hide_ai_connector_page = !empty($input['hide_ai_connector_page']) ? 1 : 0;
            $this->siteOptions->advanced->disable_font_library_admin = !empty($input['disable_font_library_admin']) ? 1 : 0;
            $this->siteOptions->discussion->default_settings = !empty($input['discussion_default_settings']) ? 1 : 0;
            $this->siteOptions->discussion->disable_avatars = !empty($input['discussion_disable_avatars']) ? 1 : 0;
            $this->siteOptions->metatags->allow_google_notranslate = !empty($input['allow_google_notranslate']) ? 1 : 0;

            return $this->siteOptions;
        }

        $this->options = $this->prepareOptionContainers($this->options);

        return $this->options;
    }

    /**
     * Adds sections and fields to the settings page.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);

        add_settings_section(
            $this->generalSectionName,
            __('General', 'rrze-settings'),
            [$this, 'generalSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'disable_xmlrpc',
            __('XML-RPC', 'rrze-settings'),
            [$this, 'xmlrpcField'],
            $this->menuPage,
            $this->generalSectionName
        );

        add_settings_field(
            'disable_admin_email_verification',
            __('Admin email verification', 'rrze-settings'),
            [$this, 'adminEmailVerificationField'],
            $this->menuPage,
            $this->generalSectionName
        );

        add_settings_field(
            'disable_emoji',
            __('Emoji', 'rrze-settings'),
            [$this, 'emojiField'],
            $this->menuPage,
            $this->generalSectionName
        );

        add_settings_field(
            'disable_google_fonts',
            __('Google Fonts', 'rrze-settings'),
            [$this, 'googleFontsField'],
            $this->menuPage,
            $this->generalSectionName
        );

        add_settings_section(
            $this->discussionSectionName,
            __('Discussion', 'rrze-settings'),
            [$this, 'discussionSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'discussion_default_settings',
            __('Discussion Defaults', 'rrze-settings'),
            [$this, 'discussionDefaultSettingsField'],
            $this->menuPage,
            $this->discussionSectionName
        );

        add_settings_field(
            'discussion_disable_avatars',
            __('Disable Avatars', 'rrze-settings'),
            [$this, 'discussionDisableAvatarsField'],
            $this->menuPage,
            $this->discussionSectionName
        );

        add_settings_section(
            $this->metatagsSectionName,
            __('Metatags', 'rrze-settings'),
            [$this, 'metatagsSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'allow_google_notranslate',
            __('Google Notranslate', 'rrze-settings'),
            [$this, 'allowGoogleNotranslateField'],
            $this->menuPage,
            $this->metatagsSectionName
        );

        add_settings_section(
            $this->otherSectionName,
            __('Other Functions', 'rrze-settings'),
            [$this, 'otherSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'disable_delete_site',
            __('Delete Website', 'rrze-settings'),
            [$this, 'deleteSiteField'],
            $this->menuPage,
            $this->otherSectionName
        );

        add_settings_field(
            'disable_privacy_options',
            __('Privacy Options', 'rrze-settings'),
            [$this, 'privacyOptionsField'],
            $this->menuPage,
            $this->otherSectionName
        );

        add_settings_field(
            'disable_ai_functionality',
            __('Disable AI Functionality', 'rrze-settings'),
            [$this, 'disableAIFunctionalityField'],
            $this->menuPage,
            $this->otherSectionName
        );

        add_settings_field(
            'hide_ai_connector_page',
            __('Hide AI Connector Page for Users', 'rrze-settings'),
            [$this, 'hideAIConnectorPageField'],
            $this->menuPage,
            $this->otherSectionName
        );

        add_settings_field(
            'disable_font_library_admin',
            __('Disable Font Library in Admin Dashboard', 'rrze-settings'),
            [$this, 'disableFontLibraryAdminField'],
            $this->menuPage,
            $this->otherSectionName
        );
    }

    /**
     * Registers local reading settings when enabled network-wide.
     *
     * @return void
     */
    public function adminPage(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);

        if (empty($this->siteOptions->metatags->allow_google_notranslate)) {
            return;
        }

        register_setting(
            'reading',
            'rrze_settings_google_notranslate',
            [
                'type' => 'integer',
                'sanitize_callback' => [$this, 'sanitizeGoogleNotranslateOption'],
                'default' => 0,
            ]
        );

        add_settings_section(
            'rrze-settings-reading-google-translate-section',
            __('Metatags', 'rrze-settings'),
            '__return_false',
            'reading'
        );

        add_settings_field(
            'rrze-settings-google-notranslate',
            __('Google Translate', 'rrze-settings'),
            [$this, 'googleNotranslateField'],
            'reading',
            'rrze-settings-reading-google-translate-section'
        );
    }

    /**
     * Sanitizes the local Google notranslate option.
     *
     * @param mixed $value Submitted option value.
     * @return int Sanitized setting value.
     */
    public function sanitizeGoogleNotranslateOption($value): int
    {
        return !empty($value) ? 1 : 0;
    }

    /**
     * Display the other section description.
     *
     * @return void
     */
    public function otherSectionDescription(): void
    {
        esc_html_e('Additional website-related functions that are applied across the multisite network.', 'rrze-settings');
    }

    /**
     * Display the general section description.
     *
     * @return void
     */
    public function generalSectionDescription(): void
    {
        esc_html_e('General website features that are applied across the multisite network.', 'rrze-settings');
    }

    /**
     * Display the discussion section description.
     *
     * @return void
     */
    public function discussionSectionDescription(): void
    {
        esc_html_e('Network administrators can centrally configure discussion settings across the multisite network.', 'rrze-settings');
    }

    /**
     * Display the metatags section description.
     *
     * @return void
     */
    public function metatagsSectionDescription(): void
    {
        esc_html_e('Controls which metatag settings website administrators can configure locally.', 'rrze-settings');
    }

    /**
     * Renders the delete site field.
     *
     * @return void
     */
    public function deleteSiteField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-delete-site" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_delete_site]', $this->optionName)),
            checked($this->siteOptions->tools->disable_delete_site, 1, false),
            esc_html__('Disables the delete site feature', 'rrze-settings')
        );
    }

    /**
     * Renders the privacy options field.
     *
     * @return void
     */
    public function privacyOptionsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-privacy-options" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_privacy_options]', $this->optionName)),
            checked($this->siteOptions->tools->disable_privacy_options, 1, false),
            esc_html__('Disables the privacy settings', 'rrze-settings')
        );
    }

    /**
     * Display the disable_ai_functionality field.
     *
     * @return void
     */
    public function disableAIFunctionalityField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-advanced-disable-ai-functionality" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_ai_functionality]', $this->optionName)),
            checked($this->siteOptions->advanced->disable_ai_functionality, 1, false),
            esc_html__('Disable AI functionality in WordPress.', 'rrze-settings')
        );
    }

    /**
     * Display the hide_ai_connector_page field.
     *
     * @return void
     */
    public function hideAIConnectorPageField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-advanced-hide-ai-connector-page" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[hide_ai_connector_page]', $this->optionName)),
            checked($this->siteOptions->advanced->hide_ai_connector_page, 1, false),
            esc_html__('Hide the AI Connectors settings page for users and block direct access.', 'rrze-settings')
        );
    }

    /**
     * Display the disable_font_library_admin field.
     *
     * @return void
     */
    public function disableFontLibraryAdminField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-advanced-disable-font-library-admin" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_font_library_admin]', $this->optionName)),
            checked($this->siteOptions->advanced->disable_font_library_admin, 1, false),
            esc_html__('Hide the Font Library page in the admin dashboard and block direct access.', 'rrze-settings')
        );
    }

    /**
     * Display the disable_xmlrpc field.
     *
     * @return void
     */
    public function xmlrpcField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-xmlrpc" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_xmlrpc]', $this->optionName)),
            checked($this->siteOptions->general->disable_xmlrpc, 1, false),
            esc_html__('Disables the XML-RPC API', 'rrze-settings')
        );
    }

    /**
     * Display the disable_admin_email_verification field.
     *
     * @return void
     */
    public function adminEmailVerificationField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-admin-email-verification" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_admin_email_verification]', $this->optionName)),
            checked($this->siteOptions->general->disable_admin_email_verification, 1, false),
            esc_html__('Disables the admin email verification check', 'rrze-settings')
        );
    }

    /**
     * Display the disable_emoji field.
     *
     * @return void
     */
    public function emojiField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-emoji" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_emoji]', $this->optionName)),
            checked($this->siteOptions->general->disable_emoji, 1, false),
            esc_html__('Disables Emoji graphics', 'rrze-settings')
        );
    }

    /**
     * Display the disable_google_fonts field.
     *
     * @return void
     */
    public function googleFontsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-disable-google-fonts" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[disable_google_fonts]', $this->optionName)),
            checked($this->siteOptions->general->disable_google_fonts, 1, false),
            esc_html__('Disables loading of Google Fonts', 'rrze-settings')
        );
    }

    /**
     * Display the discussion_default_settings field.
     *
     * @return void
     */
    public function discussionDefaultSettingsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-discussion-default-settings" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[discussion_default_settings]', $this->optionName)),
            checked($this->siteOptions->discussion->default_settings, 1, false),
            esc_html__('Apply restrictive discussion defaults', 'rrze-settings')
        );
        echo '<p class="description">', esc_html__('For new websites, comments and pingbacks are disabled by default and commenting requires a login. Across the network, new posts and pages get closed comments by default, and anonymous commenting is blocked.', 'rrze-settings'), '</p>';
    }

    /**
     * Display the discussion_disable_avatars field.
     *
     * @return void
     */
    public function discussionDisableAvatarsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-discussion-disable-avatars" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[discussion_disable_avatars]', $this->optionName)),
            checked($this->siteOptions->discussion->disable_avatars, 1, false),
            esc_html__('Disable avatars settings', 'rrze-settings')
        );
    }

    /**
     * Display the allow_google_notranslate field.
     *
     * @return void
     */
    public function allowGoogleNotranslateField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        printf(
            '<label><input type="checkbox" id="rrze-settings-allow-google-notranslate" name="%1$s" value="1" %2$s> %3$s</label>',
            esc_attr(sprintf('%s[allow_google_notranslate]', $this->optionName)),
            checked($this->siteOptions->metatags->allow_google_notranslate, 1, false),
            esc_html__('Allow website administrators to tell Googlebot that this website should not be offered by Google as a translated copy.', 'rrze-settings')
        );
    }

    /**
     * Display the local google_notranslate field.
     *
     * @return void
     */
    public function googleNotranslateField(): void
    {
        echo '<input type="hidden" name="rrze_settings_google_notranslate" value="0">';
        printf(
            '<label><input type="checkbox" id="rrze-settings-google-notranslate" name="rrze_settings_google_notranslate" value="1" %1$s> %2$s</label>',
            checked((int) get_option('rrze_settings_google_notranslate', 0), 1, false),
            esc_html__('Prevent Google from creating a copy of the website in other languages and offering it under its own domain.', 'rrze-settings')
        );
    }

    /**
     * Ensure all option containers edited by this page exist as objects.
     *
     * @param object $options Parsed options.
     * @return object Prepared options.
     */
    private function prepareOptionContainers(object $options): object
    {
        $defaultOptions = $this->prepareDefaultOptions();

        foreach (['tools', 'general', 'advanced', 'discussion', 'metatags'] as $group) {
            if (empty($options->{$group}) || (!is_object($options->{$group}) && !is_array($options->{$group}))) {
                $options->{$group} = $defaultOptions->{$group} ?? new \stdClass();
            }

            $options->{$group} = (object) wp_parse_args(
                (array) $options->{$group},
                (array) ($defaultOptions->{$group} ?? [])
            );
        }

        return $options;
    }

    /**
     * Ensure default options can be used as objects.
     *
     * @return object Default options.
     */
    private function prepareDefaultOptions(): object
    {
        $defaultOptions = is_object($this->defaultOptions) ? $this->defaultOptions : new \stdClass();

        foreach (['tools', 'general', 'advanced', 'discussion', 'metatags'] as $group) {
            if (empty($defaultOptions->{$group}) || (!is_object($defaultOptions->{$group}) && !is_array($defaultOptions->{$group}))) {
                $defaultOptions->{$group} = new \stdClass();
            }

            $defaultOptions->{$group} = (object) $defaultOptions->{$group};
        }

        return $defaultOptions;
    }
}
