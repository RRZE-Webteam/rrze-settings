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
     * Taxonomies section name
     *
     * @var string
     */
    protected $taxonomySectionName = 'rrze-settings-posts-taxonomies-section';

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
        $this->siteOptions->taxonomies->exclude_nosearch_posts = !empty($input['exclude_nosearch_posts']) ? 1 : 0;
        $this->siteOptions->taxonomies->taxonomy_page_category = !empty($input['taxonomy_page_category']) ? 1 : 0;
        $this->siteOptions->taxonomies->taxonomy_page_tag = !empty($input['taxonomy_page_tag']) ? 1 : 0;

        unset($input['exclude_nosearch_posts'], $input['taxonomy_page_category'], $input['taxonomy_page_tag']);

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

        add_settings_section(
            $this->taxonomySectionName,
            __('Taxonomien', 'rrze-settings'),
            [$this, 'taxonomySectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'taxonomy_page_category',
            __('Register Page Category', 'rrze-settings'),
            [$this, 'taxonomyPageCategoryField'],
            $this->menuPage,
            $this->taxonomySectionName
        );
        add_settings_field(
            'taxonomy_page_tag',
            __('Register Page Tag', 'rrze-settings'),
            [$this, 'taxonomyPageTagField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_field(
            'exclude_nosearch_posts',
            __('No-Search Posts', 'rrze-settings'),
            [$this, 'excludeNosearchPostsField'],
            $this->menuPage,
            $this->taxonomySectionName
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
     * Display the taxonomy section description
     *
     * @return void
     */
    public function taxonomySectionDescription()
    {
        esc_html_e('Configure taxonomies and search behavior for posts and pages across the multisite network.', 'rrze-settings');
    }

    /**
     * Renders the exclude nosearch posts field
     *
     * @return void
     */
    public function excludeNosearchPostsField()
    {
        $this->renderCheckbox('rrze-settings-exclude-nosearch-posts', sprintf('%s[exclude_nosearch_posts]', $this->optionName), $this->siteOptions->taxonomies->exclude_nosearch_posts, __('Exclude from search nosearch-tagged posts', 'rrze-settings'));
    }

    /**
     * Renders the taxonomy page category field
     *
     * @return void
     */
    public function taxonomyPageCategoryField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-page-category', sprintf('%s[taxonomy_page_category]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_page_category, __('Register page category taxonomy', 'rrze-settings'));
    }

    /**
     * Renders the taxonomy page tag field
     *
     * @return void
     */
    public function taxonomyPageTagField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-page-tag', sprintf('%s[taxonomy_page_tag]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_page_tag, __('Register page tag taxonomy', 'rrze-settings'));
    }

    /**
     * Renders the last modified custom column field
     * 
     * @return void
     */
    public function lastModifiedCustomColumnField()
    {
        $this->renderCheckbox('rrze-settings-last-modified-custom-column', sprintf('%s[last_modified_custom_column]', $this->optionName), $this->siteOptions->posts->last_modified_custom_column, __('Enables last modified custom column', 'rrze-settings'));
    }

    /**
     * Renders the page list table dropdown field
     * 
     * @return void
     */
    public function pageListTableDropdownField()
    {
        $this->renderCheckbox('rrze-settings-page-list-tabler-dropdown', sprintf('%s[page_list_table_dropdown]', $this->optionName), $this->siteOptions->posts->page_list_table_dropdown, __('Enables pages list dropdown', 'rrze-settings'));
    }
}
