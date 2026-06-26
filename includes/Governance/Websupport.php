<?php

namespace RRZE\Settings\Governance;

defined('ABSPATH') || exit;

/**
 * Websupport class
 *
 * @package RRZE\Settings\Governance
 */
class Websupport
{
    /**
     * Site options
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded(): void
    {
        if (empty($this->siteOptions->governance->websupport_enabled)) {
            return;
        }

        add_filter('user_has_cap', [$this, 'grantCapabilities'], 10, 4);

        if (is_network_admin()) {
            add_action('pre_user_query', [$this, 'filterNetworkUsersQuery']);
            add_filter('views_users-network', [$this, 'filterNetworkUsersViews']);
            add_action('admin_footer', [$this, 'printNetworkUsersMarkup']);
        }
    }

    /**
     * Grants websupport capabilities dynamically without adding users to individual websites.
     *
     * @param array    $allcaps All user capabilities
     * @param array    $caps Required primitive capabilities
     * @param array    $args Capability arguments
     * @param \WP_User $user User object
     * @return array Modified capabilities
     */
    public function grantCapabilities($allcaps, $caps, $args, $user): array
    {
        unset($caps, $args);

        if (!$user instanceof \WP_User || !$this->isWebsupportUser((int) $user->ID)) {
            return $allcaps;
        }

        $allcaps['rrze_websupport'] = true;

        if (is_network_admin()) {
            return $this->grantNetworkCapabilities($allcaps);
        }

        return $this->grantSiteAdministratorCapabilities($allcaps);
    }

    /**
     * Grants capabilities for read-oriented RRZE network tools.
     *
     * @param array $allcaps All user capabilities
     * @return array Modified capabilities
     */
    protected function grantNetworkCapabilities(array $allcaps): array
    {
        $allcaps['read'] = true;
        $allcaps['websupport'] = true;
        $allcaps['rrze_websupport_read_multisite_manager'] = true;
        $allcaps['rrze_multisite_manager_read'] = true;

        return $allcaps;
    }

    /**
     * Grants all capabilities of the current website administrator role.
     *
     * @param array $allcaps All user capabilities
     * @return array Modified capabilities
     */
    protected function grantSiteAdministratorCapabilities(array $allcaps): array
    {
        $role = get_role('administrator');

        if (!$role) {
            return $allcaps;
        }

        foreach ($role->capabilities as $capability => $enabled) {
            if ($enabled) {
                $allcaps[$capability] = true;
            }
        }

        $allcaps['read'] = true;
        $allcaps['websupport'] = true;
        $allcaps['rrze_websupport_site_admin'] = true;

        return $allcaps;
    }

    /**
     * Filters the network users list to websupport users.
     *
     * @param \WP_User_Query $wpUserQuery User query
     * @return void
     */
    public function filterNetworkUsersQuery($wpUserQuery): void
    {
        if (!$this->isNetworkUsersScreen() || !$this->isWebsupportFilterRequest()) {
            return;
        }

        global $wpdb;

        $userIds = $this->getWebsupportUserIds();
        if (empty($userIds)) {
            $wpUserQuery->query_where .= ' AND 1 = 0';
            return;
        }

        $wpUserQuery->query_where .= sprintf(' AND %s.ID IN (%s)', $wpdb->users, implode(',', $userIds));
    }

    /**
     * Adds the websupport view to the network users list.
     *
     * @param array $views User list views
     * @return array Modified user list views
     */
    public function filterNetworkUsersViews(array $views): array
    {
        if (!$this->isNetworkUsersScreen()) {
            return $views;
        }

        $label = $this->getRoleName();
        $count = count($this->getWebsupportUserIds());
        $url = add_query_arg('websupport', '1', network_admin_url('users.php'));
        $current = $this->isWebsupportFilterRequest() ? ' class="current" aria-current="page"' : '';

        $views['websupport'] = sprintf(
            '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
            esc_url($url),
            $current,
            esc_html($label),
            $count
        );

        return $views;
    }

