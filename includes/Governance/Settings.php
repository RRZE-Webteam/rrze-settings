<?php

namespace RRZE\Settings\Governance;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the governance section of the plugin.
 *
 * @package RRZE\Settings\Governance
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-governance';

    /**
     * Settings section name
     *
     * @var string
     */
    protected $sectionName = 'rrze-settings-governance-section';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Governance', 'rrze-settings'),
            __('Governance', 'rrze-settings'),
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
        $input['websupport_enabled'] = !empty($input['websupport_enabled']) ? 1 : 0;
        $input['websupport_role_name'] = sanitize_text_field($input['websupport_role_name'] ?? '');
        if ($input['websupport_role_name'] === '') {
            $input['websupport_role_name'] = 'Websupport';
        }
        $input['websupport_users'] = $this->sanitizeWebsupportUsers($input['websupport_users'] ?? '');

        return $this->parseOptionsValidate($input, 'governance');
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
            __('Governance', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'websupport_enabled',
            __('Websupport', 'rrze-settings'),
            [$this, 'websupportEnabledField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'websupport_users',
            __('Websupport Users', 'rrze-settings'),
            [$this, 'websupportUsersField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'websupport_role_name',
            __('Websupport Role Name', 'rrze-settings'),
            [$this, 'websupportRoleNameField'],
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
        esc_html_e('Network administrators can define governance-related access rules for the multisite network on this page.', 'rrze-settings');
    }

    /**
     * Renders the websupport enabled field
     *
     * @return void
     */
    public function websupportEnabledField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-websupport-enabled" name="<?php printf('%s[websupport_enabled]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->governance->websupport_enabled, 1); ?>>
            <?php _e("Enable network-wide websupport access", 'rrze-settings'); ?>
        </label>
<?php
    }

    /**
     * Renders the websupport users field
     *
     * @return void
     */
    public function websupportUsersField()
    {
        $users = $this->getWebsupportUsersForDisplay();

        echo '<textarea rows="8" cols="55" id="rrze-settings-websupport-users" class="regular-text" name="', sprintf('%s[websupport_users]', $this->optionName), '">', esc_textarea($users), '</textarea>';
        echo '<p class="description">' . esc_html__('List of network users who receive websupport access. Enter one user ID, login, or email address per line.', 'rrze-settings') . '</p>';
    }

    /**
     * Renders the websupport role name field
     *
     * @return void
     */
    public function websupportRoleNameField()
    {
        $roleName = $this->siteOptions->governance->websupport_role_name ?: 'Websupport';

        printf(
            '<input type="text" id="rrze-settings-websupport-role-name" class="regular-text" name="%1$s[websupport_role_name]" value="%2$s">',
            esc_attr($this->optionName),
            esc_attr($roleName)
        );
        echo '<p class="description">' . esc_html__('Visible name for websupport users in user filters and user lists. The internal role identifier remains websupport.', 'rrze-settings') . '</p>';
    }

    /**
     * Sanitize websupport users
     *
     * @param string|array $input Raw input
     * @return array User IDs
     */
    protected function sanitizeWebsupportUsers($input): array
    {
        $rows = is_array($input) ? $input : explode(PHP_EOL, (string) $input);
        $userIds = [];

        foreach ($rows as $row) {
            $value = trim((string) $row);
            if ($value === '') {
                continue;
            }

            $user = $this->getUserByInput($value);
            if ($user instanceof \WP_User && $user->exists()) {
                $userIds[] = (int) $user->ID;
            }
        }

        $userIds = array_values(array_unique(array_filter($userIds)));
        sort($userIds, SORT_NUMERIC);

        return $userIds;
    }

    /**
     * Get a user by ID, login, or email
     *
     * @param string $value Input value
     * @return \WP_User|false User object or false
     */
    protected function getUserByInput(string $value)
    {
        if (preg_match('/^(\d+)\s+-\s+.+$/', $value, $matches)) {
            return get_user_by('id', absint($matches[1]));
        }

        if (is_numeric($value)) {
            return get_user_by('id', absint($value));
        }

        if (is_email($value)) {
            return get_user_by('email', $value);
        }

        return get_user_by('login', $value);
    }

    /**
     * Get websupport users for display
     *
     * @return string Users list
     */
    protected function getWebsupportUsersForDisplay(): string
    {
        $lines = [];
        $userIds = (array) $this->siteOptions->governance->websupport_users;

        foreach ($userIds as $userId) {
            $user = get_user_by('id', absint($userId));
            if ($user instanceof \WP_User && $user->exists()) {
                $lines[] = sprintf('%d - %s', $user->ID, $user->user_login);
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
