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
 * the date. Users without a recorded login are listed last when sorting
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
        add_filter('wpmu_users_columns', [$this, 'addColumn'], PHP_INT_MAX);
        add_filter('manage_users-network_sortable_columns', [$this, 'addSortableColumn'], PHP_INT_MAX);
        add_filter('manage_users_custom_column', [$this, 'renderColumn'], PHP_INT_MAX, 3);
        add_action('pre_user_query', [$this, 'sortQuery'], PHP_INT_MAX);
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

        foreach (self::WORDFENCE_META_KEYS as $column) {
            unset($columns[$column]);
        }
        unset($columns[self::COLUMN]);

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

        $date = wp_date('d.m.Y', $timestamp);

        return sprintf(
            '<time datetime="%1$s">%2$s</time>',
            esc_attr(gmdate('c', $timestamp)),
            esc_html((string) $date)
        );
    }

    /**
     * Sort the network admin user list by the last login.
     *
     * Uses the same source priority as the displayed value. Correlated
     * subqueries avoid duplicate users when legacy metadata has more than
     * one row for a user.
     *
     * @param \WP_User_Query $query
     * @return void
     */
    public function sortQuery($query): void {
        global $wpdb;

        if (!is_network_admin() || !is_object($query) || $query->get('orderby') !== self::COLUMN) {
            return;
        }

        $order = strtoupper((string) $query->get('order')) === 'DESC' ? 'DESC' : 'ASC';
        $expression = $this->getSortExpression();

        $query->query_orderby = "ORDER BY {$expression} {$order}, {$wpdb->users}.user_login ASC";
    }

    /**
     * Build the SQL expression for the effective last-login timestamp.
     *
     * @return string
     */
    protected function getSortExpression(): string
    {
        global $wpdb;

        $wordfencePlaceholders = implode(', ', array_fill(0, count(self::WORDFENCE_META_KEYS), '%s'));
        $arguments = array_merge(
            [self::META_KEY],
            ['s:9:"timestamp";i:', self::LEGACY_ACTIVITY_META_KEY, '%s:9:"timestamp";i:%'],
            self::WORDFENCE_META_KEYS
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table and column names are trusted identifiers; all meta keys and values use placeholders.
        return $wpdb->prepare(
            "COALESCE(NULLIF((SELECT MAX(CAST(rrze_last_login.meta_value AS UNSIGNED)) FROM {$wpdb->usermeta} AS rrze_last_login WHERE rrze_last_login.user_id = {$wpdb->users}.ID AND rrze_last_login.meta_key = %s AND rrze_last_login.meta_value REGEXP '^[0-9]+$'), 0), GREATEST(COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(rrze_legacy_activity.meta_value, %s, -1), ';', 1) AS UNSIGNED)) FROM {$wpdb->usermeta} AS rrze_legacy_activity WHERE rrze_legacy_activity.user_id = {$wpdb->users}.ID AND rrze_legacy_activity.meta_key = %s AND rrze_legacy_activity.meta_value LIKE %s), 0), COALESCE((SELECT MAX(CASE WHEN rrze_wordfence.meta_value REGEXP '^[0-9]+$' THEN CAST(rrze_wordfence.meta_value AS UNSIGNED) ELSE UNIX_TIMESTAMP(rrze_wordfence.meta_value) END) FROM {$wpdb->usermeta} AS rrze_wordfence WHERE rrze_wordfence.user_id = {$wpdb->users}.ID AND rrze_wordfence.meta_key IN ({$wordfencePlaceholders})), 0)))",
            ...$arguments
        );
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
