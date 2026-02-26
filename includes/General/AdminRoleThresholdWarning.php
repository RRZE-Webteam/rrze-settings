<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use function RRZE\Settings\plugin;

/**
 * Class AdminRoleThresholdWarning
 * 
 * This class implements a warning mechanism when trying to create/promote users to administrator 
 * role in a multisite setup that already has a certain number of administrators. 
 * The warning is triggered via AJAX and can be used to prevent accidentally creating too many administrators on a site, which can be a security risk.
 *
 * @package RRZE\Settings\General
 */
class AdminRoleThresholdWarning
{
    /**
     * The threshold for the number of administrators before the warning is triggered.
     */
    private const THRESHOLD = 3;

    /**
     * Script handle (used for wp_enqueue_script + wp_set_script_translations)
     */
    private const HANDLE = 'ms-admin-role-threshold-warning';

    /**
     * Register hooks for this feature.
     * This should be called from the General class's loaded() method.
     * 
     * @return void
     */
    public static function loaded(): void
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_ajax_ms_admin_role_warning_check', [__CLASS__, 'ajax_check']);
    }

    /**
     * Enqueue the JavaScript for the admin role threshold warning.
     * This script will be enqueued on user management screens in the admin area.
     * 
     * @param string $hook The current admin page hook suffix.
     * @return void
     */
    public static function enqueue(string $hook): void
    {
        if (!is_multisite() || is_network_admin()) {
            return;
        }

        // Screens:
        // - user-edit.php  (edit user)
        // - user-new.php   (add user)
        // - users.php      (list + bulk actions)
        if (!in_array($hook, ['user-edit.php', 'user-new.php', 'users.php'], true)) {
            return;
        }

        if (!current_user_can('promote_users')) {
            return;
        }

        $buildDir  = trailingslashit(plugin()->getPath('build')) . 'general/';
        $assetPath = $buildDir . 'admin-role-threshold-warning.asset.php';
        $scriptUrl = plugins_url('build/general/admin-role-threshold-warning.js', plugin()->getBasename());

        $deps = [];
        $ver  = plugin()->getVersion();

        if (file_exists($assetPath)) {
            /** @var array{dependencies?:string[],version?:string} $asset */
            $asset = include $assetPath;
            $deps  = $asset['dependencies'] ?? [];
            $ver   = $asset['version'] ?? $ver;
        }

        wp_enqueue_script(self::HANDLE, $scriptUrl, $deps, $ver, true);

        wp_set_script_translations(
            self::HANDLE,
            'rrze-settings',
            plugin()->getPath('languages')
        );

        // Runtime data only (no UI strings here; those come from wp.i18n)
        wp_localize_script(self::HANDLE, 'MSAdminRoleWarning', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('ms_admin_role_warning'),
            'threshold' => self::THRESHOLD,
            'phrase'    => __('Yes, I understand the consequences and still want to create more administrators.', 'rrze-settings'),
        ]);
    }

    /**
     * AJAX handler to check if creating/promoting a user to administrator would exceed the threshold.
     * Expects POST parameters:
     * - role: the role being assigned (should be 'administrator' to trigger the check)
     * - user_id: (optional) a single user ID being edited/created
     * - user_ids: (optional) a list of user IDs being edited/created (for bulk actions)
     * 
     * Returns a JSON response with the following structure:
     * {
     *   success: true,
     *   data: {
     *     needWarning: bool, // whether the warning should be shown
     *     adminCount: int,   // current number of admins on this site
     *     newAdmins: int,    // how many new admins this action would create
     *     threshold: int,    // the threshold value for reference
     *   }
     * }
     * 
     * @return void
     */
    public static function ajax_check(): void
    {
        if (!is_multisite() || is_network_admin()) {
            wp_send_json_error(['message' => 'invalid_context'], 400);
        }

        if (!current_user_can('promote_users')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        check_ajax_referer('ms_admin_role_warning', 'nonce');

        $role = isset($_POST['role']) ? sanitize_key((string) $_POST['role']) : '';

        if ($role !== 'administrator') {
            wp_send_json_success([
                'needWarning' => false,
                'adminCount'  => 0,
                'newAdmins'   => 0,
                'threshold'   => self::THRESHOLD,
            ]);
        }

        $blog_id = get_current_blog_id();

        // Current admins on this blog (accurate at request time).
        $admin_ids = get_users([
            'blog_id' => $blog_id,
            'role'    => 'administrator',
            'fields'  => 'ID',
            'number'  => 0,
        ]);

        $admin_ids   = array_map('intval', is_array($admin_ids) ? $admin_ids : []);
        $admin_count = count($admin_ids);
        $admin_set   = array_fill_keys($admin_ids, true);

        // Selected users: either a single user_id or a list of user_ids (bulk).
        $user_ids = [];

        if (isset($_POST['user_id']) && $_POST['user_id'] !== '') {
            $user_ids[] = (int) $_POST['user_id'];
        }

        if (isset($_POST['user_ids'])) {
            $raw = $_POST['user_ids'];

            if (is_array($raw)) {
                foreach ($raw as $id) {
                    $user_ids[] = (int) $id;
                }
            } else {
                // allow comma-separated
                $parts = preg_split('/\s*,\s*/', (string) $raw);
                foreach ($parts as $id) {
                    $user_ids[] = (int) $id;
                }
            }
        }

        $user_ids = array_values(array_unique(array_filter($user_ids, static fn($v) => $v > 0)));

        // Compute how many *new* admins this action would create.
        // Only users that are NOT already admins on this blog would become "newAdmins".
        $new_admins = 0;
        foreach ($user_ids as $uid) {
            if (!isset($admin_set[$uid])) {
                $new_admins++;
            }
        }

        // Need warning if already at/over threshold and this action would create at least one new admin.
        $need = ($admin_count >= self::THRESHOLD) && ($new_admins > 0);

        wp_send_json_success([
            'needWarning' => $need,
            'adminCount'  => $admin_count,
            'newAdmins'   => $new_admins,
            'threshold'   => self::THRESHOLD,
        ]);
    }
}
