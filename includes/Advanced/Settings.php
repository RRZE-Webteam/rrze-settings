<?php

namespace RRZE\Settings\Advanced;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the advanced section of the plugin.
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
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-advanced-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Advanced', 'rrze-settings'),
            __('Advanced', 'rrze-settings'),
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
        $input['frontend_style'] = trim(wp_strip_all_tags($input['frontend_style']));
        $input['backend_style'] = trim(wp_strip_all_tags($input['backend_style']));

        return $this->parseOptionsValidate($input, 'advanced');
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
            __('Advanced', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'frontend_style',
            __('Frontend Style', 'rrze-settings'),
            [$this, 'frontendStyleField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'backend_style',
            __('Backend Style', 'rrze-settings'),
            [$this, 'backendStyleField'],
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
        esc_html_e('This page enables network administrators to centrally manage custom CSS styles for both the frontend and backend of every website in a multisite network, offering fields to input and organize CSS code and ensure advanced customization and consistent branding across the entire network.', 'rrze-settings');
    }

    /**
     * Display the frontend_style field
     * 
     * @return void
     */
    public function frontendStyleField()
    {
        $css = esc_textarea($this->siteOptions->advanced->frontend_style);
        echo '<textarea rows="5" cols="55" id="rrze-settings-advanced-frontend-style" class="regular-text" name="', sprintf('%s[frontend_style]', $this->optionName), '">', $css, '</textarea>';
        echo '<p class="description">' . __('Enter the CSS code which will be added to the frontend of all websites.', 'rrze-settings') . '</p>';
    }

    /**
     * Display the backend_style field
     * 
     * @return void
     */
    public function backendStyleField()
    {
        $css = esc_textarea($this->siteOptions->advanced->backend_style);
        echo '<textarea rows="5" cols="55" id="rrze-settings-advanced-backend-style" class="regular-text" name="', sprintf('%s[backend_style]', $this->optionName), '">', $css, '</textarea>';
        echo '<p class="description">' . __('Enter the CSS code which will be added to the backend of all websites.', 'rrze-settings') . '</p>';
    }
}
