<?php

namespace RRZE\Settings\Advanced;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;
use RRZE\Settings\Advanced\Sanitizer;

/**
 * Class Settings
 *
 * Manage network CSS, editor body classes and operations markers.
 *
 * @package RRZE\Settings\Advanced
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-advanced';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu(): void
    {
        add_submenu_page(
            'rrze-settings',
            __('CSS and Operations', 'rrze-settings'),
            __('CSS and Operations', 'rrze-settings'),
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
    public function optionsValidate($input): object
    {
        // FIELDS FOR GLOBAL CSS INJECTION START
        $input['frontend_style'] = trim(wp_strip_all_tags($input['frontend_style']));
        $input['backend_style'] = trim(wp_strip_all_tags($input['backend_style']));
        // FIELDS FOR GLOBAL CSS INJECTION END

        // FIELDS FOR IFRAME CLASS INJECTION START
        $input['block_editor_iframe_body_class'] = Sanitizer::sanitize_css_classes($input['block_editor_iframe_body_class'] ?? '');
        $input['block_editor_theme_exceptions'] = Sanitizer::sanitize_theme_slugs($input['block_editor_theme_exceptions'] ?? '');
        $input['block_editor_auto_theme_classes'] = isset($input['block_editor_auto_theme_classes']) ? 1 : 0;
        // FIELDS FOR IFRAME CLASS INJECTION END

        $input['sentry_mode'] = isset($input['sentry_mode']) ? 1 : 0;
        $input['sentry_mode_monitor_deletions'] = isset($input['sentry_mode_monitor_deletions']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'advanced');
    }

    /**
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getAdvancedSections(): array
    {
        return [
            'rrze-settings-advanced-css' => [
                'title' => __('Network-wide CSS', 'rrze-settings'),
                'callback' => 'cssSectionDescription',
            ],
            'rrze-settings-advanced-editor' => [
                'title' => __('CSS Classes in the Block Editor', 'rrze-settings'),
                'callback' => 'editorSectionDescription',
            ],
            'rrze-settings-advanced-sentry' => [
                'title' => __('Operations Marker (Sentry)', 'rrze-settings'),
                'callback' => 'sentrySectionDescription',
            ],
        ];
    }

    /**
     * Render the shared CSS and operations form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage(): void
    {
        global $title;

        $sections = $this->getAdvancedSections();
        echo '<div class="wrap rrze-settings-advanced-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-advanced-index" aria-labelledby="rrze-settings-advanced-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-advanced-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-advanced" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-advanced-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-advanced-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
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
     * Register all controls in one form, keeping dependent choices visible.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        foreach ($this->getAdvancedSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'frontend_style',
            __('Website CSS', 'rrze-settings'),
            [$this, 'frontendStyleField'],
            $this->menuPage,
            'rrze-settings-advanced-css',
            ['label_for' => 'rrze-settings-advanced-frontend-style']
        );

        add_settings_field(
            'backend_style',
            __('Administration CSS', 'rrze-settings'),
            [$this, 'backendStyleField'],
            $this->menuPage,
            'rrze-settings-advanced-css',
            ['label_for' => 'rrze-settings-advanced-backend-style']
        );

        add_settings_field(
            'block_editor_iframe_body_class',
            __('Additional Body Classes', 'rrze-settings'),
            [$this, 'BEIframeBodyClassField'],
            $this->menuPage,
            'rrze-settings-advanced-editor',
            ['label_for' => 'rrze-settings-advanced-blockeditor-iframe-body-class']
        );

        add_settings_field(
            'block_editor_auto_theme_classes',
            __('Automatic Theme Class', 'rrze-settings'),
            [$this, 'autoThemeClassesField'],
            $this->menuPage,
            'rrze-settings-advanced-editor'
        );

        add_settings_field(
            'block_editor_theme_exceptions',
            __('Themes Without Additional Classes', 'rrze-settings'),
            [$this, 'themeExceptionsField'],
            $this->menuPage,
            'rrze-settings-advanced-editor',
            ['label_for' => 'rrze-settings-advanced-block-editor-theme-exceptions']
        );

        add_settings_field(
            'sentry_mode',
            __('Update Marker', 'rrze-settings'),
            [$this, 'sentryModeField'],
            $this->menuPage,
            'rrze-settings-advanced-sentry'
        );

        add_settings_field(
            'sentry_mode_monitor_deletions',
            __('Deletion Marker', 'rrze-settings'),
            [$this, 'sentryModeMonitorDeletionsField'],
            $this->menuPage,
            'rrze-settings-advanced-sentry'
        );
    }

    /**
     * Introduce the network-wide CSS and operations settings.
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        echo '<p>', esc_html(__('Manage custom CSS, additional classes in the Block Editor and a local marker for server automation. These settings apply across the network. All sections are saved together.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope of the network-wide CSS settings.
     *
     * @return void
     */
    public function cssSectionDescription(): void
    {
        echo '<p>', esc_html(__('Add CSS for public websites and the WordPress administration. The two fields apply throughout the network. Enter CSS rules without style tags; an empty field adds no CSS.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope of the editor settings.
     *
     * @return void
     */
    public function editorSectionDescription(): void
    {
        echo '<p>', esc_html(__('Additional body classes let existing editor styles target the editing canvas. They apply to the iframe used by the Block Editor, not to saved blocks or the public website. Classes alone do not load any CSS.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope of the sentry settings.
     *
     * @return void
     */
    public function sentrySectionDescription(): void
    {
        echo '<p>', esc_html(__('Provide a local marker file that server automation can read after installations, updates and optionally deletions. This feature does not send notifications or configure an external monitoring service.', 'rrze-settings')), '</p>';
    }

    /**
     * Render a textarea with an associated description and optional code styling.
     *
     * @param string $id Field ID.
     * @param string $key Key in the existing advanced option group.
     * @param int $rows Visible rows.
     * @param string $description Translated short description.
     * @param bool $code Whether to use the WordPress code font.
     * @return void
     */
    protected function renderAdvancedTextarea(string $id, string $key, int $rows, string $description, bool $code = false): void
    {
        printf(
            '<textarea id="%1$s" name="%2$s[%3$s]" rows="%4$d" cols="55" class="%5$s" spellcheck="false" aria-describedby="%1$s-description">%6$s</textarea>',
            esc_attr($id),
            esc_attr($this->optionName),
            esc_attr($key),
            absint($rows),
            $code ? 'code' : '',
            esc_textarea($this->siteOptions->advanced->{$key})
        );
        printf('<p class="description" id="%1$s-description">%2$s</p>', esc_attr($id), esc_html($description));
    }

    /**
     * Render and explain the frontend_style setting.
     *
     * @return void
     */
    public function frontendStyleField(): void
    {
        $this->renderAdvancedTextarea(
            'rrze-settings-advanced-frontend-style',
            'frontend_style',
            8,
            __('Adds CSS to the public pages of all websites through the normal WordPress style output. Theme and plugin styles continue to apply.', 'rrze-settings'),
            true
        );
        $this->renderSettingDetails(__('Website CSS', 'rrze-settings'), [
            __('Example: .site-footer { margin-block-start: 2rem; } changes the spacing only where the theme uses that class. Check the selector on each affected theme.', 'rrze-settings'),
            __('This field does not automatically add styles to the Block Editor canvas or login screen. More specific selectors and later rules may override your CSS.', 'rrze-settings'),
            __('The form removes HTML tags but does not validate CSS syntax. To remove these additions, clear the field and save. If old styles remain visible, check the affected page caches.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the backend_style setting.
     *
     * @return void
     */
    public function backendStyleField(): void
    {
        $this->renderAdvancedTextarea(
            'rrze-settings-advanced-backend-style',
            'backend_style',
            8,
            __('Adds CSS to WordPress administration pages, including network administration. The rules can affect all users who see those pages.', 'rrze-settings'),
            true
        );
        $this->renderSettingDetails(__('Administration CSS', 'rrze-settings'), [
            __('Use selectors limited to the intended screen or component. Broad rules for buttons, inputs or navigation can also affect settings pages and editing tools.', 'rrze-settings'),
            __('The editing canvas inside an iframe is a separate document. Administration CSS does not automatically style that canvas; matching styles must be loaded into the editor separately.', 'rrze-settings'),
            __('This field changes appearance, not permissions. Hiding a control with CSS does not prevent access to its function. An empty field stops adding this CSS after saving.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the block_editor_iframe_body_class setting.
     *
     * @return void
     * @since 2.1.1
     */
    public function BEIframeBodyClassField(): void
    {
        $this->renderAdvancedTextarea(
            'rrze-settings-advanced-blockeditor-iframe-body-class',
            'block_editor_iframe_body_class',
            3,
            __('Enter class names separated by commas, for example site-layout, editor-preview. Use names without a leading dot and without CSS rules.', 'rrze-settings')
        );
        $this->renderSettingDetails(__('Additional Body Classes', 'rrze-settings'), [
            __('The classes are added to the body element of the editor-canvas iframe. Use letters a-z, digits, hyphens and underscores. Separate multiple names with commas, not just spaces or line breaks.', 'rrze-settings'),
            __('An empty field adds no manual classes. Automatic theme classes can still be active. Theme exceptions below disable both kinds of additions on matching websites.', 'rrze-settings'),
            __('These classes do not change the Additional CSS class(es) setting of an individual block. Without an editor-canvas iframe, this feature has no target.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the block_editor_auto_theme_classes setting.
     *
     * @return void
     */
    public function autoThemeClassesField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-block-editor-auto-theme-classes',
            $this->optionName . '[block_editor_auto_theme_classes]',
            $this->siteOptions->advanced->block_editor_auto_theme_classes,
            __('Add the active theme as an editor body class', 'rrze-settings')
        );
        $this->renderDescription(__('Adds wp-theme- followed by the active theme directory name to the editor iframe body, for example wp-theme-twentytwentyfive.', 'rrze-settings'));
        $this->renderSettingDetails(__('Automatic Theme Class', 'rrze-settings'), [
            __('With a child theme, the child theme directory name is used. The theme display name and the parent theme name are not added as separate classes.', 'rrze-settings'),
            __('Disabled: no automatic theme class is added by this feature. Manual classes still apply unless the active theme is listed as an exception.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the block_editor_theme_exceptions setting.
     *
     * @return void
     */
    public function themeExceptionsField(): void
    {
        $this->renderAdvancedTextarea(
            'rrze-settings-advanced-block-editor-theme-exceptions',
            'block_editor_theme_exceptions',
            3,
            __('Enter exact theme directory names separated by commas, for example twentytwentyfive, my-child-theme. Matching websites receive neither manual nor automatic body classes from this feature.', 'rrze-settings')
        );
        $this->renderSettingDetails(__('Themes Without Additional Classes', 'rrze-settings'), [
            __('Use the active theme directory name, including its spelling and case. For a child theme, enter the child theme directory. Display names and wildcards are not matched.', 'rrze-settings'),
            __('The exception only affects the class additions on this page. Network-wide CSS, the theme itself and existing classes from WordPress or other plugins remain active. An empty list makes no theme exceptions.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the sentry_mode setting.
     *
     * @return void
     */
    public function sentryModeField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-sentry-mode',
            $this->optionName . '[sentry_mode]',
            $this->siteOptions->advanced->sentry_mode,
            __('Write a marker after installations and updates', 'rrze-settings')
        );
        $this->renderDescription(__('Writes .rrze-settings-sentry.json in the WordPress base directory when WordPress reports a completed installation or update of core, plugins, themes or translations.', 'rrze-settings'));
        $this->renderSettingDetails(__('Update Marker', 'rrze-settings'), [
            __('The file describes the latest recorded event, including UTC time, action, type and affected items where available. Each new event replaces the previous contents; this is not an event history or a guarantee that every update succeeded.', 'rrze-settings'),
            __('A server-side tool must watch or read the file to react to changes. WordPress needs write access to the base directory. No separate marker is created for each website.', 'rrze-settings'),
            __('Enabling alone does not create a marker; the next matching event does. Disabling stops future writes but leaves an existing file in place. Direct file changes outside the WordPress update hooks are not recorded.', 'rrze-settings'),
        ]);
    }

    /**
     * Render and explain the sentry_mode_monitor_deletions setting.
     *
     * @return void
     */
    public function sentryModeMonitorDeletionsField(): void
    {
        $this->renderCheckbox(
            'rrze-settings-advanced-sentry-mode-monitor-deletions',
            $this->optionName . '[sentry_mode_monitor_deletions]',
            $this->siteOptions->advanced->sentry_mode_monitor_deletions,
            __('Also record successful plugin and theme deletions', 'rrze-settings')
        );
        $this->renderDescription(__('Requires the update marker above. After a successful plugin or theme deletion through WordPress, the same file is updated with action delete.', 'rrze-settings'));
        $this->renderSettingDetails(__('Deletion Marker', 'rrze-settings'), [
            __('This records deletions; it does not trigger or prevent them. Plugin deactivation and deletions of posts, media or websites are not included.', 'rrze-settings'),
            __('The choice remains saved when the update marker is disabled, but has no effect until that main option is enabled again.', 'rrze-settings'),
        ]);
    }
}
