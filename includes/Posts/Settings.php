<?php

namespace RRZE\Settings\Posts;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the posts section of the plugin.
 *
 * @package RRZE\Settings\Posts
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-posts';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-posts-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Posts', 'rrze-settings'),
            __('Posts', 'rrze-settings'),
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
        $input['last_modified_custom_column'] = !empty($input['last_modified_custom_column']) ? 1 : 0;
        $input['page_list_table_dropdown'] = !empty($input['page_list_table_dropdown']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'posts');
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
            __('Posts', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'last_modified_custom_column',
            __('Last Modified', 'rrze-settings'),
            [$this, 'lastModifiedCustomColumnField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'page_list_table_dropdown',
            __('Pages List Dropdown', 'rrze-settings'),
            [$this, 'pageListTableDropdownField'],
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
        esc_html_e('Network administrators can centrally manage post settings across the multisite network. Options include adding a custom “Last Modified” column to post lists and a dropdown filter for pages—enhancing content visibility and management across all websites.', 'rrze-settings');
    }

    /**
     * Renders the last modified custom column field
     * 
     * @return void
     */
    public function lastModifiedCustomColumnField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-last-modified-custom-column" name="<?php printf('%s[last_modified_custom_column]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->posts->last_modified_custom_column, 1); ?>>
            <?php _e("Enables last modified custom column", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the page list table dropdown field
     * 
     * @return void
     */
    public function pageListTableDropdownField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-page-list-tabler-dropdown" name="<?php printf('%s[page_list_table_dropdown]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->posts->page_list_table_dropdown, 1); ?>>
            <?php _e("Enables pages list dropdown", 'rrze-settings'); ?>
        </label>
<?php
    }
}
