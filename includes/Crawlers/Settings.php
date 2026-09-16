<?php

namespace RRZE\Settings\Crawlers;

defined('ABSPATH') || exit;

use RRZE\Settings\Options;
use RRZE\Settings\Settings as MainSettings;

/**
 * Crawler directory settings.
 *
 * @package RRZE\Settings\Crawlers
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug.
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-crawlers';

    /**
     * Items per page in the crawler list.
     *
     * @var int
     */
    protected $itemsPerPage = 20;

    /**
     * Validation errors for the current request.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Submitted crawler data for redisplay after validation errors.
     *
     * @var array
     */
    protected $submittedCrawler = [];

    /**
     * Adds a submenu page to the network admin menu.
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Crawlers', 'rrze-settings'),
            __('Crawlers', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            [$this, 'optionsPage']
        );
    }

    /**
     * Render options page.
     *
     * @return void
     */
    public function optionsPage()
    {
        $this->processDeleteRequest();

        echo '<div class="wrap rrze-settings rrze-settings-crawlers-page">';

        if ($this->isFormAction()) {
            $this->renderFormPage();
        } else {
            $this->renderListPage();
        }

        $this->printDeleteScript();
        echo '</div>';
    }

    /**
     * Validate crawler options.
     *
     * @param array $input
     * @return object
     */
    public function optionsValidate($input)
    {
        $input = is_array($input) ? wp_unslash($input) : [];
        $crawlerInput = isset($input['crawler']) && is_array($input['crawler']) ? $input['crawler'] : [];
        $this->submittedCrawler = $crawlerInput;
        $entries = Defaults::getEntries($this->siteOptions);
        $originalKey = sanitize_key((string) ($crawlerInput['original_key'] ?? ''));
        $key = sanitize_key((string) ($crawlerInput['key'] ?? ''));
        $title = sanitize_text_field((string) ($crawlerInput['title'] ?? ''));

        if ($key === '') {
            $key = sanitize_key(sanitize_title($title));
            $this->submittedCrawler['key'] = $key;
        }

        if ($key === '') {
            $this->errors[] = __('Enter a name or Id for the crawler.', 'rrze-settings');
            return $this->siteOptions;
        }

        if ($originalKey !== '' && $originalKey !== $key && !Defaults::isDefaultCrawler($originalKey)) {
            unset($entries[$originalKey]);
        }

        if ($originalKey !== '' && Defaults::isDefaultCrawler($originalKey)) {
            $key = $originalKey;
        }

        if ($originalKey !== $key && isset($entries[$key])) {
            $this->errors[] = __('A crawler with this Id already exists.', 'rrze-settings');
            return $this->siteOptions;
        }

        $contactEmail = trim((string) ($crawlerInput['contact_email'] ?? ''));
        if ($contactEmail !== '' && !is_email($contactEmail)) {
            $this->errors[] = __('Enter a valid contact email address.', 'rrze-settings');
        }

        $contactUrl = trim((string) ($crawlerInput['contact_url'] ?? ''));
        if ($contactUrl !== '' && !$this->isValidContactUrl($contactUrl)) {
            $this->errors[] = __('Enter a valid contact website URL.', 'rrze-settings');
        }

        $ipValidation = $this->validateIpAddresses($crawlerInput['ip_addresses'] ?? '');
        if (!empty($ipValidation['invalid'])) {
            $this->errors[] = sprintf(
                /* translators: %s: Invalid IP addresses or ranges. */
                __('Invalid IP addresses or ranges: %s', 'rrze-settings'),
                implode(', ', $ipValidation['invalid'])
            );
        }

        if (!empty($this->errors)) {
            return $this->siteOptions;
        }

        $crawler = Defaults::normalizeCrawler($key, [
            'title' => $title,
            'user_agent' => $crawlerInput['user_agent'] ?? '',
            'ip_addresses' => $ipValidation['valid'],
            'contact_email' => $crawlerInput['contact_email'] ?? '',
            'contact_url' => $crawlerInput['contact_url'] ?? '',
            'notes' => $crawlerInput['notes'] ?? '',
        ]);

        if ($this->isEmptyCrawler($crawler)) {
            $this->errors[] = __('Enter crawler data before saving.', 'rrze-settings');
            return $this->siteOptions;
        }

        $entries[$key] = $crawler;
        uasort($entries, [$this, 'sortByTitle']);

        $input = [
            'entries' => $entries,
        ];

        $options = $this->parseOptionsValidate($input, 'crawlers');
        $options->plugins->siteimprove_crawler_ip_addresses = Defaults::getIpAddresses($options, 'siteimprove');

        return $options;
    }

    /**
     * Settings update.
     *
     * @return void
     */
    public function settingsUpdate()
    {
        if (!is_network_admin() || !isset($_POST[$this->menuPage . '-submit-primary'])) {
            return;
        }

        check_admin_referer($this->menuPage . '-options');

        $input = isset($_POST[$this->optionName]) ? $_POST[$this->optionName] : [];
        $siteOptions = $this->optionsValidate($input);
        if (!is_object($siteOptions) || !empty($this->errors)) {
            return;
        }

        update_site_option($this->optionName, $siteOptions);
        $this->siteOptions = Options::getSiteOptions();

        wp_safe_redirect(add_query_arg('crawler-updated', '1', $this->getListUrl()));
        exit;
    }

    /**
     * Add settings fields.
     *
     * @return void
     */
    public function networkAdminPage()
    {
        // The crawler page renders its own list and edit form.
    }

    /**
     * Render crawler list page.
     *
     * @return void
     */
    protected function renderListPage()
    {
        echo '<h1 class="wp-heading-inline">', esc_html__('Crawlers', 'rrze-settings'), '</h1>';
        echo ' <a href="', esc_url($this->getAddUrl()), '" class="page-title-action">', esc_html__('Add crawler', 'rrze-settings'), '</a>';
        echo '<hr class="wp-header-end">';
        $this->renderSuccessNotice();
        echo '<p>', esc_html__('Network administrators can maintain known crawlers and their identifying data across the multisite network.', 'rrze-settings'), '</p>';

        $this->renderCrawlerTable();
    }

    /**
     * Render crawler table.
     *
     * @return void
     */
    protected function renderCrawlerTable()
    {
        $entries = Defaults::getEntries($this->siteOptions);
        $totalItems = count($entries);
        $currentPage = $this->getCurrentPage();
        $offset = ($currentPage - 1) * $this->itemsPerPage;
        $pagedEntries = array_slice($entries, $offset, $this->itemsPerPage, true);

        echo '<table class="wp-list-table widefat fixed striped table-view-list rrze-settings-crawlers-table">';
        echo '<thead><tr>';
        echo '<th scope="col" class="manage-column column-primary column-name">', esc_html__('Name', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('UserAgent', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('IP addresses', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('Contact', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('Comment', 'rrze-settings'), '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($pagedEntries)) {
            echo '<tr class="no-items"><td class="colspanchange" colspan="5">', esc_html__('No crawlers found.', 'rrze-settings'), '</td></tr>';
        }

        foreach ($pagedEntries as $key => $crawler) {
            $this->renderCrawlerTableRow((string) $key, $crawler);
        }

        echo '</tbody>';
        echo '<tfoot><tr>';
        echo '<th scope="col" class="manage-column column-primary column-name">', esc_html__('Name', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('UserAgent', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('IP addresses', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('Contact', 'rrze-settings'), '</th>';
        echo '<th scope="col" class="manage-column">', esc_html__('Comment', 'rrze-settings'), '</th>';
        echo '</tr></tfoot>';
        echo '</table>';

        $this->renderPagination($totalItems, $currentPage);
    }

    /**
     * Render one crawler table row.
     *
     * @param string $key
     * @param array $crawler
     * @return void
     */
    protected function renderCrawlerTableRow(string $key, array $crawler)
    {
        $editUrl = $this->getEditUrl($key);
        $rowActions = [
            'edit' => sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url($editUrl),
                esc_html__('Edit', 'rrze-settings')
            ),
        ];

        if (!Defaults::isDefaultCrawler($key)) {
            $rowActions['delete'] = $this->getDeleteForm($key);
        }

        echo '<tr>';
        echo '<td class="column-primary column-name has-row-actions">';
        echo '<strong><a class="row-title" href="', esc_url($editUrl), '">', esc_html($crawler['title'] ?? $key), '</a></strong>';
        echo '<div class="row-actions">', wp_kses_post($this->getRowActions($rowActions)), '</div>';
        echo '<button type="button" class="toggle-row"><span class="screen-reader-text">', esc_html__('Show more details', 'rrze-settings'), '</span></button>';
        echo '</td>';
        echo '<td>', esc_html($crawler['user_agent'] ?? ''), '</td>';
        echo '<td>', wp_kses_post($this->getIpSummary($crawler)), '</td>';
        echo '<td>', wp_kses_post($this->getContactSummary($crawler)), '</td>';
        echo '<td>', nl2br(esc_html($crawler['notes'] ?? '')), '</td>';
        echo '</tr>';
    }

    /**
     * Render add or edit form page.
     *
     * @return void
     */
    protected function renderFormPage()
    {
        $key = $this->getRequestedCrawlerKey();
        $isEdit = $key !== '';
        $crawler = $isEdit ? Defaults::getCrawler($this->siteOptions, $key) : [];
        if (!empty($this->errors) && !empty($this->submittedCrawler)) {
            $crawler = $this->submittedCrawler;
            $key = sanitize_key((string) ($crawler['original_key'] ?? $key));
        }

        if ($isEdit && empty($crawler)) {
            echo '<h1>', esc_html__('Crawler not found', 'rrze-settings'), '</h1>';
            echo '<p><a href="', esc_url($this->getListUrl()), '">', esc_html__('Back to crawler list', 'rrze-settings'), '</a></p>';
            return;
        }

        echo '<h1>', esc_html($isEdit ? __('Edit crawler', 'rrze-settings') : __('Add crawler', 'rrze-settings')), '</h1>';
        $this->renderErrors();
        echo '<form method="post">';
        settings_fields($this->menuPage);
        echo '<input type="hidden" name="', esc_attr($this->optionName), '[crawler][original_key]" value="', esc_attr($key), '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderTextField('key', __('Id', 'rrze-settings'), $crawler['key'] ?? $key, Defaults::isDefaultCrawler($key));
        $this->renderTextField('title', __('Name', 'rrze-settings'), $crawler['title'] ?? '');
        $this->renderTextField('user_agent', __('UserAgent', 'rrze-settings'), $crawler['user_agent'] ?? '', false, 'text', __('Fixed user agent string or identifying part. Version numbers may differ.', 'rrze-settings'));
        $this->renderTextareaField('ip_addresses', __('IP addresses', 'rrze-settings'), $this->getTextareaValue($crawler['ip_addresses'] ?? []), __('IP addresses support IPv4, IPv6, CIDR notation, wildcards, and partial prefixes such as 131.188. or 2001:db8:. Enter one IP address or range per line.', 'rrze-settings'));
        $this->renderTextField('contact_email', __('Contact email', 'rrze-settings'), $crawler['contact_email'] ?? '', false, 'email');
        $this->renderTextField('contact_url', __('Contact website', 'rrze-settings'), $crawler['contact_url'] ?? '', false, 'url');
        $this->renderTextareaField('notes', __('Comment', 'rrze-settings'), $crawler['notes'] ?? '');
        echo '</tbody></table>';
        submit_button($isEdit ? __('Update crawler', 'rrze-settings') : __('Add crawler', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo ' <a href="', esc_url($this->getListUrl()), '" class="button">', esc_html__('Cancel', 'rrze-settings'), '</a>';
        echo '</form>';
    }

    /**
     * Render text input field.
     *
     * @param string $field
     * @param string $label
     * @param string $value
     * @param bool $readonly
     * @param string $type
     * @param string $description
     * @return void
     */
    protected function renderTextField(string $field, string $label, string $value, bool $readonly = false, string $type = 'text', string $description = '')
    {
        $id = 'rrze-settings-crawler-' . str_replace('_', '-', $field);

        echo '<tr>';
        echo '<th scope="row"><label for="', esc_attr($id), '">', esc_html($label), '</label></th>';
        echo '<td>';
        echo '<input type="', esc_attr($type), '" id="', esc_attr($id), '" class="regular-text" name="', esc_attr($this->getCrawlerFieldName($field)), '" value="', esc_attr($value), '"', $readonly ? ' readonly' : '', '>';

        if ($field === 'key') {
            echo '<p class="description">', esc_html__('Internal unique crawler Id. Use lowercase letters, numbers, underscores, and hyphens.', 'rrze-settings'), '</p>';
        }

        if ($description !== '') {
            echo '<p class="description">', esc_html($description), '</p>';
        }

        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render textarea field.
     *
     * @param string $field
     * @param string $label
     * @param string $value
     * @param string $description
     * @return void
     */
    protected function renderTextareaField(string $field, string $label, string $value, string $description = '')
    {
        $id = 'rrze-settings-crawler-' . str_replace('_', '-', $field);

        echo '<tr>';
        echo '<th scope="row"><label for="', esc_attr($id), '">', esc_html($label), '</label></th>';
        echo '<td>';
        echo '<textarea id="', esc_attr($id), '" class="large-text" rows="6" name="', esc_attr($this->getCrawlerFieldName($field)), '">', esc_textarea($value), '</textarea>';

        if ($description !== '') {
            echo '<p class="description">', esc_html($description), '</p>';
        }

        echo '</td>';
        echo '</tr>';
    }

    /**
     * Process a delete request.
     *
     * @return void
     */
    protected function processDeleteRequest()
    {
        if (empty($_POST['rrze_settings_crawler_delete'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to delete this crawler.', 'rrze-settings'));
        }

        check_admin_referer('rrze_settings_delete_crawler');

        $key = isset($_POST['crawler_key']) ? sanitize_key(wp_unslash($_POST['crawler_key'])) : '';
        if ($key === '' || Defaults::isDefaultCrawler($key)) {
            return;
        }

        $rawOptions = get_site_option(Options::OPTION_NAME);
        $rawOptions = is_array($rawOptions) || is_object($rawOptions) ? (array) $rawOptions : [];
        $options = Options::parseOptions($rawOptions);
        $entries = Defaults::getEntries($options);
        unset($entries[$key]);

        $options->crawlers = Defaults::normalizeDirectory(['entries' => $entries]);
        $options->plugins->siteimprove_crawler_ip_addresses = Defaults::getIpAddresses($options, 'siteimprove');
        update_site_option(Options::OPTION_NAME, $options);
        $this->siteOptions = Options::getSiteOptions();

        echo '<div class="notice notice-success is-dismissible"><p>', esc_html__('Crawler deleted.', 'rrze-settings'), '</p></div>';
    }

    /**
     * Render validation errors.
     *
     * @return void
     */
    protected function renderErrors()
    {
        if (empty($this->errors)) {
            return;
        }

        echo '<div class="notice notice-error"><ul>';
        foreach ($this->errors as $error) {
            echo '<li>', esc_html($error), '</li>';
        }
        echo '</ul></div>';
    }

    /**
     * Render success notice after redirect.
     *
     * @return void
     */
    protected function renderSuccessNotice()
    {
        if (empty($_GET['crawler-updated'])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>', esc_html__('Crawler saved.', 'rrze-settings'), '</p></div>';
    }

    /**
     * Validate IP address ranges.
     *
     * @param mixed $ipAddresses
     * @return array
     */
    protected function validateIpAddresses($ipAddresses): array
    {
        if (is_string($ipAddresses)) {
            $ipAddresses = preg_split('/\R/', $ipAddresses);
        }

        if (!is_array($ipAddresses)) {
            return [
                'valid' => [],
                'invalid' => [],
            ];
        }

        $valid = [];
        $invalid = [];
        foreach ($ipAddresses as $ipAddress) {
            $ipAddress = trim((string) $ipAddress);
            if ($ipAddress === '') {
                continue;
            }

            $sanitizedIpAddress = Defaults::sanitizeIpAddresses([$ipAddress]);
            if (empty($sanitizedIpAddress)) {
                $invalid[] = $ipAddress;
                continue;
            }

            $valid[] = $sanitizedIpAddress[0];
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => array_values(array_unique($invalid)),
        ];
    }

    /**
     * Check whether a contact URL is valid.
     *
     * @param string $url
     * @return bool
     */
    protected function isValidContactUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = (string) parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * Print delete confirmation script.
     *
     * @return void
     */
    protected function printDeleteScript()
    {
        ?>
        <script>
        function rrzeSettingsConfirmCrawlerDelete(event) {
            var message = event.currentTarget.getAttribute('data-confirm-message');

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        }

        function rrzeSettingsCrawlerDeleteInit() {
            document.querySelectorAll('[data-rrze-settings-confirm-delete]').forEach(function rrzeSettingsBindCrawlerDelete(form) {
                form.addEventListener('submit', rrzeSettingsConfirmCrawlerDelete);
            });
        }

        document.addEventListener('DOMContentLoaded', rrzeSettingsCrawlerDeleteInit);
        </script>
        <?php
    }

    /**
     * Get row actions HTML.
     *
     * @param array $actions
     * @return string
     */
    protected function getRowActions(array $actions): string
    {
        $output = [];
        foreach ($actions as $class => $action) {
            $output[] = '<span class="' . esc_attr($class) . '">' . $action . '</span>';
        }

        return implode(' | ', $output);
    }

    /**
     * Get delete form HTML.
     *
     * @param string $key
     * @return string
     */
    protected function getDeleteForm(string $key): string
    {
        $form = '<form method="post" class="rrze-settings-inline-delete-form" data-rrze-settings-confirm-delete data-confirm-message="' . esc_attr__('Do you really want to delete this crawler?', 'rrze-settings') . '">';
        $form .= wp_nonce_field('rrze_settings_delete_crawler', '_wpnonce', true, false);
        $form .= '<input type="hidden" name="crawler_key" value="' . esc_attr($key) . '">';
        $form .= '<button type="submit" class="button-link delete" name="rrze_settings_crawler_delete" value="1">' . esc_html__('Delete', 'rrze-settings') . '</button>';
        $form .= '</form>';

        return $form;
    }

    /**
     * Render pagination.
     *
     * @param int $totalItems
     * @param int $currentPage
     * @return void
     */
    protected function renderPagination(int $totalItems, int $currentPage)
    {
        $totalPages = (int) ceil($totalItems / $this->itemsPerPage);
        if ($totalPages <= 1) {
            return;
        }

        $links = paginate_links([
            'base' => add_query_arg('paged', '%#%', $this->getListUrl()),
            'format' => '',
            'current' => $currentPage,
            'total' => $totalPages,
            'type' => 'array',
            'prev_text' => __('Previous page', 'rrze-settings'),
            'next_text' => __('Next page', 'rrze-settings'),
        ]);

        if (empty($links) || !is_array($links)) {
            return;
        }

        echo '<div class="tablenav bottom"><div class="tablenav-pages">';
        /* translators: %s: Number of crawlers. */
        echo '<span class="displaying-num">', esc_html(sprintf(_n('%s item', '%s items', $totalItems, 'rrze-settings'), number_format_i18n($totalItems))), '</span>';
        echo '<span class="pagination-links">', wp_kses_post(implode('', $links)), '</span>';
        echo '</div></div>';
    }

    /**
     * Get IP summary.
     *
     * @param array $crawler
     * @return string
     */
    protected function getIpSummary(array $crawler): string
    {
        $ipAddresses = !empty($crawler['ip_addresses']) && is_array($crawler['ip_addresses']) ? $crawler['ip_addresses'] : [];
        $count = count($ipAddresses);

        if ($count === 0) {
            return esc_html__('None', 'rrze-settings');
        }

        $visibleIpAddresses = array_slice($ipAddresses, 0, 5);
        $output = [];
        foreach ($visibleIpAddresses as $ipAddress) {
            $output[] = '<code>' . esc_html($ipAddress) . '</code>';
        }

        if ($count > count($visibleIpAddresses)) {
            $remaining = $count - count($visibleIpAddresses);
            $output[] = esc_html(
                sprintf(
                    /* translators: %s: Number of hidden IP addresses. */
                    _n('%s more IP address', '%s more IP addresses', $remaining, 'rrze-settings'),
                    number_format_i18n($remaining)
                )
            );
        }

        return implode('<br>', $output);
    }

    /**
     * Get contact summary.
     *
     * @param array $crawler
     * @return string
     */
    protected function getContactSummary(array $crawler): string
    {
        $parts = [];
        $email = $crawler['contact_email'] ?? '';
        $url = $crawler['contact_url'] ?? '';

        if ($email !== '') {
            $parts[] = sprintf('<a href="mailto:%1$s">%2$s</a>', esc_attr($email), esc_html($email));
        }

        if ($url !== '') {
            $parts[] = sprintf('<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url($url), esc_html($url));
        }

        return implode('<br>', $parts);
    }

    /**
     * Get textarea value.
     *
     * @param mixed $value
     * @return string
     */
    protected function getTextareaValue($value): string
    {
        if (is_array($value)) {
            return implode(PHP_EOL, $value);
        }

        return (string) $value;
    }

    /**
     * Get crawler field name.
     *
     * @param string $field
     * @return string
     */
    protected function getCrawlerFieldName(string $field): string
    {
        return sprintf('%s[crawler][%s]', $this->optionName, $field);
    }

    /**
     * Check whether the page is in add or edit mode.
     *
     * @return bool
     */
    protected function isFormAction(): bool
    {
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

        return in_array($action, ['add', 'edit'], true);
    }

    /**
     * Get requested crawler key.
     *
     * @return string
     */
    protected function getRequestedCrawlerKey(): string
    {
        return isset($_GET['crawler']) ? sanitize_key(wp_unslash($_GET['crawler'])) : '';
    }

    /**
     * Get current pagination page.
     *
     * @return int
     */
    protected function getCurrentPage(): int
    {
        $currentPage = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

        return max(1, $currentPage);
    }

    /**
     * Get list URL.
     *
     * @return string
     */
    protected function getListUrl(): string
    {
        return network_admin_url('admin.php?page=' . $this->menuPage);
    }

    /**
     * Get add URL.
     *
     * @return string
     */
    protected function getAddUrl(): string
    {
        return add_query_arg('action', 'add', $this->getListUrl());
    }

    /**
     * Get edit URL.
     *
     * @param string $key
     * @return string
     */
    protected function getEditUrl(string $key): string
    {
        return add_query_arg(
            [
                'action' => 'edit',
                'crawler' => sanitize_key($key),
            ],
            $this->getListUrl()
        );
    }

    /**
     * Check whether a crawler row has no relevant data.
     *
     * @param array $crawler
     * @return bool
     */
    protected function isEmptyCrawler(array $crawler): bool
    {
        return $crawler['title'] === ''
            && $crawler['user_agent'] === ''
            && empty($crawler['ip_addresses'])
            && $crawler['contact_email'] === ''
            && $crawler['contact_url'] === ''
            && $crawler['notes'] === '';
    }

    /**
     * Sort crawlers by title.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByTitle(array $a, array $b): int
    {
        return strnatcasecmp($a['title'] ?? '', $b['title'] ?? '');
    }
}
