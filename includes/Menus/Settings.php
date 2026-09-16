<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the menus section of the plugin.
 *
 * @package RRZE\Settings\Menus
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-menus';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-menus-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Menus', 'rrze-settings'),
            __('Menus', 'rrze-settings'),
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
        $input['expand_collapse_menus'] = !empty($input['expand_collapse_menus']) ? 1 : 0;
        $input['menus_custom_column'] = !empty($input['menus_custom_column']) ? 1 : 0;
        $input['enhanced_menu_search'] = !empty($input['enhanced_menu_search']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'menus');
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
            __('Menus', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'expand_collapse_menus',
            __('Expand Collapse', 'rrze-settings'),
            [$this, 'expandCollapseMenusField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'menus_custom_column',
            __('Custom Column', 'rrze-settings'),
            [$this, 'menusCustomColumnField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enhanced_menu_search',
            __('Enhanced Search', 'rrze-settings'),
            [$this, 'enhancedMenuSearchField'],
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
        esc_html_e('Network administrators can manage menu-related settings across the multisite network from a centralized location. Options include enabling expand/collapse functionality for menu items, adding a custom column to the posts list, and improving menu search capabilities—enhancing efficiency and usability across all websites.', 'rrze-settings');
    }

    /**
     * Renders the expand/collapse menus field
     * 
     * @return void
     */
    public function expandCollapseMenusField()
    {
        $this->renderCheckbox('rrze-settings-expand-collapse-menus', sprintf('%s[expand_collapse_menus]', $this->optionName), $this->siteOptions->menus->expand_collapse_menus, __('Expands and collapses menu items', 'rrze-settings'));
    }

    /**
     * Renders the custom column field
     * 
     * @return void
     */
    public function menusCustomColumnField()
    {
        $this->renderCheckbox('rrze-settings-menus-custom-column', sprintf('%s[menus_custom_column]', $this->optionName), $this->siteOptions->menus->menus_custom_column, __('Creates a custom column (Menus) in the posts list', 'rrze-settings'));
    }

    /**
     * Renders the enhanced menu search field
     * 
     * @return void
     */
    public function enhancedMenuSearchField()
    {
        $this->renderCheckbox('rrze-settings-enhanced-menu-search', sprintf('%s[enhanced_menu_search]', $this->optionName), $this->siteOptions->menus->enhanced_menu_search, __('Enhanced menu search', 'rrze-settings'));
    }
}