    /**
     * Prints markup to mark websupport users in the network users list.
     *
     * @return void
     */
    public function printNetworkUsersMarkup(): void
    {
        if (!$this->isNetworkUsersScreen()) {
            return;
        }

        $userIds = $this->getWebsupportUserIds();
        if (empty($userIds)) {
            return;
        }

        ?>
        <style id="rrze-settings-websupport-users-css">
            .rrze-websupport-role-label {
                font-weight: 600;
            }
        </style>
        <script id="rrze-settings-websupport-users-js">
            var rrzeSettingsWebsupportLabel = <?php echo wp_json_encode($this->getRoleName()); ?>;
            var rrzeSettingsWebsupportUserIds = <?php echo wp_json_encode(array_map('strval', $userIds)); ?>;

            function rrzeSettingsGetUserIdFromRow(row) {
                var checkbox = row.querySelector('input[name="allusers[]"][value], input[type="checkbox"][value]');
                var userLink;
                var match;

                if (checkbox && rrzeSettingsWebsupportUserIds.indexOf(checkbox.value) !== -1) {
                    return checkbox.value;
                }

                userLink = row.querySelector('a[href*="user_id="], a[href*="user-edit.php"]');
                if (!userLink) {
                    return '';
                }

                match = userLink.href.match(/[?&]user_id=(\d+)/);
                if (match && rrzeSettingsWebsupportUserIds.indexOf(match[1]) !== -1) {
                    return match[1];
                }

                return '';
            }

            function rrzeSettingsGetUsernameCell(row) {
                return row.querySelector('td.username, td.column-username, td[data-colname="Username"], td[data-colname="Benutzername"]');
            }

            function rrzeSettingsMarkWebsupportRow(row) {
                var userId = rrzeSettingsGetUserIdFromRow(row);
                var username;
                var labelTarget;
                var marker;

                if (!userId) {
                    return;
                }

                row.classList.add('rrze-websupport-user');

                username = rrzeSettingsGetUsernameCell(row);
                if (!username || username.querySelector('.rrze-websupport-role-label')) {
                    return;
                }

                labelTarget = username.querySelector('strong') || username.querySelector('a') || username;

                marker = document.createElement('span');
                marker.className = 'rrze-websupport-role-label';
                marker.textContent = ' - ' + rrzeSettingsWebsupportLabel;
                labelTarget.appendChild(marker);
            }

            function rrzeSettingsMarkWebsupportUsers() {
                var rows = document.querySelectorAll('table.users tbody tr, .wp-list-table tbody tr, tr');

                rows.forEach(rrzeSettingsMarkWebsupportRow);
            }

            function rrzeSettingsObserveWebsupportUsers() {
                var target = document.querySelector('table.users tbody, .wp-list-table tbody, body');
                var observer;

                if (!target || !window.MutationObserver) {
                    return;
                }

                observer = new MutationObserver(rrzeSettingsMarkWebsupportUsers);
                observer.observe(target, {
                    childList: true,
                    subtree: true
                });
            }

            rrzeSettingsMarkWebsupportUsers();
            window.setTimeout(rrzeSettingsMarkWebsupportUsers, 250);
            window.setTimeout(rrzeSettingsMarkWebsupportUsers, 1000);
            window.addEventListener('load', rrzeSettingsMarkWebsupportUsers);
            rrzeSettingsObserveWebsupportUsers();
        </script>
        <?php
    }

    /**
     * Checks whether the user has websupport access.
     *
     * @param int $userId User ID
     * @return bool True if the user has websupport access
     */
    protected function isWebsupportUser(int $userId): bool
    {
        return in_array($userId, $this->getWebsupportUserIds(), true);
    }

    /**
     * Gets the configured websupport user IDs.
     *
     * @return array User IDs
     */
    protected function getWebsupportUserIds(): array
    {
        $userIds = (array) ($this->siteOptions->governance->websupport_users ?? []);
        $userIds = array_map('intval', $userIds);

        return array_values(array_unique(array_filter($userIds)));
    }

    /**
     * Gets the visible websupport role name.
     *
     * @return string Role name
     */
    protected function getRoleName(): string
    {
        $roleName = trim((string) ($this->siteOptions->governance->websupport_role_name ?? ''));

        return $roleName !== '' ? $roleName : 'Websupport';
    }

    /**
     * Checks whether the current request is the network users screen.
     *
     * @return bool True if the current request is the network users screen
     */
    protected function isNetworkUsersScreen(): bool
    {
        if (!is_network_admin()) {
            return false;
        }

        global $pagenow;

        if ($pagenow === 'users.php') {
            return true;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && in_array($screen->id, ['users-network', 'users'], true)) {
            return true;
        }

        return stripos($_SERVER['REQUEST_URI'] ?? '', '/network/users.php') !== false;
    }

    /**
     * Checks whether the websupport users filter is active.
     *
     * @return bool True if the filter is active
     */
    protected function isWebsupportFilterRequest(): bool
    {
        return isset($_GET['websupport']) && absint($_GET['websupport']) === 1;
    }
}
