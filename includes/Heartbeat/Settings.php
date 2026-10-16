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

        // Missing fields retain their saved values; bounds match runtime behavior.
        $saved = is_network_admin() ? $this->siteOptions->heartbeat : $this->options->heartbeat;
        $input['editor_interval'] = max(15, (int) ($input['editor_interval'] ?? $saved->editor_interval ?? 15));
        $input['admin_interval'] = max(60, (int) ($input['admin_interval'] ?? $saved->admin_interval ?? 60));

        // role_overrides: accept JSON (textarea) or array
        if (isset($input['role_overrides'])) {
            if (is_array($input['role_overrides'])) {
                $roleOverrides = $input['role_overrides'];
            } else {
                $text = trim(wp_unslash((string) $input['role_overrides']));
                $decoded = $text === '' ? [] : json_decode($text, true);
                $roleOverrides = is_array($decoded) ? $decoded : null;
            }

            $clean = [];
            $invalid = $roleOverrides === null || ($roleOverrides !== [] && array_is_list($roleOverrides));
            foreach ($roleOverrides ?? [] as $role => $map) {
                if (!is_string($role) || trim($role) === '' || !is_array($map) || $map === []) {
                    $invalid = true;
                    break;
                }
                $r = [];
                foreach ($map as $context => $seconds) {
                    if (!in_array($context, ['editor', 'default'], true)
                        || !(is_int($seconds) || (is_string($seconds) && preg_match('/^-?\d+$/D', $seconds)))) {
                        $invalid = true;
                        break;
                    }
                    $r[$context] = max($context === 'editor' ? 15 : 60, (int) $seconds);
                }
                $clean[$role] = $r;
            }

            // Reject the entire role edit instead of silently discarding individual rules.
            if ($invalid) {
                add_settings_error(
                    $this->menuPage,
                    'heartbeat_role_overrides_invalid',
                    __('Use a JSON object with role names and whole-second editor/default intervals. The previous role exceptions were kept; the other settings were saved.', 'rrze-settings')
                );
                unset($input['role_overrides']);
            } else {
                $input['role_overrides'] = $clean;
            }
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
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getHeartbeatSections(): array
    {
        return [
            'rrze-settings-heartbeat-availability' => [
                'title' => __('Where Heartbeat Runs', 'rrze-settings'),
                'callback' => 'availabilitySectionDescription',
            ],
            'rrze-settings-heartbeat-intervals' => [
                'title' => __('Default Intervals', 'rrze-settings'),
                'callback' => 'intervalSectionDescription',
            ],
            'rrze-settings-heartbeat-roles' => [
                'title' => __('Role Exceptions', 'rrze-settings'),
                'callback' => 'roleSectionDescription',
            ],
        ];
    }

    /**
     * Render the shared heartbeat form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage()
    {
        global $title;

        $sections = $this->getHeartbeatSections();
        echo '<div class="wrap rrze-settings-heartbeat-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-heartbeat-index" aria-labelledby="rrze-settings-heartbeat-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-heartbeat-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-heartbeat" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-heartbeat-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-heartbeat-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
            echo '</div>';
            $this->{$section['callback']}();
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($this->menuPage, $sectionId);
            echo '</table></section>';
        }
        submit_button(__('Save Changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo '</form></div>';
    }

    /**
     * Register the seven settings with labels, dependencies and examples.
     *
     * @return void
     */
    public function networkAdminPage()
    {
        foreach ($this->getHeartbeatSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'disable_frontend',
            __('Public Website', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat-availability',
            [
                'key' => 'disable_frontend',
                'title' => __('Public Website', 'rrze-settings'),
                'label' => __('Disable Heartbeat for visitors who are not logged in', 'rrze-settings'),
                'description' => __('Stops this plugin from providing the Heartbeat script on public pages for logged-out visitors. Logged-in visitors and the Customizer preview are excluded.', 'rrze-settings'),
                'details' => [
                    __('Useful only if a theme or plugin loads Heartbeat on public pages. It does not disable all AJAX requests, REST requests or scheduled WordPress tasks.', 'rrze-settings'),
                    __('Disabled: this plugin no longer removes Heartbeat there. It does not automatically load Heartbeat on every page.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'disable_admin_non_editor',
            __('Administration Outside Editors', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat-availability',
            [
                'key' => 'disable_admin_non_editor',
                'title' => __('Administration Outside Editors', 'rrze-settings'),
                'label' => __('Disable Heartbeat on other administration pages', 'rrze-settings'),
                'description' => __('Keeps Heartbeat available in classic and block content editors and on the listed exception pages. Other administration pages lose this script.', 'rrze-settings'),
                'details' => [
                    __('This applies to all users, including super administrators. Plugin status displays, login-status checks or other Heartbeat-dependent updates may stop on affected pages.', 'rrze-settings'),
                    __('The legacy Site Editor address with page=gutenberg-edit-site is also protected. The current site-editor.php screen is not automatically included; add site-editor.php to the exceptions below if it needs Heartbeat.', 'rrze-settings'),
                    __('Switching this off allows Heartbeat again on administration pages. The configured intervals still apply.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'admin_allowlist_hooks',
            __('Additional Administration Exceptions', 'rrze-settings'),
            [$this, 'renderTextareaLinesField'],
            $this->menuPage,
            'rrze-settings-heartbeat-availability',
            [
                'key' => 'admin_allowlist_hooks',
                'title' => __('Additional Administration Exceptions', 'rrze-settings'),
                'label_for' => 'rrze-settings-heartbeat-admin_allowlist_hooks',
                'description' => __('Keeps Heartbeat available on these pages when the administration restriction above is enabled. Enter one exact WordPress page hook per line, for example index.php for the dashboard.', 'rrze-settings'),
                'details' => [
                    __('Use hook names, not full URLs, menu labels or wildcard patterns. Examples: index.php for the dashboard, upload.php for the media library, site-editor.php for the Site Editor. Plugin pages use their registered hook suffix.', 'rrze-settings'),
                    __('Empty: no additional exceptions. The automatically protected content-editor screens remain exempt. Duplicate entries are combined. This list permits the script; it does not force it to load or choose an interval.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'editor_interval',
            __('Content Editor Interval', 'rrze-settings'),
            [$this, 'renderNumberField'],
            $this->menuPage,
            'rrze-settings-heartbeat-intervals',
            [
                'key' => 'editor_interval',
                'title' => __('Content Editor Interval', 'rrze-settings'),
                'label_for' => 'rrze-settings-heartbeat-editor_interval',
                'min' => 15,
                'description' => __('Default interval for content-editor screens, in seconds. Minimum and initial default: 15 seconds. A matching role exception takes precedence.', 'rrze-settings'),
                'details' => [
                    __('Heartbeat supports editing-lock updates and classic-editor autosave, among other tasks. Long intervals can delay these updates; if lock refreshes arrive after the lock expires, collaborative editing can be affected.', 'rrze-settings'),
                    __('Block-editor autosave is configured under CMS > Editor. If its Heartbeat synchronization is enabled, it uses twice this saved default, at least 60 seconds, even when a role exception gives Heartbeat a different interval.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'admin_interval',
            __('Other Administration Interval', 'rrze-settings'),
            [$this, 'renderNumberField'],
            $this->menuPage,
            'rrze-settings-heartbeat-intervals',
            [
                'key' => 'admin_interval',
                'title' => __('Other Administration Interval', 'rrze-settings'),
                'label_for' => 'rrze-settings-heartbeat-admin_interval',
                'min' => 60,
                'description' => __('Default interval for other administration pages where Heartbeat is available. Minimum and initial default: 60 seconds. Role exceptions take precedence.', 'rrze-settings'),
                'details' => [
                    __('Example: changing 60 to 120 roughly halves regular requests from an active page, but status updates can take longer. This is not a guaranteed request limit for the whole website.', 'rrze-settings'),
                    __('This value does not re-enable Heartbeat on disabled pages and does not set its interval on the public website. The current site-editor.php screen uses this administration default unless a role exception applies.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'force_js_slow',
            __('Apply the Interval in the Browser', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-heartbeat-intervals',
            [
                'key' => 'force_js_slow',
                'title' => __('Apply the Interval in the Browser', 'rrze-settings'),
                'label' => __('Set the calculated interval again when the administration page loads', 'rrze-settings'),
                'description' => __('Applies the same calculated interval, including role exceptions, through JavaScript. This does not set a fixed 60-second interval and does not re-enable a disabled Heartbeat script.', 'rrze-settings'),
                'details' => [
                    __('Use when the initial PHP interval setting is not being applied as expected. The browser call runs once when the page is ready; later changes by WordPress or other plugins can still take effect.', 'rrze-settings'),
                    __('Disabled: the PHP interval setting remains active; only the additional JavaScript call is omitted.', 'rrze-settings'),
                ],
            ]
        );

        add_settings_field(
            'role_overrides',
            __('Intervals by User Role', 'rrze-settings'),
            [$this, 'renderTextareaJsonField'],
            $this->menuPage,
            'rrze-settings-heartbeat-roles',
            [
                'key' => 'role_overrides',
                'title' => __('Intervals by User Role', 'rrze-settings'),
                'label_for' => 'rrze-settings-heartbeat-role_overrides',
                'description' => __('Enter a JSON object with technical role names such as administrator, editor or author. Use editor for content-editor screens and default for other administration pages. Minimums: editor 15 seconds, default 60 seconds.', 'rrze-settings'),
                'details' => [
                    __('Example: {"editor":{"editor":30,"default":90}} gives users with the editor role a 30-second content-editor interval and 90 seconds elsewhere. Use whole seconds and double quotes; JSON does not allow comments or trailing commas.', 'rrze-settings'),
                    __('If a role has no editor value, its default value also applies in the content editor. With multiple roles, the first applicable entry in the user’s stored role order wins. This is not necessarily the fastest interval or the most privileged role.', 'rrze-settings'),
                    __('An empty field or {} removes all role exceptions and uses the default intervals above. Invalid JSON or invalid role entries retain the previous rules and show an error after saving; the other settings are saved.', 'rrze-settings'),
                ],
            ]
        );
    }

    /**
     * Explain Heartbeat and the network scope before presenting its controls.
     *
     * @return void
     */
    public function mainSectionDescription()
    {
        echo '<p>', esc_html(__('Heartbeat is WordPress’s regular background connection to the server. It supports editing locks, login-status checks and classic-editor autosave. These settings apply throughout the network; reload affected pages after saving.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and dependencies of this settings group.
     *
     * @return void
     */
    public function availabilitySectionDescription()
    {
        echo '<p>', esc_html(__('Choose where this plugin should stop loading Heartbeat. Content editors and the exceptions below remain available. Disabling Heartbeat can stop live updates supplied by WordPress or other plugins.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and dependencies of this settings group.
     *
     * @return void
     */
    public function intervalSectionDescription()
    {
        echo '<p>', esc_html(__('These defaults apply where Heartbeat is available and no role exception takes precedence. Longer intervals reduce regular requests but delay updates. Inactive browser tabs and other plugins can change the actual timing.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and dependencies of this settings group.
     *
     * @return void
     */
    public function roleSectionDescription()
    {
        echo '<p>', esc_html(__('Role exceptions override the defaults above. They change timing, not permissions, and do not enable Heartbeat on a disabled screen.', 'rrze-settings')), '</p>';
    }

    /**
     * Show validation failures instead of reporting an unqualified successful save.
     *
     * @return void
     */
    public function settingsUpdateNotice()
    {
        if (get_settings_errors($this->menuPage)) {
            settings_errors($this->menuPage);
            return;
        }
        parent::settingsUpdateNotice();
    }

    /**
     * Render a checkbox with its own label and optional guidance.
     *
     * @param array $args Registered field metadata.
     * @return void
     */
    public function renderCheckboxField(array $args): void
    {
        $key = $args['key'];
        $this->renderCheckbox('rrze-settings-heartbeat-' . $key, sprintf('%s[%s]', $this->optionName, $key), !empty($this->siteOptions->heartbeat->$key), $args['label']);
        $this->renderHeartbeatHelp($args);
    }

    /**
     * Render a labelled interval in seconds with its actual minimum.
     *
     * @param array $args Registered field metadata.
     * @return void
     */
    public function renderNumberField(array $args): void
    {
        $key = $args['key'];
        $this->renderInput('number', $args['label_for'], sprintf('%s[%s]', $this->optionName, $key), $this->siteOptions->heartbeat->$key, 'small-text', [
            'min' => $args['min'],
            'step' => 1,
            'aria-describedby' => 'rrze-settings-heartbeat-' . $key . '-help',
        ]);
        echo ' ', esc_html(__('seconds', 'rrze-settings'));
        $this->renderHeartbeatHelp($args);
    }

    /**
     * Render role rules as readable JSON without changing their stored values.
     *
     * @param array $args Registered field metadata.
     * @return void
     */
    public function renderTextareaJsonField(array $args): void
    {
        $key = $args['key'];
        $value = $this->siteOptions->heartbeat->$key ?? [];
        $json = wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        printf(
            '<textarea id="%1$s" name="%2$s" rows="10" cols="70" class="large-text code" aria-describedby="%1$s-help" spellcheck="false">%3$s</textarea>',
            esc_attr($args['label_for']),
            esc_attr(sprintf('%s[%s]', $this->optionName, $key)),
            esc_textarea($json)
        );
        $this->renderHeartbeatHelp($args);
    }

    /**
     * Render exact administration hooks, one per line.
     *
     * @param array $args Registered field metadata.
     * @return void
     */
    public function renderTextareaLinesField(array $args): void
    {
        $key = $args['key'];
        $value = $this->siteOptions->heartbeat->$key ?? [];
        $text = is_array($value) ? implode("\n", $value) : '';
        printf(
            '<textarea id="%1$s" name="%2$s" rows="4" cols="70" class="large-text code" aria-describedby="%1$s-help" spellcheck="false">%3$s</textarea>',
            esc_attr($args['label_for']),
            esc_attr(sprintf('%s[%s]', $this->optionName, $key)),
            esc_textarea($text)
        );
        $this->renderHeartbeatHelp($args);
    }

    /**
     * Render concise guidance and optional details associated with a field.
     *
     * @param array $args Registered field metadata with key, title and help texts.
     * @return void
     */
    protected function renderHeartbeatHelp(array $args): void
    {
        printf('<p class="description" id="%1$s">%2$s</p>', esc_attr('rrze-settings-heartbeat-' . $args['key'] . '-help'), esc_html($args['description']));
        $this->renderSettingDetails($args['title'], $args['details']);
    }
}
