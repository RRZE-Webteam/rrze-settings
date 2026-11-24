<?php

namespace RRZE\Settings\Users;

defined('ABSPATH') || exit;

/**
 * Records the time of the last successful login of a user.
 *
 * The value is stored network-wide as user meta (Unix timestamp, UTC,
 * integer) and updated on the 'wp_login' action. 'wp_login' fires in
 * wp_signon() after successful authentication, which also covers the
 * SSO login of RRZE SSO (it authenticates via the 'authenticate' filter).
 *
 * In the network admin user list, the sortable column "Last login" shows
 * the value. Users without a recorded login are listed last when sorting
 * by the most recent login and first when sorting in ascending order.
 *
 * Successors of the former activity log of RRZE Netzwerk-Audit
 * ('rrze_users_activity'). A separate meta key is used on purpose:
 * RRZE Netzwerk-Audit deletes the old keys as legacy data.
 *
 * @package RRZE\Settings\Users
 */
class LastLogin {
    /**
     * User meta key (Unix timestamp as integer).
     */
    const META_KEY = 'rrze_last_login';

    /**
     * User meta key of the former RRZE Netzwerk-Audit activity record.
     */
    const LEGACY_ACTIVITY_META_KEY = 'rrze_users_activity';

    /**
     * User meta keys used by Wordfence Login Security for the last login.
     */
    const WORDFENCE_META_KEYS = ['wfls_last_login', 'wfls-last-login', 'wfls_lastlogin'];

    /**
     * Column key and orderby value in the network admin user list.
     */
    const COLUMN = 'rrze_last_login';

    /**
     * Table alias of the user meta join used for sorting.
     */
    const SORT_ALIAS = 'rrze_last_login_meta';

    /**
     * Register hooks.
     *
     * @return void
     */
    public function loaded(): void {
        add_action('wp_login', [$this, 'onLogin'], 10, 2);

        if (!is_multisite()) {
            return;
        }

        // Network admin user list.
        add_filter('wpmu_users_columns', [$this, 'addColumn']);
        add_filter('manage_users-network_sortable_columns', [$this, 'addSortableColumn']);
        add_filter('manage_users_custom_column', [$this, 'renderColumn'], 10, 3);
        add_action('pre_user_query', [$this, 'sortQuery']);
    }

    /**
     * Add the column after "Registered" (or at the end).
     *
     * @param array $columns
     * @return array
     */
    public function addColumn($columns): array {
        $columns = (array) $columns;
        $label = esc_html__('Last login', 'rrze-settings');

        if (!isset($columns['registered'])) {
            $columns[self::COLUMN] = $label;
            return $columns;
        }

        $result = [];
        foreach ($columns as $key => $value) {
            $result[$key] = $value;
            if ($key === 'registered') {
                $result[self::COLUMN] = $label;
            }
        }

        return $result;
    }

    /**
     * Make the column sortable; the first click shows the most recent logins.
     *
     * @param array $columns
     * @return array
     */
    public function addSortableColumn($columns): array {
        $columns = (array) $columns;
        $columns[self::COLUMN] = [self::COLUMN, true];

        return $columns;
    }

    /**
     * Render the column content.
     *
     * @param string $value
     * @param string $columnName
     * @param int $userId
     * @return string
     */
    public function renderColumn($value, $columnName, $userId) {
        if ($columnName !== self::COLUMN) {
            return $value;
        }

        $timestamp = self::get((int) $userId);
        if (!$timestamp) {
            return sprintf(
                '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">%s</span>',
                esc_html__('No login recorded', 'rrze-settings')
            );
        }

        $date = wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
        $ago = sprintf(
            /* translators: %s: Human-readable time difference, e.g. "3 days". */
            __('%s ago', 'rrze-settings'),
            human_time_diff($timestamp, time())
        );

        return sprintf(
            '<time datetime="%1$s">%2$s</time><br><span class="description">%3$s</span>',
            esc_attr(gmdate('c', $timestamp)),
            esc_html((string) $date),
            esc_html($ago)
        );
    }

