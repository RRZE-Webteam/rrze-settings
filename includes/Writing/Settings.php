<?php

namespace RRZE\Settings\Writing;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the writing section of the plugin.
 *
 * @package RRZE\Settings\Writing
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-writing';

    /**
     * Minimum post lock interval
     * 
     * @var int
     */
    protected $minPostLock = 150;

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Editor', 'rrze-settings'),
            __('Editor', 'rrze-settings'),
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

        $input['enable_post_lock'] = !empty($input['enable_post_lock']) ? 1 : 0;

        // The interval is edited on individual websites, not on this network page.
        $savedOptions = is_network_admin() ? $this->siteOptions : $this->options;
        $input['post_lock'] = max($this->minPostLock, (int) ($input['post_lock'] ?? $savedOptions->writing->post_lock ?? 150));

        $input['disable_custom_fields_metabox'] = !empty($input['disable_custom_fields_metabox']) ? 1 : 0;

        $input['enable_block_editor'] = !empty($input['enable_block_editor']) ? 1 : 0;
        if (is_network_admin()) {
            $input['enable_block_editor_new_sites'] = !empty($input['enable_block_editor_new_sites']) ? 1 : 0;
        }

        // Classic editor field values
        if (isset($input['allowed_post_types'])) {
            $input['allowed_post_types'] = $this->sanitizeTextarea($input['allowed_post_types']);
        }

        if (isset($input['themes_exceptions'])) {
            $input['themes_exceptions'] = $this->sanitizeTextarea($input['themes_exceptions']);
        }

        if (isset($input['websites_exceptions'])) {
            $exceptions = $this->sanitizeTextarea($input['websites_exceptions']);
            $exceptions = !empty($exceptions) ? $this->sanitizeWebsitesExceptions($exceptions) : '';
            $input['websites_exceptions'] = !empty($exceptions) ? $exceptions : '';
        }

        // Block editor field values
        $input['autosave_interval'] = max(60, (int) ($input['autosave_interval'] ?? $savedOptions->writing->autosave_interval ?? 60));
        $input['sync_autosave'] = !empty($input['sync_autosave']);

        if (isset($input['allowed_block_types'])) {
            $input['allowed_block_types'] = $this->sanitizeAllowedBlockTypes($input['allowed_block_types']);
        }

        if (isset($input['disabled_block_types'])) {
            $input['disabled_block_types'] = $this->sanitizeDisabledBlockTypes($input['disabled_block_types']);
        }

        if (isset($input['code_editor_websites_exceptions'])) {
            $exceptions = $this->sanitizeTextarea($input['code_editor_websites_exceptions']);
            $exceptions = !empty($exceptions) ? $this->sanitizeWebsitesExceptions($exceptions) : '';
            $input['code_editor_websites_exceptions'] = !empty($exceptions) ? $exceptions : '';
        }

        if (isset($input['deactivated_plugins'])) {
            $input['deactivated_plugins'] = $this->sanitizeTextarea($input['deactivated_plugins']);
        }

        $input['disable_block_directory_assets'] = !empty($input['disable_block_directory_assets']) ? 1 : 0;
        $input['disable_remote_block_patterns'] = !empty($input['disable_remote_block_patterns']) ? 1 : 0;
        $input['disable_openverse_media'] = !empty($input['disable_openverse_media']) ? 1 : 0;
        $input['disable_font_library_ui'] = !empty($input['disable_font_library_ui']) ? 1 : 0;
        $input['disable_code_editor'] = !empty($input['disable_code_editor']) ? 1 : 0;
        $input['disable_block_editor_custom_css'] = !empty($input['disable_block_editor_custom_css']) ? 1 : 0;

        if (isset($input['disable_block_editor_custom_css_themes'])) {
            $input['disable_block_editor_custom_css_themes'] = $this->sanitizeTextarea($input['disable_block_editor_custom_css_themes']);
        }

        // Try block editor field values
        if (is_super_admin() && !$this->siteOptions->writing->enable_block_editor) {
            $input['try_enable_block_editor'] = !empty($input['try_enable_block_editor']) ? 1 : 0;
        } else {
            $input['try_enable_block_editor'] = $this->options->writing->try_enable_block_editor;
        }

        return $this->parseOptionsValidate($input, 'writing');
    }

    /**
     * Define the ordered editor sections shared by field registration and navigation.
     *
     * @return array<string, array{title: string, description: string}> Section metadata.
     */
    protected function getEditorSections(): array
    {
        return [
            'rrze-settings-writing-main' => [
                'title' => __('Editor Selection', 'rrze-settings'),
                'description' => __('Choose the network editor policy and the initial editor for newly created websites.', 'rrze-settings'),
            ],
            'rrze-settings-writing-classic-editor' => [
                'title' => __('Editor and Block Exceptions', 'rrze-settings'),
                'description' => __('These exceptions enable the block editor where supported and bypass this plugin’s block lists. They remain relevant when the block editor is the network default. Other tool restrictions still apply.', 'rrze-settings'),
            ],
            'rrze-settings-writing-saving' => [
                'title' => __('Saving and Editing Locks', 'rrze-settings'),
                'description' => __('Configure automatic draft saving in the block editor and allow websites to adjust the editing lock duration.', 'rrze-settings'),
            ],
            'rrze-settings-writing-block-editor' => [
                'title' => __('Available Blocks', 'rrze-settings'),
                'description' => __('First limit the available blocks, then remove individual blocks with the exclusion list. The exceptions above take precedence.', 'rrze-settings'),
            ],
            'rrze-settings-writing-resources' => [
                'title' => __('Sources and Libraries', 'rrze-settings'),
                'description' => __('Control additional sources offered in the block editor. Hiding a source does not delete content already inserted.', 'rrze-settings'),
            ],
            'rrze-settings-writing-tools' => [
                'title' => __('Code and Custom Fields', 'rrze-settings'),
                'description' => __('Configure access to editor tools. Each restriction has its own scope; a tool being hidden is not a general content security rule.', 'rrze-settings'),
            ],
            'rrze-settings-writing-plugins' => [
                'title' => __('Plugin Compatibility', 'rrze-settings'),
                'description' => __('Manage website plugins that should not run alongside the block editor.', 'rrze-settings'),
            ],
        ];
    }

    /**
     * Render one network form with visible sections, anchor navigation and a save bar.
     *
     * @return void
     */
    public function optionsPage()
    {
        global $title;

        $sections = $this->getEditorSections();
        echo '<div class="wrap rrze-settings-editor-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-editor-index" aria-labelledby="rrze-settings-editor-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-editor-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-editor-section" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-editor-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-editor-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
            echo '</div><p>', esc_html($section['description']), '</p>';
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($this->menuPage, $sectionId);
            echo '</table></section>';
        }
        echo '<div class="rrze-settings-editor-save-bar">';
        submit_button(__('Save all changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary', false, ['aria-describedby' => 'rrze-settings-editor-save-help']);
        echo '<p id="rrze-settings-editor-save-help">', esc_html(__('Saves the settings in all sections on this page.', 'rrze-settings')), '</p></div></form></div>';
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        foreach ($this->getEditorSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], '__return_false', $this->menuPage);
        }

        $fields = [
            ['enable_block_editor', __('Default Editor', 'rrze-settings'), 'enableBlockEditorField', 'rrze-settings-writing-main', null],
            ['enable_block_editor_new_sites', __('New Websites', 'rrze-settings'), 'enableBlockEditorNewSitesField', 'rrze-settings-writing-main', null],
            ['allowed_post_types', __('Content Type Exceptions', 'rrze-settings'), 'allowedPostTypeField', 'rrze-settings-writing-classic-editor', 'rrze-settings-allowed-post-types'],
            ['themes_exceptions', __('Theme Exceptions', 'rrze-settings'), 'themesExceptionField', 'rrze-settings-writing-classic-editor', 'rrze-settings-themes-exceptions'],
            ['websites_exceptions', __('Website Exceptions', 'rrze-settings'), 'websitesExceptionsField', 'rrze-settings-writing-classic-editor', 'rrze-settings-websites-exceptions'],
            ['sync_autosave', __('Automatic Saving', 'rrze-settings'), 'syncAutosaveField', 'rrze-settings-writing-saving', null],
            ['autosave_interval', __('Custom Autosave Interval', 'rrze-settings'), 'autosaveIntervalField', 'rrze-settings-writing-saving', 'rrze-settings-autosave-interval'],
            ['enable_post_lock', __('Editing Lock Duration', 'rrze-settings'), 'enablePostLockField', 'rrze-settings-writing-saving', null],
            ['allowed_block_types', __('Allowed Blocks', 'rrze-settings'), 'allowedBlockTypesField', 'rrze-settings-writing-block-editor', 'rrze-settings-allowed-block-types'],
            ['disabled_block_types', __('Excluded Blocks', 'rrze-settings'), 'disabledBlockTypesField', 'rrze-settings-writing-block-editor', 'rrze-settings-disabled-block-types'],
            ['disable_block_directory_assets', __('Block Directory', 'rrze-settings'), 'disableBlockDirectoryAssetsField', 'rrze-settings-writing-resources', null],
            ['disable_remote_block_patterns', __('Online Block Patterns', 'rrze-settings'), 'disableRemoteBlockPatternsField', 'rrze-settings-writing-resources', null],
            ['disable_openverse_media', __('Openverse Media', 'rrze-settings'), 'disableOpenverseMediaField', 'rrze-settings-writing-resources', null],
            ['disable_font_library_ui', __('Font Library', 'rrze-settings'), 'disableFontLibraryUIField', 'rrze-settings-writing-resources', null],
            ['disable_code_editor', __('Code Editor', 'rrze-settings'), 'disableCodeEditorField', 'rrze-settings-writing-tools', null],
            ['code_editor_websites_exceptions', __('Code Editor Exceptions', 'rrze-settings'), 'codeEditorWebsitesExceptionsField', 'rrze-settings-writing-tools', 'rrze-settings-code-editor-websites-exceptions'],
            ['disable_block_editor_custom_css', __('Custom Block CSS', 'rrze-settings'), 'disableBlockEditorCustomCssField', 'rrze-settings-writing-tools', null],
            ['disable_block_editor_custom_css_themes', __('Themes for the CSS Restriction', 'rrze-settings'), 'disableBlockEditorCustomCssThemesField', 'rrze-settings-writing-tools', 'rrze-settings-disable-block-editor-custom-css-themes'],
            ['disable_custom_fields_metabox', __('Custom Fields', 'rrze-settings'), 'disableCustomFieldsMetaboxField', 'rrze-settings-writing-tools', null],
            ['deactivated_plugins', __('Automatically Deactivated Plugins', 'rrze-settings'), 'deactivatedPluginsField', 'rrze-settings-writing-plugins', 'rrze-settings-deactivated-plugins'],
        ];
        foreach ($fields as [$key, $label, $callback, $sectionId, $labelFor]) {
            add_settings_field($key, $label, [$this, $callback], $this->menuPage, $sectionId, $labelFor ? ['label_for' => $labelFor] : []);
        }
    }

    /**
     * Registers a setting and its data
     * 
     * @return void
     */
    public function adminPage()
    {
        register_setting(
            'writing',
            $this->optionName,
            [$this, 'optionsValidate']
        );

        if ($this->siteOptions->writing->enable_post_lock) {
            add_settings_field(
                $this->optionName . '_post_lock',
                __('Post Lock Interval', 'rrze-settings'),
                [$this, 'postLockField'],
                'writing'
            );
        }

        if (
            !$this->siteOptions->writing->enable_block_editor &&
            (
                is_super_admin() ||
                $this->options->writing->try_enable_block_editor
            )
        ) {
            add_settings_field(
                $this->optionName . '_enable_classic_editor',
                __('Default Editor', 'rrze-settings'),
                [$this, 'setStandardEditorField'],
                'writing'
            );
        }

        if (
            is_super_admin() &&
            !$this->siteOptions->writing->enable_block_editor
        ) {
            add_settings_field(
                $this->optionName . '_try_enable_block_editor',
                __('Allow switching editors', 'rrze-settings'),
                [$this, 'tryEnableBlockEditorField'],
                'writing'
            );
        }
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        echo '<p>';
        echo esc_html(__('Configure editor selection, blocks and editing tools across the network. Changes take effect after saving. All sections remain visible so that exceptions and dependencies can be reviewed together.', 'rrze-settings'));
        echo '</p>';
    }

    /**
     * Renders the checkbox for enabling the post lock feature
     * 
     * @return void
     */
    public function enablePostLockField()
    {
        $this->renderCheckbox('rrze-settings-enable-post-lock', sprintf('%s[enable_post_lock]', $this->optionName), $this->siteOptions->writing->enable_post_lock, __('Allow websites to configure the editing lock duration', 'rrze-settings'));
        $this->renderDescription(__('Allows an individual lock duration under Settings > Writing on each website. Disabling this option keeps the normal WordPress editing lock; it does not turn locking off.', 'rrze-settings'));
        $this->renderSettingDetails(__('Editing Lock Duration', 'rrze-settings'), [
            __('The lock detects when another person is editing the same content. The website value is entered in seconds; default and minimum when saving are 150. This is a lock timeout, not a maximum editing session length.', 'rrze-settings'),
        ]);
    }

    /**
     * Disables the custom fields metabox
     * 
     * @return void
     */
    public function disableCustomFieldsMetaboxField()
    {
        $this->renderCheckbox('rrze-settings-disable-custom-fields-metabox', sprintf('%s[disable_custom_fields_metabox]', $this->optionName), $this->siteOptions->writing->disable_custom_fields_metabox, __('Hide the standard Custom Fields box', 'rrze-settings'));
        $this->renderDescription(__('Removes the standard WordPress Custom Fields box from editing screens. Stored custom-field values remain intact.', 'rrze-settings'));
        $this->renderSettingDetails(__('Custom Fields', 'rrze-settings'), [
            __('This affects the standard metadata box, not all custom interfaces supplied by plugins such as ACF. Disabled: WordPress controls the availability of its standard box again.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the allowed post types field
     * 
     * @return void
     */
    public function allowedPostTypeField()
    {
        $this->renderTextarea('rrze-settings-allowed-post-types', sprintf('%s[allowed_post_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->allowed_post_types));
        $this->renderDescription(__('Enter one technical content type per line, such as page or newsletter. These types may use the block editor and bypass the allowed and excluded block lists.', 'rrze-settings'));
        $this->renderSettingDetails(__('Content Type Exceptions', 'rrze-settings'), [
            __('Use the registered identifier, not the translated label. The type must support the editor and REST API. These types are also exempt from the code-editor restriction; source and CSS restrictions still apply. Empty: no content-type exemptions.', 'rrze-settings'),
        ]);
    }
    /**
     * Renders the themes exceptions field
     * 
     * @return void
     */
    public function themesExceptionField()
    {
        $this->renderTextarea('rrze-settings-themes-exceptions', sprintf('%s[themes_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->themes_exceptions));
        $this->renderDescription(__('Enter one exact active theme name per line. Websites using these themes may use the block editor without this plugin’s block-list restrictions.', 'rrze-settings'));
        $this->renderSettingDetails(__('Theme Exceptions', 'rrze-settings'), [
            __('Use the displayed theme name, such as FAU-Elemental, including capitalization. This list does not match folder names or automatically match a parent theme when a child theme is active. Empty: no theme exemptions.', 'rrze-settings'),
        ]);
    }
    /**
     * Renders the websites exceptions field
     * 
     * @return void
     */
    public function websitesExceptionsField()
    {
        $this->renderTextarea('rrze-settings-websites-exceptions', sprintf('%s[websites_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->websites_exceptions));
        $this->renderDescription(__('Enter one website ID per line. These websites may use the block editor without this plugin’s block-list restrictions. Empty: no website exemptions.', 'rrze-settings'));
        $this->renderSettingDetails(__('Website Exceptions', 'rrze-settings'), [
            __('Example: enter 12 and 34 on separate lines. After saving, the website URL is appended for orientation. Existing lines such as 12 - https://example.org remain valid. Code-editor access has a separate exception list below.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the allowed block types field
     * 
     * @return void
     */
    public function allowedBlockTypesField()
    {
        $this->renderTextarea('rrze-settings-allowed-block-types', sprintf('%s[allowed_block_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->allowed_block_types));
        $this->renderDescription(__('Enter one block identifier per line, such as core/heading. Empty means all registered blocks, minus the exclusions below. This controls the selection for new blocks; existing content is retained.', 'rrze-settings'));
        $this->renderSettingDetails(__('Allowed Blocks', 'rrze-settings'), [
            __('Use namespace/block or namespace/* for a whole provider, such as core/*. Arbitrary patterns such as core/image* are not supported. A non-empty list without core/* automatically retains core/paragraph and core/missing.', 'rrze-settings'),
            __('The exclusions are applied afterwards, and the exemptions above bypass both lists. If filtering produces no blocks at all, the current implementation falls back to the previous WordPress allowance; this is not a way to disable every block.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the disabled block types field
     * 
     * @return void
     */
    public function disabledBlockTypesField()
    {
        $this->renderTextarea('rrze-settings-disabled-block-types', sprintf('%s[disabled_block_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->disabled_block_types));
        $this->renderDescription(__('Removes these block types from the selection for inserting new blocks. Existing blocks remain stored and can still be displayed and edited while their provider is available.', 'rrze-settings'));
        $this->renderSettingDetails(__('Excluded Blocks', 'rrze-settings'), [
            __('Enter one identifier per line, for example core/html, or use a provider pattern such as example/*. Exclusions take precedence over the allowed list, except for the content types, themes and websites explicitly exempted above. Empty: no additional exclusions.', 'rrze-settings'),
            __('Variation rules such as core/embed:youtube target only a variation. Their editor script currently loads only when the content-type exception list is non-empty and no exception applies to the current content. Verify variation rules in the actual editor.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling block directory assets
     * 
     * @return void
     */
    public function disableBlockDirectoryAssetsField()
    {
        $this->renderCheckbox('rrze-settings-disable-block-directory-assets', sprintf('%s[disable_block_directory_assets]', $this->optionName), $this->siteOptions->writing->disable_block_directory_assets, __('Hide the block directory in the editor', 'rrze-settings'));
        $this->renderDescription(__('Removes the WordPress block-directory integration used to discover additional block plugins in the editor. Already installed blocks remain available according to the block lists.', 'rrze-settings'));
        $this->renderSettingDetails(__('Block Directory', 'rrze-settings'), [
            __('This affects editor suggestions, not general plugin installation permissions. Disabled: WordPress may offer the directory again where permissions and editor support allow it.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling remote block patterns
     * 
     * @return void
     */
    public function disableRemoteBlockPatternsField()
    {
        $this->renderCheckbox('rrze-settings-disable-remote-block-patterns', sprintf('%s[disable_remote_block_patterns]', $this->optionName), $this->siteOptions->writing->disable_remote_block_patterns, __('Disable patterns from the WordPress online directory', 'rrze-settings'));
        $this->renderDescription(__('Stops WordPress from loading block patterns from its online directory. Locally registered patterns and already inserted content remain available.', 'rrze-settings'));
        $this->renderSettingDetails(__('Online Block Patterns', 'rrze-settings'), [
            __('Useful when the available templates should come from your themes and plugins. Disabled: remote patterns may be loaded again. This does not disable every external service used by plugins.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling openverse media
     * 
     * @return void
     */
    public function disableOpenverseMediaField()
    {
        $this->renderCheckbox('rrze-settings-disable-openverse-media', sprintf('%s[disable_openverse_media]', $this->optionName), $this->siteOptions->writing->disable_openverse_media, __('Hide Openverse in the block editor', 'rrze-settings'));
        $this->renderDescription(__('Hides the Openverse media source in the block editor. The website media library and previously inserted images remain available.', 'rrze-settings'));
        $this->renderSettingDetails(__('Openverse Media', 'rrze-settings'), [
            __('Disabled: the editor may offer Openverse again. This setting does not remove existing media or change their licenses.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling font library UI
     * 
     * @return void
     */
    public function disableFontLibraryUIField()
    {
        $this->renderCheckbox('rrze-settings-disable-font-library-ui', sprintf('%s[disable_font_library_ui]', $this->optionName), $this->siteOptions->writing->disable_font_library_ui, __('Hide the font library in the block editor', 'rrze-settings'));
        $this->renderDescription(__('Disables the font library interface in the block editor. It does not delete installed fonts or change fonts already used by the theme.', 'rrze-settings'));
        $this->renderSettingDetails(__('Font Library', 'rrze-settings'), [
            __('The separate font-library administration setting is managed under Advanced. Disabling this restriction alone does not guarantee that a font library will be offered by the active editor and theme.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling code editor
     * 
     * @return void
     */
    public function disableCodeEditorField()
    {
        $this->renderCheckbox('rrze-settings-disable-code-editor', sprintf('%s[disable_code_editor]', $this->optionName), $this->siteOptions->writing->disable_code_editor, __('Hide code editing for website users', 'rrze-settings'));
        $this->renderDescription(__('Hides the code-editing option in the block editor for regular website users. Super administrators, exempted content types and the websites listed below retain it.', 'rrze-settings'));
        $this->renderSettingDetails(__('Code Editor', 'rrze-settings'), [
            __('This controls the editor interface, not all ways of entering HTML. Exclude core/html separately if it should not be offered as a new block. Theme-file editing and the classic editor are not controlled by this option.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for disabling Custom CSS support in the Block Editor
     *
     * @return void
     */
    public function disableBlockEditorCustomCssField()
    {
        $this->renderCheckbox('rrze-settings-disable-block-editor-custom-css', sprintf('%s[disable_block_editor_custom_css]', $this->optionName), $this->siteOptions->writing->disable_block_editor_custom_css, __('Disable custom CSS support for blocks', 'rrze-settings'));
        $this->renderDescription(__('Disables Custom CSS support in block metadata for the themes selected below. An empty theme list applies the restriction to all themes.', 'rrze-settings'));
        $this->renderSettingDetails(__('Custom Block CSS', 'rrze-settings'), [
            __('This targets block-specific Custom CSS support. It does not remove stored CSS or act as a general CSS filter. The restriction also applies to super administrators; editor and block exemptions do not bypass it.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the theme list for disabling Custom CSS support in the Block Editor
     *
     * @return void
     */
    public function disableBlockEditorCustomCssThemesField()
    {
        $this->renderTextarea('rrze-settings-disable-block-editor-custom-css-themes', sprintf('%s[disable_block_editor_custom_css_themes]', $this->optionName), $this->getTextarea($this->siteOptions->writing->disable_block_editor_custom_css_themes));
        $this->renderDescription(__('Used only while the CSS restriction above is enabled. Enter one theme name or theme folder per line. Empty means all themes.', 'rrze-settings'));
        $this->renderSettingDetails(__('Themes for the CSS Restriction', 'rrze-settings'), [
            __('Matching ignores capitalization and checks the active theme name, stylesheet folder and parent-theme folder. Example: fau-elemental also covers child themes using that parent folder. These are affected themes, not exemptions.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the code editor websites exceptions field
     * 
     * @return void
     */
    public function codeEditorWebsitesExceptionsField()
    {
        $this->renderTextarea('rrze-settings-code-editor-websites-exceptions', sprintf('%s[code_editor_websites_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->code_editor_websites_exceptions));
        $this->renderDescription(__('Enter one website ID per line to exempt it from the code-editor restriction above. These entries do not grant permission to edit content.', 'rrze-settings'));
        $this->renderSettingDetails(__('Code Editor Exceptions', 'rrze-settings'), [
            __('Example: enter 12 for a website whose editors need source-code access. Saving appends the website URL. Empty: no website exceptions; super administrators and exempted content types remain exempt.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain plugin file identifiers and the persistent scope of automatic deactivation.
     *
     * @return void
     */
    public function deactivatedPluginsField()
    {
        $this->renderTextarea('rrze-settings-deactivated-plugins', sprintf('%s[deactivated_plugins]', $this->optionName), $this->getTextarea($this->siteOptions->writing->deactivated_plugins));
        $this->renderDescription(__('Deactivates listed website plugins when the website uses the block editor and its administration loads. Network-active plugins are skipped. Removing an entry does not reactivate the plugin.', 'rrze-settings'));
        $this->renderSettingDetails(__('Automatically Deactivated Plugins', 'rrze-settings'), [
            __('Enter the plugin file path relative to the plugins directory, one per line, for example tinymce-advanced/tinymce-advanced.php. A display name or folder name alone is insufficient. Empty: no automatic deactivations.', 'rrze-settings'),
            __('This is a persistent deactivation, not just hiding the plugin in the editor. It can affect other parts of the website. A block-editor exemption for individual content does not by itself trigger this website-level rule.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders all the fields for the block/classic editor
     * 
     * @return void
     */
    public function enableBlockEditorField()
    {
        echo '<fieldset><legend class="screen-reader-text">', esc_html__('Default Editor', 'rrze-settings'), '</legend>';
        $name = sprintf('%s[enable_block_editor]', $this->optionName);
        printf('<label><input type="radio" id="rrze-settings-block-editor-enabled" name="%1$s" value="1" %2$s> %3$s</label><br><label><input type="radio" id="rrze-settings-block-editor-disabled" name="%1$s" value="0" %4$s> %5$s</label>', esc_attr($name), checked($this->siteOptions->writing->enable_block_editor, 1, false), esc_html__('Block Editor', 'rrze-settings'), checked($this->siteOptions->writing->enable_block_editor, 0, false), esc_html__('Classic Editor', 'rrze-settings'));
        echo '</fieldset>';
        $this->renderDescription(__('Block Editor makes it the network standard. Classic Editor allows the classic editor while retaining website-level editor choices and the exemptions below.', 'rrze-settings'));
        $this->renderSettingDetails(__('Default Editor', 'rrze-settings'), [
            __('Changing this setting does not convert existing content. Test representative pages before switching. Content types must support the editor and REST API for block editing.', 'rrze-settings'),
            __('With Classic Editor selected, super administrators can choose an editor under Settings > Writing on each website and allow website administrators to switch. The network website list also offers an Editor column and filter.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the checkbox for enabling the block editor for new sites
     * 
     * @return void
     */
    public function enableBlockEditorNewSitesField()
    {
        $this->renderCheckbox('rrze-settings-enable-block-editor-new-sites', sprintf('%s[enable_block_editor_new_sites]', $this->optionName), $this->siteOptions->writing->enable_block_editor_new_sites, __('Start new websites with the block editor', 'rrze-settings'));
        $this->renderDescription(__('Starts newly created websites with the block editor when the network otherwise permits the classic editor. Existing websites are not changed.', 'rrze-settings'));
        $this->renderSettingDetails(__('New Websites', 'rrze-settings'), [
            __('Example: keep existing classic-editor websites as they are while new projects start with blocks. If Block Editor is already the network standard, it applies to new websites regardless of this checkbox.', 'rrze-settings'),
        ]);
    }

    /**
     * Renders the post lock field
     * 
     * @return void
     */
    public function postLockField()
    {
        $this->renderInput('number', 'rrze-settings-post-lock', sprintf('%s[post_lock]', $this->optionName), $this->options->writing->post_lock, 'small-text', ['min' => $this->minPostLock, 'step' => 1]);
        $this->renderDescription(__('Lock timeout in seconds. Default and minimum when saving: 150. This does not limit the duration of an editing session.', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for enabling the block editor
     * 
     * @return void
     */
    public function tryEnableBlockEditorField()
    {
        $this->renderCheckbox('rrze-settings-try-block-editor-enabled', sprintf('%s[try_enable_block_editor]', $this->optionName), $this->options->writing->try_enable_block_editor, __('Allow admins to switch editors', 'rrze-settings'));
    }

    /**
     * Renders the standard editor field
     * 
     * @return void
     */
    public function setStandardEditorField()
    {
        $name = sprintf('%s[enable_classic_editor]', $this->optionName);
        printf('<label><input type="radio" id="rrze-settings-classic-editor" name="%1$s" value="1" %2$s> %3$s</label><br><label><input type="radio" id="rrze-settings-block-editor" name="%1$s" value="0" %4$s> %5$s</label>', esc_attr($name), checked($this->options->writing->enable_classic_editor, 1, false), esc_html__('Classic editor', 'rrze-settings'), checked($this->options->writing->enable_classic_editor, 0, false), esc_html__('Block editor', 'rrze-settings'));
    }

    /**
     * Render autosave synchronization and explain its effective interval formula.
     *
     * @return void
     */
    public function syncAutosaveField(): void
    {
        $this->renderCheckbox('rrze-settings-sync-autosave', sprintf('%s[sync_autosave]', $this->optionName), $this->siteOptions->writing->sync_autosave, __('Derive the autosave interval from Heartbeat', 'rrze-settings'));
        $this->renderDescription(__('Uses twice the configured Heartbeat editor interval, with a minimum of 60 seconds. While enabled, the custom interval below is ignored.', 'rrze-settings'));
        $this->renderSettingDetails(__('Automatic Saving', 'rrze-settings'), [
            __('Example: 30 seconds under CMS > Heartbeat results in 60 seconds; 60 results in 120. The calculation uses the saved editor interval, not role-specific overrides. Autosave saves drafts and does not publish changes.', 'rrze-settings'),
        ]);
    }

    /**
     * Render a labelled autosave interval consistent with server-side validation.
     *
     * @return void
     */
    public function autosaveIntervalField(): void
    {
        $this->renderInput('number', 'rrze-settings-autosave-interval', sprintf('%s[autosave_interval]', $this->optionName), $this->siteOptions->writing->autosave_interval, 'small-text', ['min' => 60, 'step' => 1, 'aria-describedby' => 'rrze-settings-autosave-help']);
        echo ' ', esc_html__('seconds', 'rrze-settings');
        printf('<p id="rrze-settings-autosave-help" class="description">%s</p>', esc_html(__('Applies only when Heartbeat synchronization above is off. Default and minimum: 60 seconds. This setting applies to the block editor.', 'rrze-settings')));
    }

    /**
     * Get the textarea value
     * 
     * @param  array $option The option
     * @return string
     */
    protected function getTextarea($option)
    {
        if (!empty($option) && is_array($option)) {
            return implode(PHP_EOL, $option);
        }
        return '';
    }

    /**
     * Sanitize textarea field
     * 
     * @param  string $input The input
     * @return mixed
     */
    protected function sanitizeTextarea(string $input)
    {
        if (!empty($input)) {
            $input = explode(PHP_EOL, sanitize_textarea_field($input));
            $input = array_filter(array_map('trim', $input));
            sort($input);
            return !empty($input) ? $input : '';
        }
        return '';
    }

    /**
     * Sanitize websites exceptions
     * 
     * @param  array $sites The websites
     * @return array
     */
    protected function sanitizeWebsitesExceptions(array $sites)
    {
        $exceptions = [];
        foreach ($sites as $row) {
            $aryRow = explode(' - ', $row);
            $blogId = isset($aryRow[0]) ? trim($aryRow[0]) : '';
            if (!absint($blogId)) {
                continue;
            }
            switch_to_blog($blogId);
            $url = get_option('siteurl');
            restore_current_blog();
            if (!$url) {
                continue;
            }
            $exceptions[$blogId] = implode(' - ', [$blogId, $url]);
        }
        ksort($exceptions);
        return $exceptions;
    }

    /**
     * Sanitize allowed block types
     * 
     * @param  string $input The input
     * @return string
     */
    protected function sanitizeAllowedBlockTypes($input)
    {
        $allowedBlockTypes = $this->sanitizeTextarea($input);
        if (is_array($allowedBlockTypes) && count($allowedBlockTypes) > 0) {
            foreach ($allowedBlockTypes as $key => $block) {
                $value = array_filter(explode('/', $block));
                if (count($value) < 2) {
                    unset($allowedBlockTypes[$key]);
                } elseif (strpos($block, ':') !== false) {
                    unset($allowedBlockTypes[$key]);
                }
            }

            if (!empty($allowedBlockTypes) && !in_array('core/*', $allowedBlockTypes)) {
                $allowedBlockTypes[] = 'core/paragraph';
                $allowedBlockTypes[] = 'core/missing';
            }

            $allowedBlockTypes = array_unique(array_values($allowedBlockTypes));
            sort($allowedBlockTypes);
        }
        return $allowedBlockTypes ?: '';
    }

    /**
     * Sanitize disabled block types
     * 
     * @param  string $input The input
     * @return string
     */
    protected function sanitizeDisabledBlockTypes($input)
    {
        $disabledBlockTypes = $this->sanitizeTextarea($input);
        if (is_array($disabledBlockTypes) && count($disabledBlockTypes) > 0) {
            foreach ($disabledBlockTypes as $key => $value) {
                $value = array_filter(explode('/', $value));
                if (count($value) < 2) {
                    unset($disabledBlockTypes[$key]);
                }
            }
            $disabledBlockTypes = array_unique(array_values($disabledBlockTypes));
            sort($disabledBlockTypes);
        }
        return $disabledBlockTypes ?: '';
    }
}
