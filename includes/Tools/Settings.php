<?php

namespace RRZE\Settings\Tools;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the tools section of the plugin.
 *
 * @package RRZE\Settings\Tools
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-tools';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-tools-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Tools', 'rrze-settings'),
            __('Tools', 'rrze-settings'),
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
        $input['disable_delete_site'] = !empty($input['disable_delete_site']) ? 1 : 0;
        $input['disable_privacy_options'] = !empty($input['disable_privacy_options']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'tools');
    }

    /**
     * Adds settings fields to the network admin page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            $this->sectionName,
            __('Tools', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'disable_delete_site',
            __('Delete Website', 'rrze-settings'),
            [$this, 'deleteSiteField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'disable_privacy_options',
            __('Privacy Options', 'rrze-settings'),
            [$this, 'privacyOptionsField'],
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
        esc_html_e('Network administrators can configure tools settings across the entire multisite network on this page, disable the “delete site” feature, and adjust privacy options—strengthening oversight and preventing unintended changes or deletions on every website.', 'rrze-settings');
    }

    /**
     * Renders the delete site field
     * 
     * @return void
     */
    public function deleteSiteField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-delete-site" name="<?php printf('%s[disable_delete_site]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->tools->disable_delete_site, 1); ?>>
            <?php _e("Disables the delete site feature", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the privacy options field
     * 
     * @return void
     */
    public function privacyOptionsField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-disable-privacy-options" name="<?php printf('%s[disable_privacy_options]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->tools->disable_privacy_options, 1); ?>>
            <?php _e("Disables the privacy settings", 'rrze-settings'); ?>
        </label>
<?php
    }
}
