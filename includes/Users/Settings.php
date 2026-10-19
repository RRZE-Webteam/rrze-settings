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
            $this->menuPage,
            [
                'before_section' => '<div class="rrze-settings-users">',
                'after_section' => '</div>',
            ]
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
            __('User Search', 'rrze-settings'),
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
            __('Debug Log Access', 'rrze-settings'),
            [$this, 'canViewDebugLogField'],
            $this->menuPage,
            $this->sectionName,
            ['label_for' => 'rrze-settings-can-view-debug-log']
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
        esc_html_e('Manage additional author roles, user search, contact information and dashboard guidance across the network. You can also configure administrator warnings and access to debug information. Changes take effect after saving.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Display the welcome panel control and distinguish it from other dashboard widgets.
     *
     * @return void
     */
    public function welcomePanelField()
    {
        $this->renderCheckbox('rrze-settings-disable-welcome-panel', sprintf('%s[disable_welcome_panel]', $this->optionName), $this->siteOptions->general->disable_welcome_panel, __('Hide the WordPress welcome panel', 'rrze-settings'));
        $this->renderDescription(__('Removes the WordPress introduction panel from website dashboards. Other dashboard widgets remain available.', 'rrze-settings'));
        $this->renderSettingDetails(__('Welcome Panel', 'rrze-settings'), [
            __('Useful when users receive their introduction through your own training or documentation. This option does not change user roles or permissions.', 'rrze-settings'),
            __('Disabled: WordPress controls the welcome panel again. Whether an individual user sees it also depends on their permissions and personal display settings.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the administrator warning with a labelled threshold and its limitations.
     *
     * @return void
     */
    public function adminRoleThresholdWarningField()
    {
        $this->renderCheckbox('rrze-settings-admin-role-threshold-warning', sprintf('%s[admin_role_threshold_warning]', $this->optionName), $this->siteOptions->general->admin_role_threshold_warning, __('Ask for confirmation before assigning more administrators', 'rrze-settings'));
        $this->renderDescription(__('Warns when a website already has at least the configured number of administrators and an existing user is promoted. The warning can be confirmed; it is not a fixed limit.', 'rrze-settings'));
        printf('<p><label for="rrze-settings-admin-role-threshold-warning-threshold">%s</label> ', esc_html__('Administrator threshold per website', 'rrze-settings'));
        $this->renderInput('number', 'rrze-settings-admin-role-threshold-warning-threshold', sprintf('%s[admin_role_threshold_warning_threshold]', $this->optionName), $this->siteOptions->general->admin_role_threshold_warning_threshold, 'small-text', ['min' => 3, 'step' => 1, 'aria-describedby' => 'rrze-settings-admin-threshold-help']);
        echo '</p>';
        printf('<p id="rrze-settings-admin-threshold-help" class="description">%s</p>', esc_html__('Minimum and default: 3. Used only while the warning is enabled.', 'rrze-settings'));
        $this->renderSettingDetails(__('Admin Role Threshold Warning', 'rrze-settings'), [
            __('Example: with a threshold of 3 and three existing administrators, promoting another user requires typing the displayed confirmation phrase. Existing administrators keep their role.', 'rrze-settings'),
            __('The check runs in the user management screens of individual websites, including bulk role changes. It counts users with the Administrator role on that website, not all network super administrators.', 'rrze-settings'),
            __('The current count is checked before the change. A bulk change that crosses the threshold from below does not trigger the warning. Actions without an existing user ID, such as creating a new account, are not covered.', 'rrze-settings'),
            __('This is a JavaScript confirmation, not a server-enforced restriction. Network administration, API requests and command-line changes are not covered. Disabled: this additional confirmation is omitted.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the pages author role, capabilities and role removal consequences.
     *
     * @return void
     */
    public function pagesAuthorRoleField()
    {
        $this->renderCheckbox('rrze-settings-pages-author-role', sprintf('%s[pages_author_role]', $this->optionName), $this->siteOptions->users->pages_author_role, __('Make the Pages Author role available', 'rrze-settings'));
        $this->renderDescription(__('Allows users to create, publish and manage their own pages and upload media. Before disabling, assign affected users another role so they retain the access they need.', 'rrze-settings'));
        $this->renderSettingDetails(__('Pages Author', 'rrze-settings'), [
            __('Suitable for people who maintain their own pages but do not need to write posts. Includes editing and deleting their own published pages; does not grant management of other users, website settings or pages written by others.', 'rrze-settings'),
            __('Enabling makes the role available for assignment; it does not assign it to anyone automatically. Role definitions are added or removed when each website loads, rather than updating all websites at once.', 'rrze-settings'),
            __('Disabling removes the role definition. Existing role assignments are not migrated, so affected users lose the permissions supplied by this role. Their content is not deleted.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the super author role and distinguish it from administrative access.
     *
     * @return void
     */
    public function superAuthorRoleField()
    {
        $this->renderCheckbox('rrze-settings-super-author-role', sprintf('%s[super_author_role]', $this->optionName), $this->siteOptions->users->super_author_role, __('Make the Super Author role available', 'rrze-settings'));
        $this->renderDescription(__('Allows users to create, publish and manage their own posts and pages and upload media. Before disabling, assign affected users another role so they retain the access they need.', 'rrze-settings'));
        $this->renderSettingDetails(__('Super Author', 'rrze-settings'), [
            __('Combines work on own posts and pages, including editing and deleting published content. It does not grant editing of content written by others, user management or website administration. Super Author is not a network super administrator.', 'rrze-settings'),
            __('Enabling makes the role available for assignment; it does not assign it to anyone automatically. Role definitions are added or removed when each website loads, rather than updating all websites at once.', 'rrze-settings'),
            __('Disabling removes the role definition. Existing role assignments are not migrated, so affected users lose the permissions supplied by this role. Their content is not deleted.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the enhanced user search with examples and its email-search exception.
     *
     * @return void
     */
    public function usersSearchField()
    {
        $this->renderCheckbox('rrze-settings-users-search', sprintf('%s[users_search]', $this->optionName), $this->siteOptions->users->users_search, __('Include first and last names in user searches', 'rrze-settings'));
        $this->renderDescription(__('Adds matching profile information to searches in user lists. Useful when you know a person by name rather than by their login.', 'rrze-settings'));
        $this->renderSettingDetails(__('User Search', 'rrze-settings'), [
            __('Searches first name, last name, login, display name, email address, website URL and the URL-friendly user name. For example, Anna Meyer can match first and last names stored in separate profile fields.', 'rrze-settings'),
            __('Multiple words must all match for an additional result; partial matches are supported. Searches containing @ use the normal WordPress search. This option does not add a search to the public website.', 'rrze-settings'),
            __('Disabled: user lists use the normal WordPress search without these additional matches.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the virtual contact page and its public data and main-site exceptions.
     *
     * @return void
     */
    public function contactPageField()
    {
        $this->renderCheckbox('rrze-settings-contact-page', sprintf('%s[contact_page]', $this->optionName), $this->siteOptions->users->contact_page, __('Provide an automatic contact page', 'rrze-settings'));
        $this->renderDescription(__('Lists website administrators with their display names and email addresses on a public-facing contact page. Check that these details are suitable for publication. The main website is excluded.', 'rrze-settings'));
        $this->renderSettingDetails(__('Contact Page', 'rrze-settings'), [
            __('The address follows the website language, for example /kontakt/ in German and /contact/ in English. It lists accounts with the Administrator role on that website; network super administrators are not automatically included.', 'rrze-settings'),
            __('An existing page with the same slug prevents the virtual page, including drafts, private pages and nested pages. This does not publish or move that existing page.', 'rrze-settings'),
            __('The contact page is generated when requested and is not stored as an editable page. To provide your own content, create a normal page with the matching slug. Existing website access restrictions still apply.', 'rrze-settings'),
            __('Disabled: the virtual page is no longer generated. Any normal contact page remains unchanged.', 'rrze-settings'),
        ]);
    }

    /**
     * Display the debug access list with the required login format and integration scope.
     *
     * @return void
     */
    public function canViewDebugLogField()
    {
        $rrzeUsers = implode(PHP_EOL, (array) $this->siteOptions->users->can_view_debug_log);
        $this->renderTextarea('rrze-settings-can-view-debug-log', sprintf('%s[can_view_debug_log]', $this->optionName), $rrzeUsers, 5, 55);
        $this->renderDescription(__('Enter one exact user login per line, not a display name, email address or user ID. Listed accounts can view debug information in components that use this permission check. Super administrators are always allowed.', 'rrze-settings'));
        $this->renderSettingDetails(__('Debug Log Access', 'rrze-settings'), [
            __('For example, enter anna.meyer and max.mustermann on separate lines. Use the spelling and capitalization of the actual login. An empty list allows only super administrators through this check.', 'rrze-settings'),
            __('This network-wide list is evaluated by Helper::userCanViewDebugLog(). It does not enable logging, create a log viewer or grant general website access. Other components may apply additional access checks.', 'rrze-settings'),
            __('Debug information may contain internal details or personal data. Add only accounts that need this information for support; removing a login withdraws this additional permission.', 'rrze-settings'),
        ]);
    }
}
