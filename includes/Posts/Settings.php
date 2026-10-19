<?php

namespace RRZE\Settings\Posts;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles network settings and guidance for posts and pages.
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
     * Settings section for the website search exclusion.
     *
     * @var string
     */
    protected $searchSectionName = 'rrze-settings-posts-search-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Posts and Pages', 'rrze-settings'),
            __('Posts and Pages', 'rrze-settings'),
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
        $sectionArgs = [
            'before_section' => '<div class="rrze-settings-posts">',
            'after_section' => '</div>',
        ];

        add_settings_section(
            $this->sectionName,
            __('List View', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage,
            $sectionArgs
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
            __('Parent Page Filter', 'rrze-settings'),
            [$this, 'pageListTableDropdownField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_section(
            $this->taxonomySectionName,
            __('Categories and Tags', 'rrze-settings'),
            [$this, 'taxonomySectionDescription'],
            $this->menuPage,
            $sectionArgs
        );

        add_settings_field(
            'taxonomy_page_category',
            __('Page Categories', 'rrze-settings'),
            [$this, 'taxonomyPageCategoryField'],
            $this->menuPage,
            $this->taxonomySectionName
        );
        add_settings_field(
            'taxonomy_page_tag',
            __('Page Tags', 'rrze-settings'),
            [$this, 'taxonomyPageTagField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_section(
            $this->searchSectionName,
            __('Website Search', 'rrze-settings'),
            [$this, 'searchSectionDescription'],
            $this->menuPage,
            $sectionArgs
        );

        add_settings_field(
            'exclude_nosearch_posts',
            __('Search Exclusion', 'rrze-settings'),
            [$this, 'excludeNosearchPostsField'],
            $this->menuPage,
            $this->searchSectionName
        );
    }

    /**
     * Explain the network-wide scope and the purpose of list enhancements.
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        echo '<p>';
        esc_html_e('These settings apply to all websites in the network after saving. The list options help editors find recently changed content and navigate page hierarchies in the WordPress administration.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Explain page taxonomies using familiar terms and clarify their local scope.
     *
     * @return void
     */
    public function taxonomySectionDescription()
    {
        echo '<p>';
        esc_html_e('Organize pages by topic using their own categories and tags. WordPress calls these groupings taxonomies. The features are enabled network-wide, but each website maintains its own terms and assignments.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Describe the scope of the optional website search exclusion.
     *
     * @return void
     */
    public function searchSectionDescription()
    {
        echo '<p>';
        esc_html_e('Control whether content marked with a specific tag appears in the regular WordPress search on the website.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Render search exclusion guidance, prerequisites and access limitations.
     *
     * @return void
     */
    public function excludeNosearchPostsField()
    {
        $this->renderCheckbox('rrze-settings-exclude-nosearch-posts', sprintf('%s[exclude_nosearch_posts]', $this->optionName), $this->siteOptions->taxonomies->exclude_nosearch_posts, __('Exclude content tagged nosearch from website search', 'rrze-settings'));
        $this->renderDescription(__('Hides tagged content from the regular WordPress search results on the website. This does not restrict access: direct links remain accessible according to the existing permissions.', 'rrze-settings'));
        $this->renderSettingDetails(__('Search Exclusion', 'rrze-settings'), [
            __('Create a tag whose slug is exactly nosearch on the relevant website and assign it to the content. The slug is the URL-friendly identifier, not just the displayed name. A category named nosearch has no effect.', 'rrze-settings'),
            __('For pages, enable Page Tags above. Posts use their normal tags. Media tags are also checked when enabled under Media and when attachments are included in the search. The option does not create or assign tags automatically.', 'rrze-settings'),
            __('Example: tag a landing page that should remain reachable through a direct link but should not appear in the website search. This is not a noindex instruction for Google or other external search engines.', 'rrze-settings'),
            __('Only the main WordPress search query on the public-facing website is filtered. Administration searches, separate search queries and external search services are not covered. Search plugins may change the result.', 'rrze-settings'),
            __('Disabled: this additional search exclusion is removed. Existing tags and assignments remain stored.', 'rrze-settings'),
        ]);
    }

    /**
     * Render page category guidance, grouping examples and disabling behavior.
     *
     * @return void
     */
    public function taxonomyPageCategoryField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-page-category', sprintf('%s[taxonomy_page_category]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_page_category, __('Enable categories for pages', 'rrze-settings'));
        $this->renderDescription(__('Adds a separate set of categories for pages, including parent and child categories. Page categories are independent of post categories and do not change the parent-child structure of pages.', 'rrze-settings'));
        $this->renderSettingDetails(__('Page Categories', 'rrze-settings'), [
            __('Example: group pages under Study and Research, with Degree Programs as a child category of Study. Assign categories in the page editor; the page list shows them and offers a filter when used categories are available.', 'rrze-settings'),
            __('Page categories can be used in navigation menus and public category archives. Their display depends on the theme; enabling this option does not add them to a menu automatically.', 'rrze-settings'),
            __('Disabled: the plugin stops providing page categories and their administration controls. Existing terms and assignments are not deleted and become available again when re-enabled.', 'rrze-settings'),
        ]);
    }

    /**
     * Render page tag guidance and explain how it relates to search exclusion.
     *
     * @return void
     */
    public function taxonomyPageTagField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-page-tag', sprintf('%s[taxonomy_page_tag]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_page_tag, __('Enable tags for pages', 'rrze-settings'));
        $this->renderDescription(__('Adds a separate set of tags for pages. Tags describe topics without a parent-child hierarchy and are independent of post tags.', 'rrze-settings'));
        $this->renderSettingDetails(__('Page Tags', 'rrze-settings'), [
            __('Example: assign application or accessibility to related pages. Tags can be assigned in the page editor, appear in the page list and can be used there as a filter when used tags are available.', 'rrze-settings'),
            __('Page tags are available in the editor and REST API, but are not registered as public tag archives or navigation menu entries. Enabling them does not automatically display tags in the website theme.', 'rrze-settings'),
            __('To exclude a page from website search, also enable Search Exclusion below and assign a page tag with the slug nosearch. Enabling Page Tags alone does not exclude anything.', 'rrze-settings'),
            __('Disabled: page tags are no longer provided by this plugin, so they cannot be used by its search exclusion. Existing terms and assignments remain stored for reactivation.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the enhanced date column and sorting for posts and pages.
     * 
     * @return void
     */
    public function lastModifiedCustomColumnField()
    {
        $this->renderCheckbox('rrze-settings-last-modified-custom-column', sprintf('%s[last_modified_custom_column]', $this->optionName), $this->siteOptions->posts->last_modified_custom_column, __('Show the last modification in the date column', 'rrze-settings'));
        $this->renderDescription(__('Extends the Date column in the post and page lists with the last modification. Clicking the column heading sorts by modification date.', 'rrze-settings'));
        $this->renderSettingDetails(__('Last Modified', 'rrze-settings'), [
            __('Example: find an older page that was revised recently by sorting the Date column by the latest modification. Published and scheduled content still shows its publication or scheduled date in the standard list view.', 'rrze-settings'),
            __('Applies to the administration lists for posts and pages, except the Trash view. It does not add a date to the public website or change publication dates.', 'rrze-settings'),
            __('Disabled: the usual WordPress date column is used again. The stored modification dates remain unchanged.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the parent page filter, descendants and available list views.
     * 
     * @return void
     */
    public function pageListTableDropdownField()
    {
        $this->renderCheckbox('rrze-settings-page-list-tabler-dropdown', sprintf('%s[page_list_table_dropdown]', $this->optionName), $this->siteOptions->posts->page_list_table_dropdown, __('Filter the page list by parent page', 'rrze-settings'));
        $this->renderDescription(__('Adds a dropdown to the page list. Selecting a page and applying the filter shows that page and all its descendants, including deeper levels.', 'rrze-settings'));
        $this->renderSettingDetails(__('Parent Page Filter', 'rrze-settings'), [
            __('Example: select Study to show that page together with Degree Programs, Application and their subpages. Choose All page levels and apply the filter to remove this restriction; other active list filters still apply.', 'rrze-settings'),
            __('The dropdown is available in the All and Published page views, not in status views such as Drafts or Trash. It lists published pages for selection and displays up to six hierarchy levels.', 'rrze-settings'),
            __('Disabled: this additional dropdown is removed. Page content, parent-child relationships and navigation menus remain unchanged.', 'rrze-settings'),
        ]);
    }
}
