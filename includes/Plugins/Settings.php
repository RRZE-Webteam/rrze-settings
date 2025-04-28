<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;
use RRZE\Settings\Settings as MainSettings;
use RRZE\Settings\Library\Network\IPUtils;
use RRZE\Settings\Library\Encryption\Encryption;

/**
 * Class Settings
 *
 * This class handles the settings for the plugins section of the plugin.
 *
 * @package RRZE\Settings\Plugins
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-plugins';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Plugins', 'rrze-settings'),
            __('Plugins', 'rrze-settings'),
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
        $input['rrze_newsletter_global_settings'] = !empty($input['rrze_newsletter_global_settings']) ? 1 : 0;

        $input['rrze_newsletter_mail_queue_send_limit'] = isset($input['rrze_newsletter_mail_queue_send_limit']) ? $input['rrze_newsletter_mail_queue_send_limit'] : '';
        $input['rrze_newsletter_mail_queue_send_limit'] = $this->validateIntRange($input['rrze_newsletter_mail_queue_send_limit'], 15, 1, 60);

        $input['rrze_newsletter_mail_queue_max_retries'] = isset($input['rrze_newsletter_mail_queue_max_retries']) ? $input['rrze_newsletter_mail_queue_max_retries'] : '';
        $input['rrze_newsletter_mail_queue_max_retries'] = $this->validateIntRange($input['rrze_newsletter_mail_queue_max_retries'], 1, 1, 10);

        $input['rrze_newsletter_disable_subscription'] = !empty($input['rrze_newsletter_disable_subscription']) ? 1 : 0;

        $input['rrze_newsletter_sender_allowed_domains'] = isset($input['rrze_newsletter_sender_allowed_domains']) ? $input['rrze_newsletter_sender_allowed_domains'] : '';
        $allowedDomains = $this->sanitizeTextarea($input['rrze_newsletter_sender_allowed_domains']);
        $allowedDomains = !empty($allowedDomains) ? $this->sanitizeAllowedDomains($allowedDomains) : '';
        $input['rrze_newsletter_sender_allowed_domains'] = !empty($allowedDomains) ? $allowedDomains : '';

        $input['rrze_newsletter_recipient_allowed_domains'] = isset($input['rrze_newsletter_recipient_allowed_domains']) ? $input['rrze_newsletter_recipient_allowed_domains'] : '';
        $allowedDomains = $this->sanitizeTextarea($input['rrze_newsletter_recipient_allowed_domains']);
        $allowedDomains = !empty($allowedDomains) ? $this->sanitizeAllowedDomains($allowedDomains) : '';
        $input['rrze_newsletter_recipient_allowed_domains'] = !empty($allowedDomains) ? $allowedDomains : '';

        $input['rrze_newsletter_exceptions'] = isset($input['rrze_newsletter_exceptions']) ? $input['rrze_newsletter_exceptions'] : '';
        $exceptions = $this->sanitizeTextarea($input['rrze_newsletter_exceptions']);
        $exceptions = !empty($exceptions) ? $this->sanitizeWebsitesExceptions($exceptions) : '';
        $input['rrze_newsletter_exceptions'] = !empty($exceptions) ? $exceptions : '';

        $input['cms_workflow_not_allowed_post_types'] = isset($input['cms_workflow_not_allowed_post_types']) ? $input['cms_workflow_not_allowed_post_types'] : '';
        $input['cms_workflow_not_allowed_post_types'] = $this->sanitizeTextarea($input['cms_workflow_not_allowed_post_types']);

        $input['siteimprove_crawler_ip_addresses'] = isset($input['siteimprove_crawler_ip_addresses']) ? $input['siteimprove_crawler_ip_addresses'] : '';
        $ipAddresses = $this->sanitizeTextarea($input['siteimprove_crawler_ip_addresses'], false);
        $ipAddresses = !empty($ipAddresses) ? $this->sanitizeIpRange($ipAddresses) : '';
        $input['siteimprove_crawler_ip_addresses'] = !empty($ipAddresses) ? $ipAddresses : '';

        $input['wpseo_disable_metaboxes'] = !empty($input['wpseo_disable_metaboxes']) ? 1 : 0;

        $input['cf7_dequeue'] = !empty($input['cf7_dequeue']) ? 1 : 0;

        $input['the_seo_framework_activate'] = !empty($input['the_seo_framework_activate']) ? 1 : 0;

        $input['ws_form_license_key'] = isset($input['ws_form_license_key']) ? $input['ws_form_license_key'] : '';
        $licenseKey = $this->sanitizeSecureText($input['ws_form_license_key'], $this->siteOptions->plugins->ws_form_license_key);
        $input['ws_form_license_key'] = !empty($licenseKey) ? $licenseKey : '';

        $input['ws_form_action_pdf_license_key'] = isset($input['ws_form_action_pdf_license_key']) ? $input['ws_form_action_pdf_license_key'] : '';
        $pdfLicenseKey = $this->sanitizeSecureText($input['ws_form_action_pdf_license_key'], $this->siteOptions->plugins->ws_form_action_pdf_license_key);
        $input['ws_form_action_pdf_license_key'] = !empty($pdfLicenseKey) ? $pdfLicenseKey : '';

        $input['ws_form_not_allowed_field_types'] = isset($input['ws_form_not_allowed_field_types']) ? $input['ws_form_not_allowed_field_types'] : '';
        $notAllowedFieldTypes = $this->sanitizeTextarea($input['ws_form_not_allowed_field_types']);
        $input['ws_form_not_allowed_field_types'] = !empty($notAllowedFieldTypes) ? $notAllowedFieldTypes : '';

        $input['ws_form_exceptions'] = isset($input['ws_form_exceptions']) ? $input['ws_form_exceptions'] : '';
        $exceptions = $this->sanitizeTextarea($input['ws_form_exceptions']);
        $exceptions = !empty($exceptions) ? $this->sanitizeWebsitesExceptions($exceptions) : '';
        $input['ws_form_exceptions'] = !empty($exceptions) ? $exceptions : '';

        $input['dip_apiKey'] = !empty($input['dip_apiKey']) ? sanitize_text_field($input['dip_apiKey']) : '';

        $input['faudir_public_apiKey'] = !empty($input['faudir_public_apiKey']) ? sanitize_text_field($input['faudir_public_apiKey']) : '';

        $input['bite_api_key'] = !empty($input['bite_api_key']) ? sanitize_text_field($input['bite_api_key']) : '';

        $input['dip_edu_api_key'] = !empty($input['dip_edu_api_key']) ? sanitize_text_field($input['dip_edu_api_key']) : '';

        return $this->parseOptionsValidate($input, 'plugins');
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            'rrze-settings-plugins-main',
            __('Plugins', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        // DIP
        add_settings_section(
            'rrze-settings-plugins-dip',
            __('DIP', 'rrze-settings'),
            '__return_false',
            $this->menuPage
        );

        add_settings_field(
            'dip_apiKey',
            __('DIP API Key', 'rrze-settings'),
            [$this, 'dipAPIField'],
            $this->menuPage,
            'rrze-settings-plugins-dip'
        );

        add_settings_field(
            'dip_edu_api_key',
            __('DIP Edu API key', 'rrze-settings'),
            [$this, 'dipEduApiKeyField'],
            $this->menuPage,
            'rrze-settings-plugins-dip'
        );

        // FAUdir
        if ($this->pluginExists(FAUdir::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-faudir',
                __('FAUdir', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'faudir_public_apiKey',
                __('FAUdir API Key', 'rrze-settings'),
                [$this, 'faudirApiKeyField'],
                $this->menuPage,
                'rrze-settings-plugins-faudir'
            );
        }

        // RRZE Jobs
        if ($this->pluginExists(Jobs::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-rrzejobs',
                __('RRZE Jobs', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'bite_api_key',
                __('B-ITE API Key', 'rrze-settings'),
                [$this, 'biteApiKeyField'],
                $this->menuPage,
                'rrze-settings-plugins-rrzejobs'
            );
        }

        // RRZE Newsletter
        if ($this->pluginExists(Newsletter::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-rrze-newsletter',
                __('RRZE Newsletter', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'rrze_newsletter_global_settings',
                __('Global Settings', 'rrze-settings'),
                [$this, 'newsletterGlobalSettingsField'],
                $this->menuPage,
                'rrze-settings-plugins-rrze-newsletter'
            );

            if ($this->siteOptions->plugins->rrze_newsletter_global_settings) {
                add_settings_field(
                    'rrze_newsletter_exceptions',
                    __('Exceptions', 'rrze-settings'),
                    [$this, 'newsletterExceptionsField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );

                add_settings_field(
                    'rrze_newsletter_sender_allowed_domains',
                    __('Allowed domains for Envelope Sender', 'rrze-settings'),
                    [$this, 'newsletterSenderAllowedDomainsField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );

                add_settings_field(
                    'rrze_newsletter_mail_queue_send_limit',
                    __('Send Limit', 'rrze-settings'),
                    [$this, 'newsletterMailQueueSendLimitField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );

                add_settings_field(
                    'rrze_newsletter_mail_queue_max_retries',
                    __('Max. Retries', 'rrze-settings'),
                    [$this, 'newsletterMaxRetriesField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );

                add_settings_field(
                    'rrze_newsletter_disable_subscription',
                    __('Disable subscription', 'rrze-settings'),
                    [$this, 'newsletterDisableSubscriptionField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );

                add_settings_field(
                    'rrze_newsletter_recipient_allowed_domains',
                    __('Allowed domains for recipients', 'rrze-settings'),
                    [$this, 'newsletterRecipientAllowedDomainsField'],
                    $this->menuPage,
                    'rrze-settings-plugins-rrze-newsletter'
                );
            }
        }

        // RRZE CMS Workflow
        if ($this->pluginExists(CMSWorkflow::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-cms-workflow',
                __('CMS Workflow', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'cms_workflow_not_allowed_post_types',
                __('Not Allowed Post Types', 'rrze-settings'),
                [$this, 'cmsworkflowNotAllowedPostTypesField'],
                $this->menuPage,
                'rrze-settings-plugins-cms-workflow'
            );
        }

        // WS Form
        if ($this->pluginExists(WSForm::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-ws-form',
                __('WS Form', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'ws_form_license_key',
                __('License Key', 'rrze-settings'),
                [$this, 'wsformLicenseKeyField'],
                $this->menuPage,
                'rrze-settings-plugins-ws-form'
            );

            add_settings_field(
                'ws_form_action_pdf_license_key',
                __('PDF Add-On License Key', 'rrze-settings'),
                [$this, 'wsformActionPDFLicenseKeyField'],
                $this->menuPage,
                'rrze-settings-plugins-ws-form'
            );

            add_settings_field(
                'ws_form_not_allowed_field_types',
                __('Not Allowed Field Types', 'rrze-settings'),
                [$this, 'wsformNotAllowedFieldTypesField'],
                $this->menuPage,
                'rrze-settings-plugins-ws-form'
            );

            add_settings_field(
                'ws_form_exceptions',
                __('Exceptions', 'rrze-settings'),
                [$this, 'wsformExceptionsField'],
                $this->menuPage,
                'rrze-settings-plugins-ws-form'
            );
        }

        // Contact Form 7
        if ($this->pluginExists(CF7::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-contact-form-7',
                __('Contact Form 7', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'cf7_dequeue',
                __('Dequeue scripts', 'rrze-settings'),
                [$this, 'cf7DequeueField'],
                $this->menuPage,
                'rrze-settings-plugins-contact-form-7'
            );
        }

        // The SEO Framework
        if ($this->pluginExists(TheSEOFramework::PLUGIN)) {
            add_settings_section(
                'rrze-settings-plugins-the-seo-framework',
                __('SEO Framework', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            add_settings_field(
                'the_seo_framework_activate',
                __('Force activation', 'rrze-settings'),
                [$this, 'theSEOFrameworkActivateField'],
                $this->menuPage,
                'rrze-settings-plugins-the-seo-framework'
            );
        }

        // Siteimprove
        add_settings_section(
            'rrze-settings-plugins-siteimprove',
            __('Siteimprove', 'rrze-settings'),
            '__return_false',
            $this->menuPage
        );

        add_settings_field(
            'siteimprove_crawler_ip_addresses',
            __('Crawler IP addresses', 'rrze-settings'),
            [$this, 'siteimproveCrawlerIpAddressesField'],
            $this->menuPage,
            'rrze-settings-plugins-siteimprove'
        );
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e('Network administrators can oversee and configure plugin settings across a multisite environment. The page automatically detects supported plugins and presents dedicated sections and fields for each one. Network administrators can enter API keys, adjust plugin‑specific options, and maintain efficient, consistent management of all websites in the network.', 'rrze-settings');
    }

    /**
     * Newsletter - Global Settings Field
     */
    public function newsletterGlobalSettingsField()
    {
        echo '<label>';
        echo '<input type="checkbox" id="rrze-settings-rrze-newsletter-global-settings" name="', sprintf('%s[rrze_newsletter_global_settings]', $this->optionName), '" value="1"', checked($this->siteOptions->plugins->rrze_newsletter_global_settings, 1), '>';
        _e('Activate the global settings of the plugin', 'rrze-settings');
        echo '</label>';
    }

    /**
     * Newsletter - Allowed domains for senders
     */
    public function newsletterSenderAllowedDomainsField()
    {
        $option = $this->siteOptions->plugins->rrze_newsletter_sender_allowed_domains;
        echo '<textarea id="rrze-settings-rrze-newsletter-sender-allowed-domains" cols="50" rows="5" name="', sprintf('%s[rrze_newsletter_sender_allowed_domains]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of allowed domains for envelope sender. Enter one domain per line.', 'rrze-settings'), '</p>';
    }

    /**
     * Newsletter - Mail Queue Send Limit Field
     */
    public function newsletterMailQueueSendLimitField()
    {
        echo '<input type="number" class="regular-number" id="rrze-newsletter-mail-queue-send-limit" name="', sprintf('%s[rrze_newsletter_mail_queue_send_limit]', $this->optionName), '" value="', $this->siteOptions->plugins->rrze_newsletter_mail_queue_send_limit, '" placeholder="15" min="1" max="60" step="1">';
        echo '<p class="description">', __('Maximum number of emails that can be sent per minute.', 'rrze-settings'), '</p>';
    }

    /**
     * Newsletter - Mail Queue Max. Retries Field
     */
    public function newsletterMaxRetriesField()
    {
        echo '<input type="number" class="regular-number" id="rrze-newsletter-mail-queue-max-retries" name="', sprintf('%s[rrze_newsletter_mail_queue_max_retries]', $this->optionName), '" value="', $this->siteOptions->plugins->rrze_newsletter_mail_queue_max_retries, '" placeholder="1" min="0" max="10" step="1">';
        echo '<p class="description">', __('Maximum number of retries until an email is sent successfully.', 'rrze-settings'), '</p>';
    }

    /**
     * Newsletter - Disable the subscription (and Mailing Lists)
     */
    public function newsletterDisableSubscriptionField()
    {
        echo '<label>';
        echo '<input type="checkbox" id="rrze-settings-rrze-newsletter-disable-subscription" name="', sprintf('%s[rrze_newsletter_disable_subscription]', $this->optionName), '" value="1"', checked($this->siteOptions->plugins->rrze_newsletter_disable_subscription, 1), '>';
        _e('Disables the subscription and mailing lists', 'rrze-settings');
        echo '</label>';
    }

    /**
     * Newsletter - Allowed domains for recipients
     */
    public function newsletterRecipientAllowedDomainsField()
    {
        $option = $this->siteOptions->plugins->rrze_newsletter_recipient_allowed_domains;
        echo '<textarea id="rrze-settings-rrze-newsletter-recipient-allowed-domains" cols="50" rows="5" name="', sprintf('%s[rrze_newsletter_recipient_allowed_domains]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of allowed domains for recipients. Enter one domain per line.', 'rrze-settings'), '</p>';
    }

    /**
     * Newsletter - Websites that are exempt to all global settings
     */
    public function newsletterExceptionsField()
    {
        $option = $this->siteOptions->plugins->rrze_newsletter_exceptions;
        echo '<textarea id="rrze-newsletter-exceptions" cols="50" rows="5" name="', sprintf('%s[rrze_newsletter_exceptions]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of IDS of websites that are exempt to all global settings. Enter one website ID per line.', 'rrze-settings'), '</p>';
    }

    /**
     * CMS Workflow -  Not allowed post types
     */
    public function cmsworkflowNotAllowedPostTypesField()
    {
        $option = $this->siteOptions->plugins->cms_workflow_not_allowed_post_types;
        echo '<textarea id="rrze-settings-cms-workflow-not-allowed-post-types" cols="50" rows="5" name="', sprintf('%s[cms_workflow_not_allowed_post_types]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of not allowed post types. Enter one post type per line.', 'rrze-settings'), '</p>';
    }

    /**
     * Siteimprove - Crawler allowed IP addresses
     */
    public function siteimproveCrawlerIpAddressesField()
    {
        $option = $this->siteOptions->plugins->siteimprove_crawler_ip_addresses;
        echo '<textarea id="rrze-settings-siteimprove-crawler-ip-addresses" cols="50" rows="5" name="', sprintf('%s[siteimprove_crawler_ip_addresses]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of allowed IP addresses of the Siteimprove crawler in case there are access restrictions to the content of the website. Enter one IP address per line.', 'rrze-settings'), '</p>';
    }

    /**
     * WPSEO - Disable metaboxes
     */
    public function wpseoDisableMetaboxes()
    {
        echo '<label>';
        echo '<input type="checkbox" id="rrze-settings-wpseo-disable-metaboxes" name="', sprintf('%s[wpseo_disable_metaboxes]', $this->optionName), '" value="1"', checked($this->siteOptions->plugins->wpseo_disable_metaboxes, 1), '>';
        _e('Disables custom columns and metaboxes', 'rrze-settings');
        echo '</label>';
    }

    /**
     * CF7 - Dequeue scripts
     */
    public function cf7DequeueField()
    {
        echo '<label>';
        echo '<input type="checkbox" id="rrze-settings-cf7-dequeue" name="', sprintf('%s[cf7_dequeue]', $this->optionName), '" value="1"', checked($this->siteOptions->plugins->cf7_dequeue, 1), '>';
        _e('Dequeue Contact Form 7 scripts on the posts where it is not needed', 'rrze-settings');
        echo '</label>';
    }

    /**
     * SEO Framework - Force activation
     */
    public function theSEOFrameworkActivateField()
    {
        echo '<label>';
        echo '<input type="checkbox" id="rrze-settings-the-seo-framework-activate" name="', sprintf('%s[the_seo_framework_activate]', $this->optionName), '" value="1"', checked($this->siteOptions->plugins->the_seo_framework_activate, 1), '>';
        _e('Forces the activation of the plugin if it exists and has not yet been activated', 'rrze-settings');
        echo '</label>';
    }

    /**
     * WS Form - License Key
     */
    public function wsformLicenseKeyField()
    {
        $value = $this->getValueAttribute($this->siteOptions->plugins->ws_form_license_key);
        echo '<input type="text" id="rrze-settings-ws-form-license-key" name="', sprintf('%s[ws_form_license_key]', $this->optionName), '" value="', $this->maskSecureValues($value), '" class="regular-text">';
        echo '<p class="description">', __('This license key will apply to all websites.', 'rrze-settings'), '</p>';
    }

    /**
     * WS Form - PDF License Key
     */
    public function wsformActionPDFLicenseKeyField()
    {
        $value = $this->getValueAttribute($this->siteOptions->plugins->ws_form_action_pdf_license_key);
        echo '<input type="text" id="rrze-settings-ws-form-action-pdf-license-key" name="', sprintf('%s[ws_form_action_pdf_license_key]', $this->optionName), '" value="', $this->maskSecureValues($value), '" class="regular-text">';
        echo '<p class="description">', __('This add-on license key will apply to all websites.', 'rrze-settings'), '</p>';
    }

    /**
     * WS Form - Not allowed field types
     */
    public function wsformNotAllowedFieldTypesField()
    {
        $option = $this->siteOptions->plugins->ws_form_not_allowed_field_types;
        echo '<textarea id="rrze-settings-ws-form-not-allowed-field-types" cols="50" rows="5" name="', sprintf('%s[ws_form_not_allowed_field_types]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of not allowed field types. Enter one field type per line.', 'rrze-settings'), '</p>';
    }

    /**
     * WS Form - Websites that are exempt to all global settings
     */
    public function wsformExceptionsField()
    {
        $option = $this->siteOptions->plugins->ws_form_exceptions;
        echo '<textarea id="ws-form-exceptions" cols="50" rows="5" name="', sprintf('%s[ws_form_exceptions]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of IDS of websites that are exempt to all global settings. Enter one website ID per line.', 'rrze-settings'), '</p>';
    }

    /**
     * DIP API Key Field
     */
    public function dipAPIField()
    {
        echo '<input type="text" id="rrze-settings-dip-apiKey" name="', sprintf('%s[dip_apiKey]', $this->optionName), '" value="', $this->siteOptions->plugins->dip_apiKey, '" class="regular-text">';
        echo '<p class="description">', __('The DIP API key used by some plugins.', 'rrze-settings'), '</p>';
    }

    /**
     * DIP Edu API Key Field
     */
    public function dipEduApiKeyField()
    {
        echo '<input type="text" id="rrze-settings-dip-edu-api-key" name="', sprintf('%s[dip_edu_api_key]', $this->optionName), '" value="', $this->siteOptions->plugins->dip_edu_api_key, '" class="regular-text">';
        echo '<p class="description">', __('The DIP Edu API key used by some plugins.', 'rrze-settings'), '</p>';
    }

    /**
     * FAUdir - API Key Field
     */
    public function faudirApiKeyField()
    {
        echo '<input type="text" id="rrze-settings-faudir-apiKey" name="', sprintf('%s[faudir_public_apiKey]', $this->optionName), '" value="', $this->siteOptions->plugins->faudir_public_apiKey, '" class="regular-text">';
        echo '<p class="description">', __('The FAUdir API key to access the API service https://api.fau.de/.', 'rrze-settings'), '</p>';
    }

    /**
     * B-ITE API Key Field
     */
    public function biteApiKeyField()
    {
        echo '<input type="text" id="rrze-settings-bite-api-key" name="', sprintf('%s[bite_api_key]', $this->optionName), '" value="', $this->siteOptions->plugins->bite_api_key, '" class="regular-text">';
        echo '<p class="description">', __('The B-ITE API key used for RRZE Jobs.', 'rrze-settings'), '</p>';
    }

    /**
     * getTextarea
     *
     * @param array $option
     * @return string
     */
    protected function getTextarea($option)
    {
        if (!empty($option) && is_array($option)) {
            return implode(PHP_EOL, $option);
        }
        return '';
    }

    /**
     * sanitizeTextarea
     *
     * @param string $input
     * @param boolean $sort
     * @return mixed
     */
    protected function sanitizeTextarea(string $input, bool $sort = true)
    {
        if (!empty($input)) {
            $inputAry = explode(PHP_EOL, sanitize_textarea_field($input));
            $inputAry = array_filter(array_map('trim', $inputAry));
            $inputAry = array_unique(array_values($inputAry));
            if ($sort) sort($inputAry);
            return !empty($inputAry) ? $inputAry : '';
        }
        return '';
    }

    /**
     * Sanitize allowed domains
     * 
     * @param array $domains
     * @return array
     */
    public function sanitizeAllowedDomains(array $domains)
    {
        $allowedDomains = [];
        foreach ($domains as $domain) {
            if (Helper::isValidDomain($domain)) {
                $allowedDomains[$domain] = $domain;
            }
        }
        return $allowedDomains;
    }

    /**
     * Sanitize websites exceptions
     *
     * @param array $sites
     * @return array
     */
    public function sanitizeWebsitesExceptions(array $sites)
    {
        $exceptions = [];
        foreach ($sites as $row) {
            $aryRow = explode(' - ', $row);
            $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
            if (!absint($blogId)) {
                continue;
            }
            switch_to_blog($blogId);
            $url = get_option('siteurl');
            restore_current_blog();
            if (!$url) {
                continue;
            }
            $exceptions[$url] = implode(' - ', [$blogId, $url]);
        }
        ksort($exceptions);
        return $exceptions;
    }

    /**
     * Validate Integer Range
     *
     * @param string $input
     * @param integer $default
     * @param integer $min
     * @param integer $max
     * @param boolean $absint
     * @return integer
     */
    protected function validateIntRange(string $input, int $default, int $min, int $max): int
    {
        $integer = intval($input);
        if (filter_var($integer, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]) === false) {
            return $default;
        } else {
            return $integer;
        }
    }

    /**
     * Sanitize secure text
     *
     * @param string $value
     * @param string $optionValue
     * @return string
     */
    public function sanitizeSecureText($value, $optionValue)
    {
        if (false !== mb_stripos($value, '•••')) {
            $value = $this->getValueAttribute($optionValue);
        }
        $value = sanitize_text_field($value);
        return Encryption::encrypt($value);
    }

    /**
     * Get the value attribute
     *
     * @param string $value
     * @return string
     */
    public function getValueAttribute($value)
    {
        return $value ? Encryption::decrypt($value) : '';
    }

    /**
     * Mask secure values
     *
     * @param string $value Original value.
     * @param string $hint  Number of characters to show.
     * 
     * @return string
     */
    public function maskSecureValues($value, $hint = 6)
    {
        $length = mb_strlen($value);
        if ($length > 0 && $length <= $hint) {
            $value = $this->mbStrPad($value, $length, '•', STR_PAD_LEFT);
        } elseif ($length > $hint) {
            $substr = substr($value, -$hint);
            $value = $this->mbStrPad($substr, $length, '•', STR_PAD_LEFT);
        }
        return $value;
    }

    /**
     * Multibyte String Pad
     *
     * Replaces the mb_str_pad() function that is included in PHP 8 >= PHP 8.3.0
     *
     * @param string $input The string to be padded.
     * @param int $length The length of the resultant padded string.
     * @param string $padding The string to use as padding. Defaults to space.
     * @param int $padType The type of padding. Defaults to STR_PAD_RIGHT.
     * @param string $encoding The encoding to use, defaults to UTF-8.
     *
     * @return string A padded multibyte string.
     */
    public function mbStrPad($input, $length, $padding = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8')
    {
        $result = $input;
        if (($paddingRequired = $length - mb_strlen($input, $encoding)) > 0) {
            switch ($padType) {
                case STR_PAD_LEFT:
                    $result =
                        mb_substr(str_repeat($padding, $paddingRequired), 0, $paddingRequired, $encoding) .
                        $input;
                    break;
                case STR_PAD_RIGHT:
                    $result =
                        $input .
                        mb_substr(str_repeat($padding, $paddingRequired), 0, $paddingRequired, $encoding);
                    break;
                case STR_PAD_BOTH:
                    $leftPaddingLength = floor($paddingRequired / 2);
                    $rightPaddingLength = $paddingRequired - $leftPaddingLength;
                    $result =
                        mb_substr(str_repeat($padding, $leftPaddingLength), 0, $leftPaddingLength, $encoding) .
                        $input .
                        mb_substr(str_repeat($padding, $rightPaddingLength), 0, $rightPaddingLength, $encoding);
                    break;
            }
        }

        return $result;
    }

    /**
     * Sanitize IP Range
     *
     * @param array $ipAddress
     * @return array
     */
    protected function sanitizeIpRange(array $ipAddress)
    {
        $ipRange = [];
        if (!empty($ipAddress)) {
            foreach ($ipAddress as $key => $value) {
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                $sanitizedValue = IPUtils::sanitizeIpRange($value);
                if (!is_null($sanitizedValue)) {
                    $ipRange[] = $sanitizedValue;
                }
            }
        }
        return $ipRange;
    }

    /**
     * Check if plugin is available
     * 
     * @param  string  $plugin Plugin
     * @return boolean True if plugin is available
     */
    protected function pluginExists($plugin)
    {
        return Helper::pluginExists($plugin);
    }
}
