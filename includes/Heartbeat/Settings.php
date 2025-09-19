<?php

namespace RRZE\Settings\Heartbeat;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the heartbeat section of the plugin.
 *
 * @package RRZE\Settings\Heartbeat
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-heartbeat';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Heartbeat', 'rrze-settings'),
            __('Heartbeat', 'rrze-settings'),
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
        $input = (array) $input;

        $input['disable_frontend']         = !empty($input['disable_frontend']);
        $input['disable_admin_non_editor'] = !empty($input['disable_admin_non_editor']);
        $input['force_js_slow']            = !empty($input['force_js_slow']);

        $input['editor_interval'] = isset($input['editor_interval']) ? (int) $input['editor_interval'] : $input['editor_interval'];
        $input['admin_interval']  = isset($input['admin_interval'])  ? (int) $input['admin_interval']  : $input['admin_interval'];

        // Enforce lower bound (WordPress internals typically map 'fast' ~15s and slow ~60s)
        $input['editor_interval'] = max(15, $input['editor_interval']);
        $input['admin_interval']  = max(60, $input['admin_interval']);

        // role_overrides: accept JSON (textarea) or array
        if (isset($input['role_overrides'])) {
            if (is_array($input['role_overrides'])) {
                $roleOverrides = $input['role_overrides'];
            } else {
                $decoded = json_decode(wp_unslash($input['role_overrides']), true);
                $roleOverrides = is_array($decoded) ? $decoded : [];
            }

            // Sanitize role overrides
            $clean = [];
            foreach ($roleOverrides as $role => $map) {
                if (!is_string($role) || !is_array($map)) {
                    continue;
                }
                $r = [];
                if (isset($map['editor'])) {
                    $r['editor'] = max(15, (int)$map['editor']);
                }
                if (isset($map['default'])) {
                    $r['default'] = max(15, (int)$map['default']);
                }
                if (!empty($r)) {
                    $clean[$role] = $r;
                }
            }
            $input['role_overrides'] = $clean;
        }

        if (isset($input['admin_allowlist_hooks'])) {
            $text = is_array($input['admin_allowlist_hooks'])
                ? implode("\n", array_map('strval', $input['admin_allowlist_hooks']))
                : (string) $input['admin_allowlist_hooks'];

            $text = wp_unslash($text);
            $lines = preg_split('/\R/u', $text) ?: [];
            $hooks = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                // basic hardening: keep only reasonable chars (a–z, 0–9, . _ - /)
                $line = preg_replace('~[^a-z0-9._/\-]+~i', '', $line);
                if ($line !== '') {
                    $hooks[] = $line;
                }
            }
            // de-duplicate while preserving order
            $input['admin_allowlist_hooks'] = array_values(array_unique($hooks));
        }

        return $this->parseOptionsValidate($input, 'heartbeat');
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            'rrze-settings-heartbeat',
            __('Heartbeat', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'disable_frontend',
            __('Disable Heartbeat On Frontend', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'disable_frontend',
                'description' => __('Disable Heartbeat on the frontend for non-logged-in users (e.g., visitors).', 'rrze-settings')
            ]
        );

        add_settings_field(
            'disable_admin_non_editor',
            __('Disable Heartbeat In Admin Area', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'disable_admin_non_editor',
                'description' => __('Disable Heartbeat in the admin area (except on post editor screens).', 'rrze-settings')
            ]
        );

        add_settings_field(
            'force_js_slow',
            __('Force JS interval To Slow', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'force_js_slow',
                'description' => __('Set the JavaScript interval to “slow” (about 60s) in the admin area as a fallback if other settings do not apply.', 'rrze-settings')
            ]
        );

        add_settings_field(
            'editor_interval',
            __('Editor interval', 'rrze-settings'),
            [$this, 'renderNumberField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'editor_interval',
                'min' => 15,
                'step' => 1,
                'unit' => __('seconds', 'rrze-settings'),
                'description' => __('Interval for post editors (seconds).', 'rrze-settings')
            ]
        );

        add_settings_field(
            'admin_interval',
            __('Admin Interval', 'rrze-settings'),
            [$this, 'renderNumberField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'admin_interval',
                'min' => 15,
                'step' => 1,
                'unit' => __('seconds', 'rrze-settings'),
                'description' => __('Interval for other admin pages (seconds).', 'rrze-settings')
            ]
        );

        add_settings_field(
            'role_overrides',
            __('Role Overrides', 'rrze-settings'),
            [$this, 'renderTextareaJsonField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'role_overrides',
                'placeholder' => '{"administrator":{"editor":15,"default":60},"editor":{"editor":30,"default":90},"author":{"editor":45,"default":120}}',
                'description' => __('Set custom Heartbeat intervals per role. Provide a JSON object where each role key contains two values: editor for post editor screens and default for other admin pages. Intervals are in seconds. Example: {"administrator":{"editor":15,"default":60},"editor":{"editor":30,"default":90},"author":{"editor":45,"default":120}}.', 'rrze-settings')
            ]
        );

        add_settings_field(
            'admin_allowlist_hooks',
            __('Allowed Admin Page Hooks', 'rrze-settings'),
            [$this, 'renderTextareaLinesField'],
            $this->menuPage,
            'rrze-settings-heartbeat',
            [
                'key' => 'admin_allowlist_hooks',
                'placeholder' => "index.php",
                'description' => __('List of admin page hooks where Heartbeat is always allowed (one per line). Post editors (post.php, post-new.php) and the Site Editor are always allowed.', 'rrze-settings')
            ]
        );
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e('This page enables network administrators to manage heartbeat-related settings across the multisite network.', 'rrze-settings');
    }

    /**
     * Renders a checkbox field
     * 
     * @param  array $args The field arguments
     * @return void
     */
    public function renderCheckboxField(array $args): void
    {
        $key = $args['key'];
        printf(
            '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
            $this->optionName,
            esc_attr($key),
            checked(!empty($this->siteOptions->heartbeat->$key), true, false),
            $args['description'] ? esc_html__($args['description']) : ''
        );
    }

    /**
     * Renders a number field
     * 
     * @param  array $args The field arguments
     * @return void
     */
    public function renderNumberField(array $args): void
    {
        $key = $args['key'];
        $min = isset($args['min']) ? (int)$args['min'] : 0;
        $step = isset($args['step']) ? (int)$args['step'] : 1;
        printf(
            '<input type="number" name="%1$s[%2$s]" value="%3$s" min="%4$d" step="%5$d" class="small-text">',
            $this->optionName,
            esc_attr($key),
            esc_attr((string) $this->siteOptions->heartbeat->$key),
            $min,
            $step
        );
        if (isset($args['unit'])) {
            echo ' <span>' . esc_html__($args['unit']) . '</span>';
        }
        if ($args['description']) {
            echo '<p class="description">' . esc_html__($args['description']) . '</p>';
        }
    }

    /**
     * Renders a textarea field for JSON input
     * 
     * @param  array $args The field arguments
     * @return void
     */
    public function renderTextareaJsonField(array $args): void
    {
        $key = $args['key'];
        $val = isset($this->siteOptions->heartbeat->$key) ? $this->siteOptions->heartbeat->$key : [];
        $json = wp_json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        printf(
            '<textarea name="%1$s[%2$s]" rows="10" cols="70" %3$s class="large-text code">%4$s</textarea>',
            $this->optionName,
            esc_attr($key),
            isset($args['placeholder']) ? 'placeholder="' . esc_attr($args['placeholder']) . '"' : '',
            esc_textarea($json)
        );
        if ($args['description']) {
            echo '<p class="description">' . esc_html__($args['description']) . '</p>';
        }
    }

    /**
     * Renders a textarea field for line-separated input
     * 
     * @param  array $args The field arguments
     * @return void
     */
    public function renderTextareaLinesField(array $args): void
    {
        $key = $args['key'];
        $val = isset($this->siteOptions->heartbeat->$key) && is_array($this->siteOptions->heartbeat->$key) ? $this->siteOptions->heartbeat->$key : [];
        $text = implode("\n", $val);
        printf(
            '<textarea name="%1$s[%2$s]" rows="6" cols="70" class="large-text code" %3$s>%4$s</textarea>',
            $this->optionName,
            esc_attr($key),
            isset($args['placeholder']) ? 'placeholder="' . esc_attr($args['placeholder']) . '"' : '',
            esc_textarea($text)
        );
        if ($args['description']) {
            echo '<p class="description">' . esc_html__($args['description']) . '</p>';
        }
    }
}
