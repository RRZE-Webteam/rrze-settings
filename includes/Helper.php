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
        $ids_sql   = implode(',', $ids);
        $taxonomy_sql = esc_sql($taxonomy);

        $tt_ids = $wpdb->get_col("
        SELECT DISTINCT tt.term_taxonomy_id
        FROM {$wpdb->term_taxonomy} tt
        WHERE tt.taxonomy = '{$taxonomy_sql}'
          AND ( tt.term_taxonomy_id IN ({$ids_sql}) OR tt.term_id IN ({$ids_sql}) )
    ");

        $tt_ids = array_map('intval', (array) $tt_ids);
        if (empty($tt_ids)) {
            return;
        }

        // Build SQL fragments
        $tt_ids_sql     = implode(',', $tt_ids);
        $post_types_sql = "'" . implode("','", array_map('esc_sql', $object_types)) . "'";

        // Count attachments; allow filter if you also want to include e.g. 'private'
        $statuses = apply_filters('rrze_attachment_count_statuses', ['inherit']);
        // Defensive: ensure non-empty, and never count 'trash'
        $statuses = array_values(array_filter(array_diff((array) $statuses, ['trash'])));
        if (empty($statuses)) {
            $statuses = ['inherit'];
        }
        $statuses_sql = "'" . implode("','", array_map('esc_sql', $statuses)) . "'";

        // 2) Update counts for ALL requested term_taxonomy_ids in one query
        //    We count distinct object_ids that match the taxonomy row AND post filters.
        $wpdb->query("
        UPDATE {$wpdb->term_taxonomy} AS tt
        SET tt.count = (
            SELECT COUNT(DISTINCT tr.object_id)
            FROM {$wpdb->term_relationships} AS tr
            INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
              AND p.post_type IN ({$post_types_sql})
              AND p.post_status IN ({$statuses_sql})
        )
        WHERE tt.term_taxonomy_id IN ({$tt_ids_sql})
          AND tt.taxonomy = '{$taxonomy_sql}'
    ");

        error_log("
        UPDATE {$wpdb->term_taxonomy} AS tt
        SET tt.count = (
            SELECT COUNT(DISTINCT tr.object_id)
            FROM {$wpdb->term_relationships} AS tr
            INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
            WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
              AND p.post_type IN ({$post_types_sql})
              AND p.post_status IN ({$statuses_sql})
        )
        WHERE tt.term_taxonomy_id IN ({$tt_ids_sql})
          AND tt.taxonomy = '{$taxonomy_sql}'
    ");
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
        if (!in_array($taxonomy, ['attachment_category', 'attachment_tag'], true)) {
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
        if (empty($tt_ids)) return;

        $tt_ids = array_map('intval', (array) $tt_ids);
        $in     = implode(',', $tt_ids);

        $rows = $wpdb->get_results("
        SELECT taxonomy, term_taxonomy_id
        FROM {$wpdb->term_taxonomy}
        WHERE term_taxonomy_id IN ({$in})
          AND taxonomy IN ('attachment_category','attachment_tag')
    ", ARRAY_A);

        if (!$rows) return;

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
        $taxes = ['attachment_category', 'attachment_tag'];
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
