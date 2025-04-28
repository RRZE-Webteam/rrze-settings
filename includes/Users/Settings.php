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
        esc_html_e('Network administrators can configure user settings across the entire multisite network on this page, enabling special author roles, enhancing user search, generating a virtual contact page, and specifying which users have access to debug logs—streamlining user management and strengthening administrative control on every website.', 'rrze-settings');
    }

    /**
     * Renders the pages author role field
     *
     * @return void
     */
    public function pagesAuthorRoleField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-pages-author-role" name="<?php printf('%s[pages_author_role]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->users->pages_author_role, 1); ?>>
            <?php _e("Enables pages author role", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the super author role field
     *
     * @return void
     */
    public function superAuthorRoleField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-super-author-role" name="<?php printf('%s[super_author_role]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->users->super_author_role, 1); ?>>
            <?php _e("Enables super author role", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the users search field
     *
     * @return void
     */
    public function usersSearchField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-users-search" name="<?php printf('%s[users_search]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->users->users_search, 1); ?>>
            <?php _e("Enables enhanced users search", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the contact page field
     *
     * @return void
     */
    public function contactPageField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-contact-page" name="<?php printf('%s[contact_page]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->users->contact_page, 1); ?>>
            <?php _e("Generate a virtual page (contact) with the contact list (administrators) of the website or blog. If the page already exists, the existing page is displayed.", 'rrze-settings'); ?>
        </label>
<?php
    }

    /**
     * Renders the can view debug log field
     *
     * @return void
     */
    public function canViewDebugLogField()
    {
        $rrzeUsers = implode(PHP_EOL, (array) $this->siteOptions->users->can_view_debug_log);
        echo '<textarea rows="5" cols="55" id="rrze-settings-can-view-debug-log" class="regular-text" name="', sprintf('%s[can_view_debug_log]', $this->optionName), '">', esc_attr($rrzeUsers), '</textarea>';
        echo '<p class="description">' . __('List of users who can view debug information on websites, if any. Enter one user login per line.', 'rrze-settings') . '</p>';
    }
}
