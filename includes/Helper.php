<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

/**
 * Helper class
 * 
 * @package RRZE\Settings
 */
class Helper
{
    /**
     * Check if the plugin exists (by its main file).
     *
     * @param  string $pluginFile Path relative to WP_PLUGIN_DIR (e.g. 'akismet/akismet.php').
     * @return bool True if the plugin file exists, false otherwise.
     */
    public static function pluginExists(string $pluginFile): bool
    {
        return file_exists(WP_PLUGIN_DIR . '/' . ltrim($pluginFile, '/'));
    }

    /**
     * Check if a plugin is active (site or network).
     *
     * @param string $pluginFile Path relative to wp-content/plugins (e.g. 'my-plugin/my-plugin.php').
     * @return bool True if active, false otherwise.
     */
    public static function isPluginActive(string $pluginFile): bool
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (is_multisite() && is_plugin_active_for_network($pluginFile)) {
            return true;
        }

        return is_plugin_active($pluginFile);
    }

    /**
     * Validate a domain name (optionally extracted from a URL).
     *
     * - Accepts either a bare domain ("example.com") or a full URL ("https://sub.example.com/path").
     * - Converts IDN (e.g., bücher.de) to ASCII (punycode) if the intl extension is available.
     * - Rejects IP addresses unless $allowLocalhost is true for loopback.
     * - Optionally requires DNS existence (A/AAAA/CNAME).
     *
     * @param string $input            Domain or URL to check.
     * @param bool   $allowLocalhost   Allow "localhost" and loopback IPs.
     * @param bool   $requireDns       If true, check DNS records must exist.
     * @return bool
     */
    public static function isValidDomain(string $input, bool $allowLocalhost = false, bool $requireDns = false): bool
    {
        $candidate = trim($input);

        // If it's a URL (scheme://...), extract host. Otherwise treat as domain string.
        if (preg_match('~^[a-z][a-z0-9+\-.]*://~i', $candidate)) {
            $parts = parse_url($candidate);
            $candidate = $parts['host'] ?? '';
        }

        if ($candidate === '') {
            return false;
        }

        // Strip a trailing dot (FQDNs like "example.com.")
        $candidate = rtrim($candidate, '.');

        // Handle IDN -> ASCII (punycode) if possible
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($candidate, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $candidate = $ascii;
            }
        }

        // Disallow underscores in labels (not valid per RFC 1035 for hostnames)
        if (strpos($candidate, '_') !== false) {
            return false;
        }

        // If it's an IP literal, only allow if localhost is allowed and it's loopback
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            if ($allowLocalhost && in_array($candidate, ['127.0.0.1', '::1'], true)) {
                return true;
            }
            return false;
        }

        // RFC-ish domain syntax: labels 1-63, total <= 253, letters/digits/hyphens, no leading/trailing hyphen
        $isDomain = (bool) preg_match(
            '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
            $candidate
        );

        if (!$isDomain) {
            // Optionally allow "localhost" as a special case
            if ($allowLocalhost && strcasecmp($candidate, 'localhost') === 0) {
                return true;
            }
            return false;
        }

        if ($requireDns) {
            // checkdnsrr needs a trailing dot for absolute names on some systems
            $fqdn = $candidate . '.';
            if (!checkdnsrr($fqdn, 'A') && !checkdnsrr($fqdn, 'AAAA') && !checkdnsrr($fqdn, 'CNAME')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user can view the debug log
     * 
     * @return boolean Returns true if either is_super_admin() or the user is allowed to view the debug log
     */
    public static function userCanViewDebugLog(): bool
    {
        $allowed = false;

        if (is_super_admin()) {
            $allowed = true;
        } else {
            $siteOptions = Options::getSiteOptions();
            $currentUser = wp_get_current_user();
            if (!empty($siteOptions->users->can_view_debug_log)) {
                $allowed = in_array($currentUser->user_login, $siteOptions->users->can_view_debug_log);
            }
        }

        return $allowed;
    }

    /**
     * Check if the user has websupport access
     *
     * @param int $userId User ID. Defaults to the current user.
     * @return bool True if the user has websupport access
     */
    public static function isWebsupportUser(int $userId = 0): bool
    {
        if (!$userId) {
            $userId = get_current_user_id();
        }

        if (!$userId) {
            return false;
        }

        $siteOptions = Options::getSiteOptions();
        if (empty($siteOptions->governance->websupport_enabled)) {
            return false;
        }

        $userIds = (array) ($siteOptions->governance->websupport_users ?? []);
        $userIds = array_map('intval', $userIds);

        return in_array($userId, $userIds, true);
    }

    /**
     * Get the visible websupport role name
     *
     * @return string Role name
     */
    public static function getWebsupportRoleName(): string
    {
        $siteOptions = Options::getSiteOptions();
        $roleName = trim((string) ($siteOptions->governance->websupport_role_name ?? ''));

        return $roleName !== '' ? $roleName : 'Websupport';
    }

    /**
     * Check if the current user has websupport access
     *
     * @return bool True if the current user has websupport access
     */
    public static function currentUserCanWebsupport(): bool
    {
        return self::isWebsupportUser();
    }

    /**
     * Check if the user can view the debug log
     * 
     * @deprecated 2.0.0 Use self::userCanViewDebugLog() instead
     * @return boolean Returns true if either is_super_admin() or the user is allowed to view the debug log
     */
    public static function isRRZEAdmin(): bool
    {
        _deprecated_function(__METHOD__, '2.0.0', 'self::userCanViewDebugLog');

        return self::userCanViewDebugLog();
    }

    /**
     * Get the BITE API key
     * 
     * @return string The API key
     */
    public static function getBiteApiKey(): string
    {
        $siteOptions = Options::getSiteOptions();
        return $siteOptions->plugins->bite_api_key ?? '';
    }

    /**
     * Get the DIP Edu API key
     * 
     * @return string The API key
     */
    public static function getDipEduApiKey(): string
    {
        $siteOptions = Options::getSiteOptions();
        return $siteOptions->plugins->dip_edu_api_key ?? '';
    }

    /**
     * Add an admin notice based on a transient
     * 
     * @param  string  $transient The transient name
     * @param  string  $message The message to display
     * @param  string  $type The type of notice (error, success, etc.)
     * @param  int     $duration The duration of the notice in seconds
     * @return void
     */
    public static function flashAdminNotice(string $transient, string $message, string $type = 'error', int $duration = 5): void
    {
        add_action('admin_notices', function () use ($transient, $message, $type, $duration) {
            if (get_transient($transient)) {
                echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible" data-duration="' . esc_attr($duration) . '">';
                echo '<p>' . esc_html($message) . '</p>';
                echo '</div>';
            }
        });
    }

    /**
     * Recount terms for taxonomies attached to attachments.
     * Counts posts with post_status IN ('inherit') by default.
     *
     * Accepts either term_taxonomy_ids or term_ids in $tt_ids and normalizes to tt_ids.
     *
     * @param int[]  $tt_ids   List of term_taxonomy_id OR term_id values.
     * @param string $taxonomy Taxonomy name.
     * @return void
     */
    public static function updateAttachmentTermCount($tt_ids, $taxonomy): void
    {
        global $wpdb;

        // Normalize input to integers
        $ids = array_map('intval', (array) $tt_ids);
        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        // Ensure taxonomy exists
        $tax = get_taxonomy($taxonomy);
        if (!$tax) {
            return;
        }

        // Resolve object types (should include 'attachment')
        $object_types = array_map('sanitize_key', (array) $tax->object_type);
        if (empty($object_types)) {
            $object_types = ['attachment'];
        }

        // 1) Normalize to term_taxonomy_ids:
        //    If we received term_taxonomy_ids, this query keeps them.
        //    If we received term_ids, this maps them to tt_ids for this taxonomy.
        $idsPlaceholders = implode(',', array_fill(0, count($ids), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Core does not provide a bulk term-taxonomy recount API.
        $tt_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT tt.term_taxonomy_id
        FROM {$wpdb->term_taxonomy} tt
        WHERE tt.taxonomy = %s
          AND ( tt.term_taxonomy_id IN ({$idsPlaceholders}) OR tt.term_id IN ({$idsPlaceholders}) )
    ", array_merge([$taxonomy], $ids, $ids)));

        $tt_ids = array_map('intval', (array) $tt_ids);
        if (empty($tt_ids)) {
            return;
        }

        $ttIdsPlaceholders = implode(',', array_fill(0, count($tt_ids), '%d'));
        $postTypesPlaceholders = implode(',', array_fill(0, count($object_types), '%s'));

        // Count attachments; allow filter if you also want to include e.g. 'private'
        $statuses = apply_filters('rrze_attachment_count_statuses', ['inherit']);
        // Defensive: ensure non-empty, and never count 'trash'
        $statuses = array_values(array_filter(array_diff((array) $statuses, ['trash'])));
        if (empty($statuses)) {
            $statuses = ['inherit'];
        }
        $statusesPlaceholders = implode(',', array_fill(0, count($statuses), '%s'));

        // 2) Update counts for ALL requested term_taxonomy_ids in one query
        //    We count distinct object_ids that match the taxonomy row AND post filters.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Core does not provide a bulk term-taxonomy recount API.
        $wpdb->query($wpdb->prepare("
        UPDATE {$wpdb->term_taxonomy} AS tt
        SET tt.count = (
            SELECT COUNT(DISTINCT tr.object_id)
            FROM {$wpdb->term_relationships} AS tr
            INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
              AND p.post_type IN ({$postTypesPlaceholders})
              AND p.post_status IN ({$statusesPlaceholders})
        )
        WHERE tt.term_taxonomy_id IN ({$ttIdsPlaceholders})
          AND tt.taxonomy = %s
    ", array_merge($object_types, $statuses, $tt_ids, [$taxonomy])));

    }

    /**
     * Recount on set_object_terms hook
     * 
     * @param int    $object_id
     * @param array  $terms
     * @param array  $tt_ids
     * @param string $taxonomy
     * @return void
     */
    public static function recountOnSet($object_id, $terms, $tt_ids, $taxonomy)
    {
        if (!in_array($taxonomy, ['attachment_document', 'attachment_category', 'attachment_tag'], true)) {
            return;
        }

        self::updateAttachmentTermCount($tt_ids, $taxonomy);
    }

    /**
     * Recount on deleted_term_relationships hook
     * 
     * @param int   $object_id
     * @param array $tt_ids
     * @return void
     */
    public static function recountOnDelete($object_id, $tt_ids)
    {
        global $wpdb;
        if (empty($tt_ids)) {
            return;
        }

        $tt_ids = array_map('intval', (array) $tt_ids);
        $placeholders = implode(',', array_fill(0, count($tt_ids), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Core does not provide this term-taxonomy lookup API.
        $rows = $wpdb->get_results($wpdb->prepare("
        SELECT taxonomy, term_taxonomy_id
        FROM {$wpdb->term_taxonomy}
        WHERE term_taxonomy_id IN ({$placeholders})
          AND taxonomy IN ('attachment_document','attachment_category','attachment_tag')
    ", $tt_ids), ARRAY_A);

        if (!$rows) {
            return;
        }

        $byTax = [];
        foreach ($rows as $r) {
            $byTax[$r['taxonomy']][] = (int) $r['term_taxonomy_id'];
        }
        foreach ($byTax as $tax => $ids) {
            self::updateAttachmentTermCount($ids, $tax);
        }
    }

    /**
     * Recount on rest_after_insert_attachment hook
     * 
     * @param \WP_Post        $post
     * @param \WP_REST_Request $request
     * @param boolean        $creating
     * @return void
     */
    public static function recountOnRest($post, $request, $creating)
    {
        $taxes = ['attachment_document', 'attachment_category', 'attachment_tag'];
        foreach ($taxes as $tax) {
            if ($request->offsetExists($tax)) {
                $tt = get_terms(['taxonomy' => $tax, 'fields' => 'tt_ids', 'hide_empty' => false]);
                if (!is_wp_error($tt) && $tt) {
                    self::updateAttachmentTermCount($tt, $tax);
                }
            }
        }
    }
}
