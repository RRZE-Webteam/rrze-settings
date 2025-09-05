<?php

namespace RRZE\Settings\Heartbeat;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;

/**
 * RRZE Settings – Heartbeat
 *
 * Class to throttle WordPress Heartbeat requests with a settings page.
 * 
 * @package RRZE\Settings\Heartbeat
 */
class Heartbeat extends Main
{
    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        add_filter('heartbeat_settings', [$this, 'filterHeartbeatSettings'], 1000, 1);
        add_action('wp_enqueue_scripts', [$this, 'maybeDisableFrontendHeartbeat'], 1);
        add_action('admin_enqueue_scripts', [$this, 'maybeDisableAdminHeartbeat'], 1);
        add_action('admin_enqueue_scripts', [$this, 'maybeForceInlineAfter'], 1000);
    }

    /**
     * Filter heartbeat settings to adjust interval.
     * 
     * @param array $settings The current heartbeat settings
     * @return array The modified heartbeat settings
     */
    public function filterHeartbeatSettings(array $settings): array
    {
        if (is_admin()) {
            $settings['interval'] = $this->getEffectiveInterval();
        }
        return $settings;
    }

    /**
     * Maybe disable frontend heartbeat
     * 
     * @return void
     */
    public function maybeDisableFrontendHeartbeat(): void
    {
        if (! is_user_logged_in() && ! is_admin() && ! is_customize_preview()) {
            if (!empty($this->siteOptions->heartbeat->disable_frontend)) {
                wp_deregister_script('heartbeat');
            }
        }
    }

    /**
     * Maybe disable admin heartbeat for non-editors
     * 
     * @param string $hook The current admin page hook
     * @return void
     */
    public function maybeDisableAdminHeartbeat($hook): void
    {
        if (empty($this->siteOptions->heartbeat->disable_admin_non_editor)) {
            return;
        }

        // Always keep in post editors
        if ($this->isPostEditorScreen()) {
            return;
        }

        // NEW: Allowlist check (from settings)
        $allow = isset($this->siteOptions->heartbeat->admin_allowlist_hooks) && is_array($this->siteOptions->heartbeat->admin_allowlist_hooks)
            ? $this->siteOptions->heartbeat->admin_allowlist_hooks
            : [];

        if (in_array($hook, $allow, true)) {
            return; // allow heartbeat on this screen
        }

        // Default: disable heartbeat
        wp_deregister_script('heartbeat');
    }

    /**
     * Maybe force inline after for heartbeat
     * 
     * @return void
     */
    public function maybeForceInlineAfter($hook = null): void
    {
        if (empty($this->siteOptions->heartbeat->force_js_slow)) {
            return;
        }

        $interval = $this->getEffectiveInterval();
        $code = sprintf(
            'jQuery(function(){ wp.heartbeat.interval(%d); console.log("[rrze-settings - heartbeat] forced inline after to %d"); });',
            $interval,
            $interval
        );

        wp_add_inline_script('heartbeat', $code, 'after');
    }

    /**
     * Check if current screen is a post editor screen
     * 
     * @return bool True if current screen is a post editor screen, false otherwise
     */
    private function isPostEditorScreen(): bool
    {
        global $pagenow;

        if (in_array($pagenow, ['post.php', 'post-new.php'], true)) {
            return true;
        }
        // Site editor safeguard (rarely needed for Heartbeat):
        if (isset($_GET['page']) && $_GET['page'] === 'gutenberg-edit-site') {
            return true;
        }
        return false;
    }

    /**
     * Get current user roles
     * 
     * @return array The current user roles
     */
    private function getCurrentUserRoles(): array
    {
        if (! is_user_logged_in()) {
            return [];
        }
        $user = wp_get_current_user();
        return array_values((array)$user->roles);
    }

    /**
     * Get effective interval based on user roles and screen type
     * 
     * @return int The effective interval in seconds (minimum 15 seconds)
     */
    private function getEffectiveInterval(): int
    {
        $roles    = $this->getCurrentUserRoles();
        $isEditor = $this->isPostEditorScreen();

        // Role overrides take precedence
        foreach ($roles as $role) {
            if (isset($this->siteOptions->heartbeat->role_overrides[$role]) && is_array($this->siteOptions->heartbeat->role_overrides[$role])) {
                $ov = $this->siteOptions->heartbeat->role_overrides[$role];
                if ($isEditor && isset($ov['editor'])) {
                    return max(15, (int) $ov['editor']);
                }
                if (isset($ov['default'])) {
                    return max(60, (int) $ov['default']);
                }
            }
        }

        // Fallback: by screen type
        if ($isEditor) {
            return max(15, (int) $this->siteOptions->heartbeat->editor_interval);
        }
        return max(60, (int) $this->siteOptions->heartbeat->admin_interval);
    }
}
