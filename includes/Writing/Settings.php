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

        $postLock = !empty($input['post_lock']) ? absint($input['post_lock']) : $this->minPostLock;
        $input['post_lock'] = $postLock > $this->minPostLock ? $postLock : $this->minPostLock;

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
?>
        <label>
            <input type="checkbox" id="rrze-settings-enable-post-lock" name="<?php printf('%s[enable_post_lock]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->enable_post_lock, 1); ?>>
            <?php _e("Enable the post lock feature", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Disables the custom fields metabox
     * 
     * @return void
     */
    public function disableCustomFieldsMetaboxField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-custom-fields-metabox" name="<?php printf('%s[disable_custom_fields_metabox]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_custom_fields_metabox, 1); ?>>
            <?php _e("Disables the custom fields metabox", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the allowed post types field
     * 
     * @return void
     */
    public function allowedPostTypeField()
    {
    ?>
        <textarea id="rrze-settings-allowed-post-types" cols="50" rows="5" name="<?php printf('%s[allowed_post_types]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->allowed_post_types)); ?></textarea>
        <p class="description"><?php _e('List of post types that always have the block editor enabled and no block restrictions. Enter one post type per line.', 'rrze-settings'); ?></p>

    <?php
    }
    /**
     * Renders the themes exceptions field
     * 
     * @return void
     */
    public function themesExceptionField()
    {
    ?>
        <textarea id="rrze-settings-themes-exceptions" cols="50" rows="5" name="<?php printf('%s[themes_exceptions]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->themes_exceptions)); ?></textarea>
        <p class="description"><?php _e('List of themes that always have the block editor enabled and no block restrictions. Enter one theme name per line.', 'rrze-settings'); ?></p>
    <?php
    }
    /**
     * Renders the websites exceptions field
     * 
     * @return void
     */
    public function websitesExceptionsField()
    {
    ?>
        <textarea id="rrze-settings-websites-exceptions" cols="50" rows="5" name="<?php printf('%s[websites_exceptions]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->websites_exceptions)); ?></textarea>
        <p class="description"><?php _e('List of websites ids that always have the block editor enabled and no block restrictions. Enter one website id per line.', 'rrze-settings'); ?></p>
    <?php
    }

    /**
     * Renders the allowed block types field
     * 
     * @return void
     */
    public function allowedBlockTypesField()
    {
    ?>
        <textarea id="rrze-settings-allowed-block-types" cols="50" rows="5" name="<?php printf('%s[allowed_block_types]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->allowed_block_types)); ?></textarea>
        <p class="description"><?php _e('List of allowed blocks. The * wildcard is supported at the end of a string.', 'rrze-settings'); ?></p>
        <p class="description"><?php _e('If this field is left empty, all registered blocks will be available.', 'rrze-settings'); ?></p>
        <p class="description"><?php _e('Enter one block type per line.', 'rrze-settings'); ?></p>
    <?php
    }

    /**
     * Renders the disabled block types field
     * 
     * @return void
     */
    public function disabledBlockTypesField()
    {
    ?>
        <textarea id="rrze-settings-disabled-block-types" cols="50" rows="5" name="<?php printf('%s[disabled_block_types]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->disabled_block_types)); ?></textarea>
        <p class="description"><?php _e('List of disabled blocks. The * wildcard is supported at the end of a string.', 'rrze-settings'); ?></p>
        <p class="description"><?php _e('Enter one block type per line.', 'rrze-settings'); ?></p>
    <?php
    }

    /**
     * Renders the checkbox for disabling block directory assets
     * 
     * @return void
     */
    public function disableBlockDirectoryAssetsField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-disable-block-directory-assets" name="<?php printf('%s[disable_block_directory_assets]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_block_directory_assets, 1); ?>>
        <label for="rrze-settings-disable-block-directory-assets">
            <?php _e('Remove Block Directory assets', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the checkbox for disabling remote block patterns
     * 
     * @return void
     */
    public function disableRemoteBlockPatternsField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-disable-remote-block-patterns" name="<?php printf('%s[disable_remote_block_patterns]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_remote_block_patterns, 1); ?>>
        <label for="rrze-settings-disable-remote-block-patterns">
            <?php _e('Disable remote block patterns', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the checkbox for disabling openverse media
     * 
     * @return void
     */
    public function disableOpenverseMediaField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-disable-openverse-media" name="<?php printf('%s[disable_openverse_media]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_openverse_media, 1); ?>>
        <label for="rrze-settings-disable-openverse-media">
            <?php _e('Disable loading of Openverse Media', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the checkbox for disabling font library UI
     * 
     * @return void
     */
    public function disableFontLibraryUIField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-disable-font-library-ui" name="<?php printf('%s[disable_font_library_ui]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_font_library_ui, 1); ?>>
        <label for="rrze-settings-disable-font-library-ui">
            <?php _e('Disable the Font Library user interface', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the checkbox for disabling code editor
     * 
     * @return void
     */
    public function disableCodeEditorField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-disable-code-editor" name="<?php printf('%s[disable_code_editor]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->disable_code_editor, 1); ?>>
        <label for="rrze-settings-disable-code-editor">
            <?php _e('Disable the Code Editor option from the Block Editor settings', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the code editor websites exceptions field
     * 
     * @return void
     */
    public function codeEditorWebsitesExceptionsField()
    {
    ?>
        <textarea id="rrze-settings-code-editor-websites-exceptions" cols="50" rows="5" name="<?php printf('%s[code_editor_websites_exceptions]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->code_editor_websites_exceptions)); ?></textarea>
        <p class="description"><?php _e('List of websites ids that always have the code editor enabled. Enter one website id per line.', 'rrze-settings'); ?></p>
    <?php
    }

    public function deactivatedPluginsField()
    {
    ?>
        <textarea id="rrze-settings-deactivated-plugins" cols="50" rows="5" name="<?php printf('%s[deactivated_plugins]', $this->optionName); ?>"><?php echo esc_attr($this->getTextarea($this->siteOptions->writing->deactivated_plugins)); ?></textarea>
        <p class="description"><?php _e('Plugins to disable when the block editor is active. Enter one plugin per line.', 'rrze-settings'); ?></p>
        <p class="description"><?php _e('The plugin name must be the same as in the plugins folder.', 'rrze-settings'); ?></p>
    <?php
    }

    /**
     * Renders all the fields for the block/classic editor
     * 
     * @return void
     */
    public function enableBlockEditorField()
    {
    ?>
        <input type="radio" id="rrze-settings-block-editor-enabled" name="<?php printf('%s[enable_block_editor]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->writing->enable_block_editor, 1); ?>>
        <label for="rrze-settings-block-editor-enabled">
            <?php _e('Block Editor', 'rrze-settings'); ?>
        </label>
        <br>
        <input type="radio" id="rrze-settings-block-editor-disabled" name="<?php printf('%s[enable_block_editor]', $this->optionName); ?>" value="0" <?php checked($this->siteOptions->writing->enable_block_editor, 0); ?>>
        <label for="rrze-settings-block-editor-disabled">
            <?php _e('Classic Editor', 'rrze-settings'); ?>
        </label>

    <?php
    }

    /**
     * Renders the post lock field
     * 
     * @return void
     */
    public function postLockField()
    {
    ?>
        <input type="text" class="small-text" id="rrze-settings-post-lock" name="<?php printf('%s[post_lock]', $this->optionName); ?>" value="<?php echo $this->options->writing->post_lock; ?>">
        <p class="description">
            <?php _e('Post Lock interval in seconds.', 'rrze-settings'); ?>
        </p>
    <?php
    }

    /**
     * Renders the checkbox for enabling the block editor
     * 
     * @return void
     */
    public function tryEnableBlockEditorField()
    {
    ?>
        <input type="checkbox" id="rrze-settings-try-block-editor-enabled" name="<?php printf('%s[try_enable_block_editor]', $this->optionName); ?>" value="1" <?php checked($this->options->writing->try_enable_block_editor, 1); ?>>
        <label for="rrze-settings-try-block-editor-enabled">
            <?php _e('Allow admins to switch editors', 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the standard editor field
     * 
     * @return void
     */
    public function setStandardEditorField()
    {
    ?>
        <input type="radio" id="rrze-settings-classic-editor" name="<?php printf('%s[enable_classic_editor]', $this->optionName); ?>" value="1" <?php checked($this->options->writing->enable_classic_editor, 1); ?>>
        <label for="rrze-settings-classic-editor">
            <?php _e('Classic editor', 'rrze-settings'); ?>
        </label>
        <br>
        <input type="radio" id="rrze-settings-block-editor" name="<?php printf('%s[enable_classic_editor]', $this->optionName); ?>" value="0" <?php checked($this->options->writing->enable_classic_editor, 0); ?>>
        <label for="rrze-settings-block-editor">
            <?php _e('Block editor', 'rrze-settings'); ?>
        </label>
<?php
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
