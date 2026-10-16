<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles network settings and guidance for classic navigation menus.
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
            __('Classic Menu Management', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage,
            [
                'before_section' => '<div class="rrze-settings-menus">',
                'after_section' => '</div>',
            ]
        );

        add_settings_field(
            'expand_collapse_menus',
            __('Collapse Submenus', 'rrze-settings'),
            [$this, 'expandCollapseMenusField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'menus_custom_column',
            __('Menu Membership in the Page List', 'rrze-settings'),
            [$this, 'menusCustomColumnField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enhanced_menu_search',
            __('More Search Results', 'rrze-settings'),
            [$this, 'enhancedMenuSearchField'],
            $this->menuPage,
            $this->sectionName
        );
    }

    /**
     * Explain the network scope and distinguish classic menus from block navigation.
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        echo '<p>';
        esc_html_e('These settings help editors work with classic navigation menus under Appearance > Menus and find menu membership in the page list. They apply to all websites in the network after saving. They do not extend the Navigation block in the Site Editor.', 'rrze-settings');
        echo '</p>';
    }

    /**
     * Render submenu visibility guidance, persistence and disabling behavior.
     * 
     * @return void
     */
    public function expandCollapseMenusField()
    {
        $this->renderCheckbox('rrze-settings-expand-collapse-menus', sprintf('%s[expand_collapse_menus]', $this->optionName), $this->siteOptions->menus->expand_collapse_menus, __('Allow submenus to be collapsed in the menu editor', 'rrze-settings'));
        $this->renderDescription(__('Adds a toggle to menu items with subitems. Collapsing hides the subitems in the editor so that large menus are easier to navigate; the website menu remains unchanged.', 'rrze-settings'));
        $this->renderSettingDetails(__('Collapse Submenus', 'rrze-settings'), [
            __('Example: collapse the Study branch while editing Research. Expanding Study shows its subitems again; no menu items are deleted or moved.', 'rrze-settings'),
            __('Collapsed branches are remembered as a personal WordPress display setting. This feature requires JavaScript and the classic menu editor; items without subitems have no additional toggle.', 'rrze-settings'),
            __('Disabled: the additional toggles are removed and subitems are shown normally again. Menu structure, links and theme locations remain unchanged.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the page-list menu column, filtering links and detection limits.
     * 
     * @return void
     */
    public function menusCustomColumnField()
    {
        $this->renderCheckbox('rrze-settings-menus-custom-column', sprintf('%s[menus_custom_column]', $this->optionName), $this->siteOptions->menus->menus_custom_column, __('Show the Menus column in the page list', 'rrze-settings'));
        $this->renderDescription(__('Shows which classic menus contain a page. Clicking a menu name filters the page list to pages assigned to that menu. The column is added to Pages, not Posts.', 'rrze-settings'));
        $this->renderSettingDetails(__('Menu Membership in the Page List', 'rrze-settings'), [
            __('Example: a page appears in both Main Navigation and Footer. Both menu names are listed. Clicking Footer shows the pages assigned to that menu, rather than opening the menu editor.', 'rrze-settings'),
            __('The column checks saved menu items. Custom links entered as URLs are not resolved to their destination pages, and Navigation blocks are not evaluated. A dash means no menu membership was detected; it does not mean the page is unreachable.', 'rrze-settings'),
            __('Menus without an assigned theme location can also appear. The column shows saved membership, not whether the theme currently displays a menu. If the column is hidden, check Screen Options above the page list.', 'rrze-settings'),
            __('Disabled: the additional column and its menu filter are no longer provided. Pages and menu assignments remain stored.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the menu quick-search result limit without implying a new search algorithm.
     * 
     * @return void
     */
    public function enhancedMenuSearchField()
    {
        $this->renderCheckbox('rrze-settings-enhanced-menu-search', sprintf('%s[enhanced_menu_search]', $this->optionName), $this->siteOptions->menus->enhanced_menu_search, __('Show up to 100 results when searching for content to add', 'rrze-settings'));
        $this->renderDescription(__('Raises the result limit for content searches in the classic menu editor to 100 per search. Search criteria and result order remain unchanged.', 'rrze-settings'));
        $this->renderSettingDetails(__('More Search Results', 'rrze-settings'), [
            __('Example: under Appearance > Menus, open the Search tab in Pages, enter Study and select the matching pages to add. More matches can be displayed without changing the search term.', 'rrze-settings'),
            __('Applies to content selection, such as pages and posts, when adding menu items. It does not search the existing menu structure and does not raise the limit for category or tag searches, website search or the Navigation block.', 'rrze-settings'),
            __('A maximum of 100 matches is displayed; more specific search terms are still useful for large websites. Disabled: WordPress uses its regular limit again, which is 10 results by default.', 'rrze-settings'),
        ]);
    }
}