    /**
     * Sort the network admin user list by the last login.
     *
     * Uses a LEFT JOIN instead of a meta query, so users without a value
     * remain in the list without an OR meta query (which would add DISTINCT
     * and conflict with ORDER BY in MySQL).
     *
     * @param \WP_User_Query $query
     * @return void
     */
    public function sortQuery($query): void {
        global $wpdb;

        if (!is_network_admin() || !is_object($query) || $query->get('orderby') !== self::COLUMN) {
            return;
        }

        $alias = self::SORT_ALIAS;
        $order = strtoupper((string) $query->get('order')) === 'DESC' ? 'DESC' : 'ASC';

        $query->query_from .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->usermeta} AS {$alias} ON ({$alias}.user_id = {$wpdb->users}.ID AND {$alias}.meta_key = %s)",
            self::META_KEY
        );
        $query->query_orderby = "ORDER BY CAST({$alias}.meta_value AS UNSIGNED) {$order}, {$wpdb->users}.user_login ASC";
    }

    /**
     * Update the timestamp after a successful login.
     *
     * @param string $userLogin
     * @param \WP_User|null $user
     * @return void
     */
    public function onLogin($userLogin, $user = null): void {
        $userId = (is_object($user) && isset($user->ID)) ? (int) $user->ID : 0;
        if ($userId <= 0) {
            return;
        }

        self::update($userId);
    }

    /**
     * Store the given (or current) timestamp for a user.
     *
     * @param int $userId
     * @param int $timestamp Unix timestamp; 0 = now
     * @return void
     */
    public static function update(int $userId, int $timestamp = 0): void {
        if ($userId <= 0) {
            return;
        }

        if ($timestamp <= 0) {
            $timestamp = time();
        }

        update_user_meta($userId, self::META_KEY, $timestamp);

        /**
         * Fires after the last login timestamp of a user was updated.
         *
         * @param int $userId
         * @param int $timestamp Unix timestamp (UTC)
         */
        do_action('rrze_settings_user_last_login', $userId, $timestamp);
    }

    /**
     * Get the best available last-login timestamp of a user.
     *
     * The RRZE Settings value is authoritative. Legacy activity and
     * Wordfence values are used only when no RRZE Settings value exists.
     *
     * @param int $userId
     * @return int Unix timestamp (UTC); 0 if unknown
     */
    public static function get(int $userId): int {
        if ($userId <= 0) {
            return 0;
        }

        $timestamp = self::getTimestamp(get_user_meta($userId, self::META_KEY, true));
        if ($timestamp > 0) {
            return $timestamp;
        }

        return max(
            self::getLegacyActivityTimestamp($userId),
            self::getWordfenceTimestamp($userId)
        );
    }

    /**
     * Get the last timestamp from the former Netzwerk-Audit user meta.
     *
     * @param int $userId
     * @return int Unix timestamp (UTC); 0 if unknown
     */
    protected static function getLegacyActivityTimestamp(int $userId): int
    {
        $activity = get_user_meta($userId, self::LEGACY_ACTIVITY_META_KEY, true);
        if (!is_array($activity)) {
            return 0;
        }

        return self::getTimestamp($activity['timestamp'] ?? 0);
    }

    /**
     * Get the latest usable timestamp stored by Wordfence.
     *
     * @param int $userId
     * @return int Unix timestamp (UTC); 0 if unknown
     */
    protected static function getWordfenceTimestamp(int $userId): int
    {
        $timestamp = 0;
        foreach (self::WORDFENCE_META_KEYS as $metaKey) {
            $timestamp = max(
                $timestamp,
                self::getTimestamp(get_user_meta($userId, $metaKey, true))
            );
        }

        return $timestamp;
    }

    /**
     * Normalize a numeric or date-based meta value to a Unix timestamp.
     *
     * @param mixed $value
     * @return int Unix timestamp (UTC); 0 if invalid
     */
    protected static function getTimestamp($value): int
    {
        if (is_numeric($value)) {
            return absint($value);
        }

        if (!is_string($value) || $value === '') {
            return 0;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? 0 : $timestamp;
    }
}
