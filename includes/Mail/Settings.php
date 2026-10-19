<?php

namespace RRZE\Settings\Mail;

defined('ABSPATH') || exit;

use RRZE\Settings\Helper;
use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * Manage envelope-sender rules and exceptions to central administration email management.
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
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Email', 'rrze-settings'),
            __('Email', 'rrze-settings'),
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
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getMailSections(): array
    {
        return [
            'rrze-settings-mail-sender-section' => [
                'title' => __('Technical Sender', 'rrze-settings'),
                'callback' => 'senderSectionDescription',
            ],
            'rrze-settings-mail-admin-email-section' => [
                'title' => __('Website Administration Email', 'rrze-settings'),
                'callback' => 'adminEmailSectionDescription',
            ],
        ];
    }

    /**
     * Render the shared email settings form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage(): void
    {
        global $title;

        $sections = $this->getMailSections();
        echo '<div class="wrap rrze-settings-mail-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-mail-index" aria-labelledby="rrze-settings-mail-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-mail-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-mail" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-mail-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-mail-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
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
     * Register sender rules and administration-email exceptions.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        foreach ($this->getMailSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'allowed_domains',
            __('Allowed Sender Domains', 'rrze-settings'),
            [$this, 'allowedDomainsField'],
            $this->menuPage,
            'rrze-settings-mail-sender-section',
            ['label_for' => 'rrze-settings-mail-allowed-domains']
        );

        add_settings_field(
            'sender',
            __('Fallback Address (Envelope Sender)', 'rrze-settings'),
            [$this, 'senderField'],
            $this->menuPage,
            'rrze-settings-mail-sender-section',
            ['label_for' => 'rrze-settings-mail-sender']
        );

        add_settings_field(
            'admin_email_exceptions',
            __('Websites with a Local Administration Email', 'rrze-settings'),
            [$this, 'adminEmailExceptionsField'],
            $this->menuPage,
            'rrze-settings-mail-admin-email-section',
            ['label_for' => 'rrze-settings-mail-admin-email-exceptions']
        );
    }

    /**
     * Introduce the scope of the network email settings.
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        echo '<p>', esc_html(__('Configure technical sender addresses for WordPress emails and exceptions to centrally managed website administration addresses. These settings apply across the network and are saved together.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the purpose and scope of the sender section.
     *
     * @return void
     */
    public function senderSectionDescription(): void
    {
        echo '<p>', esc_html(__('The envelope sender is the technical return address for delivery failures. The visible From address, sender name and Reply-To address are separate. Mail server credentials and transport are configured outside this page.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the purpose and scope of the admin-email section.
     *
     * @return void
     */
    public function adminEmailSectionDescription(): void
    {
        echo '<p>', esc_html(__('The administration email belongs to the website, not to a user profile. Where central website data provides this address, exceptions allow individual websites to manage it locally.', 'rrze-settings')), '</p>';
    }

    /**
     * Render a mail-settings textarea with an associated description.
     *
     * @param string $id Field ID.
     * @param string $key Key in the existing mail option group.
     * @param string $value Formatted current value.
     * @param string $description Translated short description.
     * @return void
     */
    protected function renderMailTextarea(string $id, string $key, string $value, string $description): void
    {
        printf(
            '<textarea id="%1$s" name="%2$s[%3$s]" rows="5" cols="55" spellcheck="false" aria-describedby="%1$s-description">%4$s</textarea>',
            esc_attr($id),
            esc_attr($this->optionName),
            esc_attr($key),
            esc_textarea($value)
        );
        printf('<p class="description" id="%1$s-description">%2$s</p>', esc_attr($id), esc_html($description));
    }

    /**
     * Explain exact-domain matching and the unrestricted empty list.
     *
     * @return void
     */
    public function allowedDomainsField(): void
    {
        $allowedDomains = implode(PHP_EOL, (array) $this->siteOptions->mail->allowed_domains);
        $this->renderMailTextarea(
            'rrze-settings-mail-allowed-domains',
            'allowed_domains',
            $allowedDomains,
            __('Enter one domain per line, for example example.org. A non-empty list restricts the envelope sender to those exact domains. An empty list imposes no domain restriction; it does not block sending.', 'rrze-settings')
        );
        $this->renderSettingDetails(__('Allowed Sender Domains', 'rrze-settings'), [
            __('Use plain domain names without @, protocol, path or wildcard. example.org does not include mail.example.org; add subdomains separately. This list does not restrict recipients or the visible From address.', 'rrze-settings'),
            __('The comparison uses the stored spelling, including case. Use matching lowercase domains in the list and sender addresses. The form checks domain syntax, not DNS records or the ability to receive email.', 'rrze-settings'),
            __('If a sender domain is not allowed, the plugin uses the fallback address instead of cancelling the message. The fallback must also belong to an allowed domain; otherwise saving clears it.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the fallback address and envelope-sender selection order.
     *
     * @return void
     */
    public function senderField(): void
    {
        $this->renderInput('email', 'rrze-settings-mail-sender', $this->optionName . '[sender]', $this->siteOptions->mail->sender, 'regular-text', [
            'aria-describedby' => 'rrze-settings-mail-sender-description',
            'spellcheck' => 'false',
            'autocapitalize' => 'none',
        ]);
        printf('<p id="rrze-settings-mail-sender-description" class="description">%s</p>', esc_html(__('Used when the existing envelope sender, or the website administration email if no sender is set, is invalid or outside the allowed domains. Enter one email address without a display name.', 'rrze-settings')));
        $this->renderSettingDetails(__('Fallback Address (Envelope Sender)', 'rrze-settings'), [
            __('Selection order: keep an existing valid and allowed envelope sender. If it is empty, check the website administration email. If the selected address is invalid or not allowed, use this fallback.', 'rrze-settings'),
            __('Example: with example.org allowed and bounces@example.org as fallback, a sender from external.example is replaced. A sender from example.org remains unchanged.', 'rrze-settings'),
            __('A blank field provides no fallback address. If fallback is needed, the plugin leaves the envelope sender empty; the final behavior then depends on the mail transport. Invalid addresses or addresses outside a non-empty domain list are cleared when saving.', 'rrze-settings'),
            __('This does not change From, Reply-To, recipients or message content. White Label under RRZE Settings > General can separately change the visible From address and name. Other mail plugins or the mail server can influence the final result.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain website exceptions and their central-data dependency.
     *
     * @return void
     */
    public function adminEmailExceptionsField(): void
    {
        $this->renderMailTextarea(
            'rrze-settings-mail-admin-email-exceptions',
            'admin_email_exceptions',
            $this->getTextarea($this->siteOptions->mail->admin_email_exceptions),
            __('Enter one website ID per line, for example 12. These websites are exempt from central administration-email management by this module. Existing WordPress permissions still apply.', 'rrze-settings')
        );
        $this->renderSettingDetails(__('Websites with a Local Administration Email', 'rrze-settings'), [
            __('This applies where an active central website record supplies site_email. Without a matching record or with an empty central address, this module does not impose central email management.', 'rrze-settings'),
            __('For an exception, the module stops overwriting the address and removes its restriction on editing it under Settings > General. The normal WordPress change and confirmation process applies. User-profile email addresses and the network administration email are separate.', 'rrze-settings'),
            __('Find website IDs in the network website list or in the id parameter of a website edit URL. After saving, recognized websites appear as ID - URL and are sorted by URL. Remove the entire line to remove an exception.', 'rrze-settings'),
            __('Removing an exception allows a valid central address to replace the local address on a subsequent administration request. An empty list makes no explicit exceptions. Sender-domain rules above continue to apply to all websites.', 'rrze-settings'),
        ]);
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
