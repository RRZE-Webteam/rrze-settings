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
            __('Functions and Services', 'rrze-settings'),
            __('Functions and Services', 'rrze-settings'),
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
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getWebsiteFunctionSections(): array
    {
        return [
            'rrze-settings-website-functions-services' => [
                'title' => __('Interfaces and AI', 'rrze-settings'),
                'callback' => 'servicesSectionDescription',
            ],
            'rrze-settings-website-functions-appearance' => [
                'title' => __('Appearance and Fonts', 'rrze-settings'),
                'callback' => 'appearanceSectionDescription',
            ],
            'rrze-settings-website-functions-discussion-section' => [
                'title' => __('Comments and Avatars', 'rrze-settings'),
                'callback' => 'discussionSectionDescription',
            ],
            'rrze-settings-website-functions-management' => [
                'title' => __('Website Administration', 'rrze-settings'),
                'callback' => 'managementSectionDescription',
            ],
            'rrze-settings-website-functions-metatags-section' => [
                'title' => __('Translation in Google Search', 'rrze-settings'),
                'callback' => 'metatagsSectionDescription',
            ],
        ];
    }

    /**
     * Render the shared functions and services form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage(): void
    {
        global $title;

        $sections = $this->getWebsiteFunctionSections();
        echo '<div class="wrap rrze-settings-website-functions-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-website-functions-index" aria-labelledby="rrze-settings-website-functions-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-website-functions-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-website-functions" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-website-functions-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-website-functions-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
            echo '</div>';
            $this->{$section['callback']}();
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($this->menuPage, $sectionId);
            echo '</table></section>';
        }
        submit_button(__('Save Changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo '</form></div>';
    }

    /**
     * Register the twelve network settings in their user-facing sections.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);

        foreach ($this->getWebsiteFunctionSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'disable_xmlrpc',
            __('XML-RPC', 'rrze-settings'),
            [$this, 'xmlrpcField'],
            $this->menuPage,
            'rrze-settings-website-functions-services'
        );

        add_settings_field(
            'disable_ai_functionality',
            __('WordPress AI Functions', 'rrze-settings'),
            [$this, 'disableAIFunctionalityField'],
            $this->menuPage,
            'rrze-settings-website-functions-services'
        );

        add_settings_field(
            'hide_ai_connector_page',
            __('AI Connection Settings', 'rrze-settings'),
            [$this, 'hideAIConnectorPageField'],
            $this->menuPage,
            'rrze-settings-website-functions-services'
        );

        add_settings_field(
            'disable_emoji',
            __('Emoji Display', 'rrze-settings'),
            [$this, 'emojiField'],
            $this->menuPage,
            'rrze-settings-website-functions-appearance'
        );

        add_settings_field(
            'disable_google_fonts',
            __('Google Fonts', 'rrze-settings'),
            [$this, 'googleFontsField'],
            $this->menuPage,
            'rrze-settings-website-functions-appearance'
        );

        add_settings_field(
            'disable_font_library_admin',
            __('Font Library in Administration', 'rrze-settings'),
            [$this, 'disableFontLibraryAdminField'],
            $this->menuPage,
            'rrze-settings-website-functions-appearance'
        );

        add_settings_field(
            'discussion_default_settings',
            __('Comment Defaults', 'rrze-settings'),
            [$this, 'discussionDefaultSettingsField'],
            $this->menuPage,
            'rrze-settings-website-functions-discussion-section'
        );

        add_settings_field(
            'discussion_disable_avatars',
            __('Avatar Display', 'rrze-settings'),
            [$this, 'discussionDisableAvatarsField'],
            $this->menuPage,
            'rrze-settings-website-functions-discussion-section'
        );

        add_settings_field(
            'disable_delete_site',
            __('Website Deletion', 'rrze-settings'),
            [$this, 'deleteSiteField'],
            $this->menuPage,
            'rrze-settings-website-functions-management'
        );

        add_settings_field(
            'disable_privacy_options',
            __('WordPress Privacy Tools', 'rrze-settings'),
            [$this, 'privacyOptionsField'],
            $this->menuPage,
            'rrze-settings-website-functions-management'
        );

        add_settings_field(
            'disable_admin_email_verification',
            __('Administration Email Reminder', 'rrze-settings'),
            [$this, 'adminEmailVerificationField'],
            $this->menuPage,
            'rrze-settings-website-functions-management'
        );

        add_settings_field(
            'allow_google_notranslate',
            __('Local Translation Preference', 'rrze-settings'),
            [$this, 'allowGoogleNotranslateField'],
            $this->menuPage,
            'rrze-settings-website-functions-metatags-section'
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
            __('Translation in Google Search', 'rrze-settings'),
            '__return_false',
            'reading'
        );

        add_settings_field(
            'rrze-settings-google-notranslate',
            __('Translated Search Results', 'rrze-settings'),
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
     * Explain the scope of the network settings and the local permission.
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        echo '<p>', esc_html(__('Manage the WordPress functions and services available across the network. Most options restrict a function; the Google translation option grants a local choice. Changes apply to all websites after saving.', 'rrze-settings')), '</p>';
    }

    /**
     * Describe the scope of the services section.
     *
     * @return void
     */
    public function servicesSectionDescription(): void
    {
        echo '<p>', esc_html(__('Control access through XML-RPC and the WordPress AI integration. AI functions and access to their settings page can be restricted separately.', 'rrze-settings')), '</p>';
    }

    /**
     * Describe the scope of the appearance section.
     *
     * @return void
     */
    public function appearanceSectionDescription(): void
    {
        echo '<p>', esc_html(__('Manage additional emoji rendering, supported Google Fonts integrations and access to the font management page.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain comment defaults and their persistence in local website options.
     *
     * @return void
     */
    public function discussionSectionDescription(): void
    {
        echo '<p>', esc_html(__('Set comment defaults and control avatar display. Some of these settings also write local website options; switching them off does not restore earlier values.', 'rrze-settings')), '</p>';
    }

    /**
     * Describe the scope of the management section.
     *
     * @return void
     */
    public function managementSectionDescription(): void
    {
        echo '<p>', esc_html(__('Restrict website deletion and WordPress privacy tools, and control the recurring reminder to verify the administration email address.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the network permission for local Google translation preferences.
     *
     * @return void
     */
    public function metatagsSectionDescription(): void
    {
        echo '<p>', esc_html(__('Allow website administrators to decide locally whether Google should offer translated search results for their website.', 'rrze-settings')), '</p>';
    }

    /**
     * Render the disable_delete_site control with its scope and consequences.
     *
     * @return void
     */
    public function deleteSiteField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-delete-site',
            $this->optionName . '[disable_delete_site]',
            $this->siteOptions->tools->disable_delete_site,
            __('Prevent website administrators from deleting their website', 'rrze-settings')
        );
        $this->renderDescription(__('Removes Tools > Delete Site and blocks that deletion path for users without super administrator privileges. Super administrators can still delete websites.', 'rrze-settings'));
        $this->renderSettingDetails(__('Website Deletion', 'rrze-settings'), [
            __('Example: a website administrator can maintain content but cannot delete the entire website through the WordPress deletion workflow.', 'rrze-settings'),
            __('Deleting individual posts, pages or media is not restricted by this option. Switching it off restores the usual website deletion workflow for authorized users.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_privacy_options control with its scope and consequences.
     *
     * @return void
     */
    public function privacyOptionsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-privacy-options',
            $this->optionName . '[disable_privacy_options]',
            $this->siteOptions->tools->disable_privacy_options,
            __('Block privacy settings and personal data tools', 'rrze-settings')
        );
        $this->renderDescription(__('Blocks access to Settings > Privacy and the tools for exporting and erasing personal data. This also applies to super administrators.', 'rrze-settings'));
        $this->renderSettingDetails(__('WordPress Privacy Tools', 'rrze-settings'), [
            __('The existing privacy policy page remains stored and accessible according to its publication status. This option does not delete personal data or replace a privacy policy.', 'rrze-settings'),
            __('Switching this option off restores access according to the normal WordPress permissions. Privacy features provided independently by plugins are not covered by this restriction.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_ai_functionality control with its scope and consequences.
     *
     * @return void
     */
    public function disableAIFunctionalityField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-advanced-disable-ai-functionality',
            $this->optionName . '[disable_ai_functionality]',
            $this->siteOptions->advanced->disable_ai_functionality,
            __('Disable the WordPress AI integration', 'rrze-settings')
        );
        $this->renderDescription(__('Disables AI support and prevents prompts sent through the WordPress AI client. Plugins with their own independent AI connections are not covered.', 'rrze-settings'));
        $this->renderSettingDetails(__('WordPress AI Functions', 'rrze-settings'), [
            __('Applies where WordPress or a plugin uses the WordPress AI integration. It does not remove installed plugins or delete saved provider settings.', 'rrze-settings'),
            __('Access to the AI Connectors settings page is controlled by the separate option below. Hiding that page alone does not disable AI requests.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the hide_ai_connector_page control with its scope and consequences.
     *
     * @return void
     */
    public function hideAIConnectorPageField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-advanced-hide-ai-connector-page',
            $this->optionName . '[hide_ai_connector_page]',
            $this->siteOptions->advanced->hide_ai_connector_page,
            __('Hide the AI Connectors page and block direct access', 'rrze-settings')
        );
        $this->renderDescription(__('Removes the AI Connectors settings page from website administration. Opening its address directly redirects to the dashboard, including for super administrators.', 'rrze-settings'));
        $this->renderSettingDetails(__('AI Connection Settings', 'rrze-settings'), [
            __('This restricts the settings interface. Existing connections and AI requests are not disabled by this option; use WordPress AI Functions for that.', 'rrze-settings'),
            __('Switching this option off allows the page again, provided it exists in the installed WordPress version and the user has the required permissions.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_font_library_admin control with its scope and consequences.
     *
     * @return void
     */
    public function disableFontLibraryAdminField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-advanced-disable-font-library-admin',
            $this->optionName . '[disable_font_library_admin]',
            $this->siteOptions->advanced->disable_font_library_admin,
            __('Hide the font management page and block direct access', 'rrze-settings')
        );
        $this->renderDescription(__('Removes the separate Font Library page under Appearance and blocks direct access. Installed fonts remain available to the website.', 'rrze-settings'));
        $this->renderSettingDetails(__('Font Library in Administration', 'rrze-settings'), [
            __('The font library inside the block editor is controlled separately under CMS > Editor. This setting affects only the separate administration page, including access by super administrators.', 'rrze-settings'),
            __('No font files are deleted. Switching the option off restores access where the page is available and the user has the required permissions.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_xmlrpc control with its scope and consequences.
     *
     * @return void
     */
    public function xmlrpcField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-xmlrpc',
            $this->optionName . '[disable_xmlrpc]',
            $this->siteOptions->general->disable_xmlrpc,
            __('Block the XML-RPC interface', 'rrze-settings')
        );
        $this->renderDescription(__('Blocks requests to xmlrpc.php. External applications that use XML-RPC can no longer access the website through this interface.', 'rrze-settings'));
        $this->renderSettingDetails(__('XML-RPC', 'rrze-settings'), [
            __('Example: publishing software that sends posts through XML-RPC can no longer publish them. Check which interface an integration uses before enabling this setting.', 'rrze-settings'),
            __('The REST API is configured separately under CMS > REST API. Switching this option off removes the XML-RPC block imposed by this plugin.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_admin_email_verification control with its scope and consequences.
     *
     * @return void
     */
    public function adminEmailVerificationField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-admin-email-verification',
            $this->optionName . '[disable_admin_email_verification]',
            $this->siteOptions->general->disable_admin_email_verification,
            __('Skip the recurring administration email check', 'rrze-settings')
        );
        $this->renderDescription(__('Suppresses the recurring prompt after login that asks administrators to confirm the website administration email address.', 'rrze-settings'));
        $this->renderSettingDetails(__('Administration Email Reminder', 'rrze-settings'), [
            __('The saved email address and email delivery remain unchanged. This option does not confirm a newly entered address or disable confirmation when changing the address.', 'rrze-settings'),
            __('Useful when administration email addresses are maintained centrally. Switching this option off restores the regular WordPress reminder.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_emoji control with its scope and consequences.
     *
     * @return void
     */
    public function emojiField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-emoji',
            $this->optionName . '[disable_emoji]',
            $this->siteOptions->general->disable_emoji,
            __('Disable additional WordPress emoji rendering', 'rrze-settings')
        );
        $this->renderDescription(__('Removes WordPress emoji scripts and selected image conversions, including in emails and the classic editor. Emoji characters in saved content remain intact.', 'rrze-settings'));
        $this->renderSettingDetails(__('Emoji Display', 'rrze-settings'), [
            __('Browsers and operating systems can still display emoji using their own fonts. Their appearance may therefore differ between devices.', 'rrze-settings'),
            __('This is not a ban on entering emoji and does not remove images inserted manually. Switching the option off restores the normal WordPress handling.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the disable_google_fonts control with its scope and consequences.
     *
     * @return void
     */
    public function googleFontsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-disable-google-fonts',
            $this->optionName . '[disable_google_fonts]',
            $this->siteOptions->general->disable_google_fonts,
            __('Disable supported Google Fonts integrations', 'rrze-settings')
        );
        $this->renderDescription(__('Disables Google Fonts in supported themes and plugins. Other integrations can still load external fonts; this is not a general block on font requests.', 'rrze-settings'));
        $this->renderSettingDetails(__('Google Fonts', 'rrze-settings'), [
            __('Fonts loaded directly through HTML, CSS or JavaScript can still be requested. Check the actual font requests of the active theme and plugins separately.', 'rrze-settings'),
            __('Locally hosted font files remain available. Where a supported Google Fonts integration is disabled, the resulting appearance depends on the fallback fonts defined by the theme.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the discussion_default_settings control with its scope and consequences.
     *
     * @return void
     */
    public function discussionDefaultSettingsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-discussion-default-settings',
            $this->optionName . '[discussion_default_settings]',
            $this->siteOptions->discussion->default_settings,
            __('Restrict comments and apply closed defaults', 'rrze-settings')
        );
        $this->renderDescription(__('Requires visitors to log in before commenting across the network. New websites start with comments and pingbacks disabled; new posts and pages default to closed comments unless an explicit status is supplied.', 'rrze-settings'));
        $this->renderSettingDetails(__('Comment Defaults', 'rrze-settings'), [
            __('Existing comments remain stored. Existing posts and pages keep their comment status, but visitors who are not logged in can no longer comment. Logged-in users can comment only where comments are open.', 'rrze-settings'),
            __('For newly created websites, outgoing link notifications and incoming pingbacks/trackbacks are disabled by default, and comment registration is enabled. Existing websites do not receive these initial options retroactively.', 'rrze-settings'),
            __('Switching this option off stops enforcing these restrictions. Previously saved website defaults and comment statuses remain stored and may need to be changed locally.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the discussion_disable_avatars control with its scope and consequences.
     *
     * @return void
     */
    public function discussionDisableAvatarsField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-discussion-disable-avatars',
            $this->optionName . '[discussion_disable_avatars]',
            $this->siteOptions->discussion->disable_avatars,
            __('Disable WordPress avatars and hide their settings', 'rrze-settings')
        );
        $this->renderDescription(__('Turns off the local WordPress avatar display option and hides the avatar settings under Settings > Discussion. The default avatar is set to Mystery Person.', 'rrze-settings'));
        $this->renderSettingDetails(__('Avatar Display', 'rrze-settings'), [
            __('Applies as each website loads. Plugins that display profile images independently of the WordPress avatar setting can still show those images.', 'rrze-settings'),
            __('Switching this option off makes the settings available again, but does not restore earlier values. Re-enable avatar display locally if needed.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the allow_google_notranslate control with its scope and consequences.
     *
     * @return void
     */
    public function allowGoogleNotranslateField(): void
    {
        $this->siteOptions = $this->prepareOptionContainers($this->siteOptions);
        $this->renderCheckbox(
            'rrze-settings-allow-google-notranslate',
            $this->optionName . '[allow_google_notranslate]',
            $this->siteOptions->metatags->allow_google_notranslate,
            __('Allow websites to opt out of translation in Google Search', 'rrze-settings')
        );
        $this->renderDescription(__('Adds an option under Settings > Reading on each website. The website administrator must enable it there before this plugin outputs a notranslate instruction for Google.', 'rrze-settings'));
        $this->renderSettingDetails(__('Local Translation Preference', 'rrze-settings'), [
            __('The instruction concerns translation features in Google Search. It is not an access restriction and does not prevent visitors from translating content themselves.', 'rrze-settings'),
            __('When this network permission is off, the local option is hidden and this plugin stops outputting the instruction. Saved local preferences are retained and apply again if permission is restored.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the local translation preference without changing its storage key.
     *
     * @return void
     */
    public function googleNotranslateField(): void
    {
        echo '<div class="rrze-settings-website-functions">';
        echo '<input type="hidden" name="rrze_settings_google_notranslate" value="0">';
        $this->renderCheckbox(
            'rrze-settings-google-notranslate',
            'rrze_settings_google_notranslate',
            (int) get_option('rrze_settings_google_notranslate', 0),
            __('Ask Google not to offer translated search results for this website', 'rrze-settings')
        );
        $this->renderDescription(__('Outputs a notranslate instruction for Google on the public website while the network allows this setting. Visitors can still translate content themselves.', 'rrze-settings'));
        echo '</div>';
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
