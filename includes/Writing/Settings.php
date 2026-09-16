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
    protected $minPostLock = 5;

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Writing', 'rrze-settings'),
            __('Writing', 'rrze-settings'),
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

        $input['post_lock'] = isset($input['post_lock']) ? (int) $input['post_lock'] : $input;
        ['post_lock'];
        $input['post_lock'] = max(150, $input['post_lock']);

        $input['disable_custom_fields_metabox'] = !empty($input['disable_custom_fields_metabox']) ? 1 : 0;

        $input['enable_block_editor'] = !empty($input['enable_block_editor']) ? 1 : 0;

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
        $input['autosave_interval'] = isset($raw['autosave_interval']) ? (int)$raw['autosave_interval'] : $input['autosave_interval'];
        $input['autosave_interval'] = max(60, $input['autosave_interval']);
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
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            'rrze-settings-writing-main',
            __('Writing', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'enable_post_lock',
            __('Post Lock', 'rrze-settings'),
            [$this, 'enablePostLockField'],
            $this->menuPage,
            'rrze-settings-writing-main'
        );

        add_settings_field(
            'disable_custom_fields_metabox',
            __('Disable Custom Fields Metabox', 'rrze-settings'),
            [$this, 'disableCustomFieldsMetaboxField'],
            $this->menuPage,
            'rrze-settings-writing-main'
        );

        add_settings_field(
            'enable_block_editor',
            __('Default Editor', 'rrze-settings'),
            [$this, 'enableBlockEditorField'],
            $this->menuPage,
            'rrze-settings-writing-main'
        );

        add_settings_field(
            'enable_block_editor_new_sites',
            __('Enable Block Editor for New Sites', 'rrze-settings'),
            [$this, 'enableBlockEditorNewSitesField'],
            $this->menuPage,
            'rrze-settings-writing-main'
        );

        if (!$this->siteOptions->writing->enable_block_editor) {
            // Classic editor section
            add_settings_section(
                'rrze-settings-writing-classic-editor',
                __('Classic Editor', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );

            // Block editor section
            add_settings_section(
                'rrze-settings-writing-block-editor',
                __('Block Editor', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );
        } else {
            // Block editor section
            add_settings_section(
                'rrze-settings-writing-block-editor',
                __('Block Editor', 'rrze-settings'),
                '__return_false',
                $this->menuPage
            );
        }

        // Classic editor fields
        add_settings_field(
            'allowed_post_types',
            __('Post Types Exceptions', 'rrze-settings'),
            [$this, 'allowedPostTypeField'],
            $this->menuPage,
            'rrze-settings-writing-classic-editor'
        );

        add_settings_field(
            'themes_exceptions',
            __('Themes Exceptions', 'rrze-settings'),
            [$this, 'themesExceptionField'],
            $this->menuPage,
            'rrze-settings-writing-classic-editor'
        );

        add_settings_field(
            'websites_exceptions',
            __('Websites Exceptions', 'rrze-settings'),
            [$this, 'websitesExceptionsField'],
            $this->menuPage,
            'rrze-settings-writing-classic-editor'
        );

        // Block editor fields
        add_settings_field(
            'autosave_interval',
            __('Autosave Interval', 'rrze-settings'),
            [$this, 'renderNumberField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor',
            [
                'key' => 'autosave_interval',
                'min' => 15,
                'step' => 1,
                'unit' => __('seconds', 'rrze-settings'),
                'description' => __('Interval in seconds for the autosave feature. Minimum is 15 seconds.', 'rrze-settings')
            ]
        );

        add_settings_field(
            'sync_autosave',
            __('Sync Autosave Interval With Heartbeat', 'rrze-settings'),
            [$this, 'renderCheckboxField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor',
            [
                'key' => 'sync_autosave',
                'description' => __('If enabled, the autosave interval will be synchronized with the Heartbeat API interval.', 'rrze-settings')
            ]
        );

        add_settings_field(
            'allowed_block_types',
            __('Allowed Blocks', 'rrze-settings'),
            [$this, 'allowedBlockTypesField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disabled_block_types',
            __('Disabled Blocks', 'rrze-settings'),
            [$this, 'disabledBlockTypesField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_block_directory_assets',
            __('Disable Block Directory Assets', 'rrze-settings'),
            [$this, 'disableBlockDirectoryAssetsField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_remote_block_patterns',
            __('Disable Remote Block Patterns', 'rrze-settings'),
            [$this, 'disableRemoteBlockPatternsField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_openverse_media',
            __('Disable Openverse Media', 'rrze-settings'),
            [$this, 'disableOpenverseMediaField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_font_library_ui',
            __('Disable Font Library UI', 'rrze-settings'),
            [$this, 'disableFontLibraryUIField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_code_editor',
            __('Disable Code Editor', 'rrze-settings'),
            [$this, 'disableCodeEditorField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_block_editor_custom_css',
            __('Disable Custom CSS', 'rrze-settings'),
            [$this, 'disableBlockEditorCustomCssField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'disable_block_editor_custom_css_themes',
            __('Disable Custom CSS Themes', 'rrze-settings'),
            [$this, 'disableBlockEditorCustomCssThemesField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'code_editor_websites_exceptions',
            __('Code Editor Websites Exceptions', 'rrze-settings'),
            [$this, 'codeEditorWebsitesExceptionsField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );

        add_settings_field(
            'deactivated_plugins',
            __('Deactivated Plugins', 'rrze-settings'),
            [$this, 'deactivatedPluginsField'],
            $this->menuPage,
            'rrze-settings-writing-block-editor'
        );
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
        esc_html_e('This page enables network administrators to manage writing-related settings across the multisite network. Available options include enabling or configuring post locking, selecting the default editor (block or classic), specifying allowed and disabled blocks, applying exceptions for particular websites, themes, or post types, and enforcing additional editor restrictions—facilitating a standardized and controlled writing environment across all websites.', 'rrze-settings');
    }

    /**
     * Renders the checkbox for enabling the post lock feature
     * 
     * @return void
     */
    public function enablePostLockField()
    {
        $this->renderCheckbox('rrze-settings-enable-post-lock', sprintf('%s[enable_post_lock]', $this->optionName), $this->siteOptions->writing->enable_post_lock, __('Enable the post lock feature', 'rrze-settings'));
    }

    /**
     * Disables the custom fields metabox
     * 
     * @return void
     */
    public function disableCustomFieldsMetaboxField()
    {
        $this->renderCheckbox('rrze-settings-disable-custom-fields-metabox', sprintf('%s[disable_custom_fields_metabox]', $this->optionName), $this->siteOptions->writing->disable_custom_fields_metabox, __('Disables the custom fields metabox', 'rrze-settings'));
    }

    /**
     * Renders the allowed post types field
     * 
     * @return void
     */
    public function allowedPostTypeField()
    {
        $this->renderTextarea('rrze-settings-allowed-post-types', sprintf('%s[allowed_post_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->allowed_post_types));
        $this->renderDescription(__('List of post types that always have the block editor enabled and no block restrictions. Enter one post type per line.', 'rrze-settings'));
    }
    /**
     * Renders the themes exceptions field
     * 
     * @return void
     */
    public function themesExceptionField()
    {
        $this->renderTextarea('rrze-settings-themes-exceptions', sprintf('%s[themes_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->themes_exceptions));
        $this->renderDescription(__('List of themes that always have the block editor enabled and no block restrictions. Enter one theme name per line.', 'rrze-settings'));
    }
    /**
     * Renders the websites exceptions field
     * 
     * @return void
     */
    public function websitesExceptionsField()
    {
        $this->renderTextarea('rrze-settings-websites-exceptions', sprintf('%s[websites_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->websites_exceptions));
        $this->renderDescription(__('List of websites ids that always have the block editor enabled and no block restrictions. Enter one website id per line.', 'rrze-settings'));
    }

    /**
     * Renders the allowed block types field
     * 
     * @return void
     */
    public function allowedBlockTypesField()
    {
        $this->renderTextarea('rrze-settings-allowed-block-types', sprintf('%s[allowed_block_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->allowed_block_types));
        $this->renderDescription(__('List of allowed blocks. The * wildcard is supported at the end of a string.', 'rrze-settings'));
        $this->renderDescription(__('If this field is left empty, all registered blocks will be available.', 'rrze-settings'));
        $this->renderDescription(__('Enter one block type per line.', 'rrze-settings'));
    }

    /**
     * Renders the disabled block types field
     * 
     * @return void
     */
    public function disabledBlockTypesField()
    {
        $this->renderTextarea('rrze-settings-disabled-block-types', sprintf('%s[disabled_block_types]', $this->optionName), $this->getTextarea($this->siteOptions->writing->disabled_block_types));
        $this->renderDescription(__('List of disabled blocks. The * wildcard is supported at the end of a string.', 'rrze-settings'));
        $this->renderDescription(__('Enter one block type per line.', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling block directory assets
     * 
     * @return void
     */
    public function disableBlockDirectoryAssetsField()
    {
        $this->renderCheckbox('rrze-settings-disable-block-directory-assets', sprintf('%s[disable_block_directory_assets]', $this->optionName), $this->siteOptions->writing->disable_block_directory_assets, __('Remove Block Directory assets', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling remote block patterns
     * 
     * @return void
     */
    public function disableRemoteBlockPatternsField()
    {
        $this->renderCheckbox('rrze-settings-disable-remote-block-patterns', sprintf('%s[disable_remote_block_patterns]', $this->optionName), $this->siteOptions->writing->disable_remote_block_patterns, __('Disable remote block patterns', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling openverse media
     * 
     * @return void
     */
    public function disableOpenverseMediaField()
    {
        $this->renderCheckbox('rrze-settings-disable-openverse-media', sprintf('%s[disable_openverse_media]', $this->optionName), $this->siteOptions->writing->disable_openverse_media, __('Disable loading of Openverse Media', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling font library UI
     * 
     * @return void
     */
    public function disableFontLibraryUIField()
    {
        $this->renderCheckbox('rrze-settings-disable-font-library-ui', sprintf('%s[disable_font_library_ui]', $this->optionName), $this->siteOptions->writing->disable_font_library_ui, __('Disable the Font Library user interface', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling code editor
     * 
     * @return void
     */
    public function disableCodeEditorField()
    {
        $this->renderCheckbox('rrze-settings-disable-code-editor', sprintf('%s[disable_code_editor]', $this->optionName), $this->siteOptions->writing->disable_code_editor, __('Disable the Code Editor option from the Block Editor settings', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for disabling Custom CSS support in the Block Editor
     *
     * @return void
     */
    public function disableBlockEditorCustomCssField()
    {
        $this->renderCheckbox('rrze-settings-disable-block-editor-custom-css', sprintf('%s[disable_block_editor_custom_css]', $this->optionName), $this->siteOptions->writing->disable_block_editor_custom_css, __('Disable Custom CSS support in Block Editor blocks', 'rrze-settings'));
    }

    /**
     * Renders the theme list for disabling Custom CSS support in the Block Editor
     *
     * @return void
     */
    public function disableBlockEditorCustomCssThemesField()
    {
        $this->renderTextarea('rrze-settings-disable-block-editor-custom-css-themes', sprintf('%s[disable_block_editor_custom_css_themes]', $this->optionName), $this->getTextarea($this->siteOptions->writing->disable_block_editor_custom_css_themes));
        $this->renderDescription(__('List of themes where Custom CSS support should be disabled in the Block Editor. Enter one theme name or stylesheet per line. Leave empty to apply to all themes.', 'rrze-settings'));
    }

    /**
     * Renders the code editor websites exceptions field
     * 
     * @return void
     */
    public function codeEditorWebsitesExceptionsField()
    {
        $this->renderTextarea('rrze-settings-code-editor-websites-exceptions', sprintf('%s[code_editor_websites_exceptions]', $this->optionName), $this->getTextarea($this->siteOptions->writing->code_editor_websites_exceptions));
        $this->renderDescription(__('List of websites ids that always have the code editor enabled. Enter one website id per line.', 'rrze-settings'));
    }

    public function deactivatedPluginsField()
    {
        $this->renderTextarea('rrze-settings-deactivated-plugins', sprintf('%s[deactivated_plugins]', $this->optionName), $this->getTextarea($this->siteOptions->writing->deactivated_plugins));
        $this->renderDescription(__('Plugins to disable when the block editor is active. Enter one plugin per line.', 'rrze-settings'));
        $this->renderDescription(__('The plugin name must be the same as in the plugins folder.', 'rrze-settings'));
    }

    /**
     * Renders all the fields for the block/classic editor
     * 
     * @return void
     */
    public function enableBlockEditorField()
    {
        $name = sprintf('%s[enable_block_editor]', $this->optionName);
        printf('<label><input type="radio" id="rrze-settings-block-editor-enabled" name="%1$s" value="1" %2$s> %3$s</label><br><label><input type="radio" id="rrze-settings-block-editor-disabled" name="%1$s" value="0" %4$s> %5$s</label>', esc_attr($name), checked($this->siteOptions->writing->enable_block_editor, 1, false), esc_html__('Block Editor', 'rrze-settings'), checked($this->siteOptions->writing->enable_block_editor, 0, false), esc_html__('Classic Editor', 'rrze-settings'));
    }

    /**
     * Renders the checkbox for enabling the block editor for new sites
     * 
     * @return void
     */
    public function enableBlockEditorNewSitesField()
    {
        $this->renderCheckbox('rrze-settings-enable-block-editor-new-sites', sprintf('%s[enable_block_editor_new_sites]', $this->optionName), $this->siteOptions->writing->enable_block_editor_new_sites, __('Enable Block Editor for new sites', 'rrze-settings'));
    }

    /**
     * Renders the post lock field
     * 
     * @return void
     */
    public function postLockField()
    {
        $this->renderInput('number', 'rrze-settings-post-lock', sprintf('%s[post_lock]', $this->optionName), $this->options->writing->post_lock, 'small-text', ['min' => $this->minPostLock, 'step' => 1]);
        $this->renderDescription(__('Post Lock interval in seconds.', 'rrze-settings'));
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
            esc_attr($this->optionName),
            esc_attr($key),
            checked(!empty($this->siteOptions->writing->$key), true, false),
            !empty($args['description']) ? esc_html($args['description']) : ''
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
        $key  = $args['key'];
        $min  = isset($args['min']) ? (int)$args['min'] : 0;
        $step = isset($args['step']) ? (int)$args['step'] : 1;
        printf(
            '<input type="number" name="%1$s[%2$s]" value="%3$s" min="%4$d" step="%5$d" class="small-text">',
            esc_attr($this->optionName),
            esc_attr($key),
            esc_attr((string) $this->siteOptions->writing->$key),
            absint($min),
            absint($step)
        );
        if (isset($args['unit'])) {
            printf(' <span>%s</span>', esc_html($args['unit']));
        }
        if (isset($args['description'])) {
            $this->renderDescription($args['description']);
        }
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
