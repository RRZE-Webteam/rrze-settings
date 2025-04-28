<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the taxonomies section of the plugin.
 *
 * @package RRZE\Settings\Taxonomies
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-taxonomies';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName = 'rrze-settings-taxonomies-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Taxonomies', 'rrze-settings'),
            __('Taxonomies', 'rrze-settings'),
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
        $input['exclude_nosearch_posts'] = !empty($input['exclude_nosearch_posts']) ? 1 : 0;
        $input['taxonomy_attachment_document'] = !empty($input['taxonomy_attachment_document']) ? 1 : 0;
        $input['taxonomy_page_category'] = !empty($input['taxonomy_page_category']) ? 1 : 0;
        $input['taxonomy_page_tag'] = !empty($input['taxonomy_page_tag']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'taxonomies');
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
            __('Taxonomies', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'taxonomy_attachment_document',
            __('Register Attachment Document', 'rrze-settings'),
            [$this, 'taxonomyAttachmentDocumentField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'taxonomy_page_category',
            __('Register Page Category', 'rrze-settings'),
            [$this, 'taxonomyPageCategoryField'],
            $this->menuPage,
            $this->sectionName
        );
        add_settings_field(
            'taxonomy_page_tag',
            __('Register Page Tag', 'rrze-settings'),
            [$this, 'taxonomyPageTagField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'exclude_nosearch_posts',
            __('No-Search Posts', 'rrze-settings'),
            [$this, 'excludeNosearchPostsField'],
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
        esc_html_e('Network administrators can configure taxonomy settings across the entire multisite network on this page, register custom taxonomies for attachments and pages, and exclude posts tagged with specific terms from search results—providing finer control over content organization and search behavior on each website.', 'rrze-settings');
    }

    /**
     * Renders the exclude nosearch posts field
     * 
     * @return void
     */
    public function excludeNosearchPostsField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-exclude-nosearch-posts" name="<?php printf('%s[exclude_nosearch_posts]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->taxonomies->exclude_nosearch_posts, 1); ?>>
            <?php _e("Exclude from search nosearch-tagged posts", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the taxonomy attachment document field
     * 
     * @return void
     */
    public function taxonomyAttachmentDocumentField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-taxonomy-attachment-document" name="<?php printf('%s[taxonomy_attachment_document]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->taxonomies->taxonomy_attachment_document, 1); ?>>
            <?php _e("Register attachment document taxonomy", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the taxonomy page category field
     * 
     * @return void
     */
    public function taxonomyPageCategoryField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-taxonomy-page-category" name="<?php printf('%s[taxonomy_page_category]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->taxonomies->taxonomy_page_category, 1); ?>>
            <?php _e("Register page category taxonomy", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Renders the taxonomy page tag field
     * 
     * @return void
     */
    public function taxonomyPageTagField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-taxonomy-page-tag" name="<?php printf('%s[taxonomy_page_tag]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->taxonomies->taxonomy_page_tag, 1); ?>>
            <?php _e("Register page tag taxonomy", 'rrze-settings'); ?>
        </label>
<?php
    }
}
