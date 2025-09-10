<?php

namespace RRZE\Settings\Discussion;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the discussion section of the plugin.
 *
 * @package RRZE\Settings\Discussion
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-discussion';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-discussion-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Discussion', 'rrze-settings'),
            __('Discussion', 'rrze-settings'),
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
        $input['default_settings'] = !empty($input['default_settings']) ? 1 : 0;
        $input['disable_avatars'] = !empty($input['disable_avatars']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'discussion');
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            $this->sectionName,
            __('Discussion', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'default_settings',
            __('Default Settings', 'rrze-settings'),
            [$this, 'defaultSettingsField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'disable_avatars',
            __('Disable Avatars', 'rrze-settings'),
            [$this, 'disableAvatarsField'],
            $this->menuPage,
            $this->sectionName
        );
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e('This page enables network administrators to centrally configure discussion settings across the multisite network, establish default options for newly created websites, disable features such as avatars, and enforce uniform, streamlined conversation policies throughout the network.', 'rrze-settings');
    }

    /**
     * Display the default_settings field
     */
    public function defaultSettingsField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-discussion-default-settings" name="<?php printf('%s[default_settings]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->discussion->default_settings, 1); ?>>
            <?php _e("Set default settings for new websites", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the disable_avatars field
     */
    public function disableAvatarsField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-discussion-disable-avatars" name="<?php printf('%s[disable_avatars]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->discussion->disable_avatars, 1); ?>>
            <?php _e("Disable avatars settings", 'rrze-settings'); ?>
        </label>
<?php
    }
}
