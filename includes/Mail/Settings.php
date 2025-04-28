<?php

namespace RRZE\Settings\Mail;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;
use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the mail section of the plugin.
 *
 * @package RRZE\Settings\Mail
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-mail';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-mail-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Mail', 'rrze-settings'),
            __('Mail', 'rrze-settings'),
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
        $allowedDomainsFilter = array_filter(
            array_map(
                'trim',
                explode(PHP_EOL, $input['allowed_domains'])
            )
        );
        $allowedDomains = $this->defaultOptions->mail->allowed_domains;
        foreach ($allowedDomainsFilter as $domain) {
            if (Helper::isValidDomain($domain)) {
                $allowedDomains[$domain] = $domain;
            }
        }
        $input['allowed_domains'] = $allowedDomains;

        $sender = sanitize_text_field($input['sender']);
        $parts = explode('@', $sender);
        $domain = array_pop($parts);
        if (
            !filter_var($sender, FILTER_VALIDATE_EMAIL)
            || (!empty($allowedDomains) && !in_array($domain, $allowedDomains))
        ) {
            $sender = '';
        }
        $input['sender'] = $sender;

        $input['admin_email_exceptions'] = isset($input['admin_email_exceptions']) ? $input['admin_email_exceptions'] : '';
        $exceptions = $this->sanitizeTextarea($input['admin_email_exceptions']);
        $exceptions = !empty($exceptions) ? $this->sanitizeAdminEmailExceptions($exceptions) : '';
        $input['admin_email_exceptions'] = !empty($exceptions) ? $exceptions : '';

        return $this->parseOptionsValidate($input, 'mail');
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
            __('Mail', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'sender',
            __('Envelope Sender', 'rrze-settings'),
            [$this, 'senderField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'allowed_domains',
            __('Allowed Domains', 'rrze-settings'),
            [$this, 'allowedDomainsField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'admin_email_exceptions',
            __('Admin Email Exceptions', 'rrze-settings'),
            [$this, 'adminEmailExceptionsField'],
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
        esc_html_e('Centralized mail management for the multisite network enables defining the default envelope sender address, specifying permitted sender domains, and setting exceptions for editing admin email addresses—guaranteeing consistent, secure email delivery across all websites.', 'rrze-settings');
    }

    /**
     * Renders the sender field
     * 
     * @return void
     */
    public function senderField()
    {
        echo '<input type="text" id="rrze-settings-mail-sender" name="', sprintf('%s[sender]', $this->optionName), '" value="', $this->siteOptions->mail->sender, '" class="regular-text">';
        echo '<p class="description">', __('Set the default envelope sender.', 'rrze-settings'), '</p>';
    }

    /**
     * Renders the allowed domains field
     * 
     * @return void
     */
    public function allowedDomainsField()
    {
        $allowedDomains = implode(PHP_EOL, (array) $this->siteOptions->mail->allowed_domains);
        echo '<textarea rows="5" cols="55" id="rrze-settings-mail-allowed-domains" class="regular-text" name="', sprintf('%s[allowed_domains]', $this->optionName), '">', esc_attr($allowedDomains), '</textarea>';
        echo '<p class="description">' . __('List of allowed domains for email addresses used as envelope sender.', 'rrze-settings') . '</p>';
    }

    /**
     * Renders the admin email exceptions field
     * 
     * @return void
     */
    public function adminEmailExceptionsField()
    {
        $option = $this->siteOptions->mail->admin_email_exceptions;
        echo '<textarea id="rrze-settings-mail-admin-email-exceptions" cols="50" rows="5" name="', sprintf('%s[admin_email_exceptions]', $this->optionName), '">', esc_attr($this->getTextarea($option)), '</textarea>';
        echo '<p class="description">', __('List of website IDs that can edit the admin_email field. Enter one website ID per line.', 'rrze-settings'), '</p>';
    }

    /**
     * Get textarea value
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
     * Sanitize textarea
     *
     * @param string $input
     * @param bool $sort
     * @return array|string
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
     * Sanitize admin email exceptions
     *
     * @param array $sites
     * @return array
     */
    public function sanitizeAdminEmailExceptions(array $sites)
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
}
