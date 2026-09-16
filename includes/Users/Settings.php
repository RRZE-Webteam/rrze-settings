<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the users section of the plugin.
 *
 * @package RRZE\Settings\Users
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-users';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-users-section';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Users', 'rrze-settings'),
            __('Users', 'rrze-settings'),
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
        $this->siteOptions->general->disable_welcome_panel = !empty($input['disable_welcome_panel']) ? 1 : 0;
        $this->siteOptions->general->admin_role_threshold_warning = !empty($input['admin_role_threshold_warning']) ? 1 : 0;
        $this->siteOptions->general->admin_role_threshold_warning_threshold = isset($input['admin_role_threshold_warning_threshold']) ? max(3, (int) $input['admin_role_threshold_warning_threshold']) : 3;

        unset($input['disable_welcome_panel'], $input['admin_role_threshold_warning'], $input['admin_role_threshold_warning_threshold']);

        $input['pages_author_role'] = !empty($input['pages_author_role']) ? 1 : 0;
        $input['super_author_role'] = !empty($input['super_author_role']) ? 1 : 0;
        $input['users_search'] = !empty($input['users_search']) ? 1 : 0;
        $input['contact_page'] = !empty($input['contact_page']) ? 1 : 0;

        $canViewDebugLog = $input['can_view_debug_log'] ?? '';
        $input['can_view_debug_log'] = array_filter(
            array_map(
                'trim',
                explode(PHP_EOL, $canViewDebugLog)
            )
        );

        return $this->parseOptionsValidate($input, 'users');
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
            __('Users', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'pages_author_role',
            __('Pages Author', 'rrze-settings'),
            [$this, 'pagesAuthorRoleField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'super_author_role',
            __('Super Author', 'rrze-settings'),
            [$this, 'superAuthorRoleField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'users_search',
            __('Search', 'rrze-settings'),
            [$this, 'usersSearchField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'contact_page',
            __('Contact Page', 'rrze-settings'),
            [$this, 'contactPageField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'disable_welcome_panel',
            __('Welcome Panel', 'rrze-settings'),
            [$this, 'welcomePanelField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'admin_role_threshold_warning',
            __('Admin Role Threshold Warning', 'rrze-settings'),
            [$this, 'adminRoleThresholdWarningField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'can_view_debug_log',
            __('Can view debug log', 'rrze-settings'),
            [$this, 'canViewDebugLogField'],
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
        esc_html_e('Network administrators can configure user settings across the entire multisite network on this page, enabling special author roles, enhancing user search, generating a virtual contact page, disabling the welcome panel, warning when administrator thresholds are exceeded, and specifying which users have access to debug logs.', 'rrze-settings');
    }

    /**
     * Display the disable_welcome_panel field
     *
     * @return void
     */
    public function welcomePanelField()
    {
        $this->renderCheckbox('rrze-settings-disable-welcome-panel', sprintf('%s[disable_welcome_panel]', $this->optionName), $this->siteOptions->general->disable_welcome_panel, __('Disables the welcome panel that introduces users to WordPress', 'rrze-settings'));
    }

    /**
     * Display the admin_role_threshold_warning field
     *
     * @return void
     */
    public function adminRoleThresholdWarningField()
    {
        $this->renderCheckbox('rrze-settings-admin-role-threshold-warning', sprintf('%s[admin_role_threshold_warning]', $this->optionName), $this->siteOptions->general->admin_role_threshold_warning, __('Enables a warning when the number of administrators exceeds a certain threshold', 'rrze-settings'));
        $this->renderInput('number', 'rrze-settings-admin-role-threshold-warning-threshold', sprintf('%s[admin_role_threshold_warning_threshold]', $this->optionName), $this->siteOptions->general->admin_role_threshold_warning_threshold, 'small-text', ['min' => 3, 'step' => 1]);
    }

    /**
     * Renders the pages author role field
     *
     * @return void
     */
    public function pagesAuthorRoleField()
    {
        $this->renderCheckbox('rrze-settings-pages-author-role', sprintf('%s[pages_author_role]', $this->optionName), $this->siteOptions->users->pages_author_role, __('Enables pages author role', 'rrze-settings'));
    }

    /**
     * Renders the super author role field
     *
     * @return void
     */
    public function superAuthorRoleField()
    {
        $this->renderCheckbox('rrze-settings-super-author-role', sprintf('%s[super_author_role]', $this->optionName), $this->siteOptions->users->super_author_role, __('Enables super author role', 'rrze-settings'));
    }

    /**
     * Renders the users search field
     *
     * @return void
     */
    public function usersSearchField()
    {
        $this->renderCheckbox('rrze-settings-users-search', sprintf('%s[users_search]', $this->optionName), $this->siteOptions->users->users_search, __('Enables enhanced users search', 'rrze-settings'));
    }

    /**
     * Renders the contact page field
     *
     * @return void
     */
    public function contactPageField()
    {
        $this->renderCheckbox('rrze-settings-contact-page', sprintf('%s[contact_page]', $this->optionName), $this->siteOptions->users->contact_page, __('Generate a virtual page (contact) with the contact list (administrators) of the website or blog. If the page already exists, the existing page is displayed.', 'rrze-settings'));
    }

    /**
     * Renders the can view debug log field
     *
     * @return void
     */
    public function canViewDebugLogField()
    {
        $rrzeUsers = implode(PHP_EOL, (array) $this->siteOptions->users->can_view_debug_log);
        $this->renderTextarea('rrze-settings-can-view-debug-log', sprintf('%s[can_view_debug_log]', $this->optionName), $rrzeUsers, 5, 55);
        $this->renderDescription(__('List of users who can view debug information on websites, if any. Enter one user login per line.', 'rrze-settings'));
    }
}
