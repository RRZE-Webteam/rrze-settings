<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the media section of the plugin.
 *
 * @package RRZE\Settings\Media
 */
class Settings extends MainSettings
{
    /**
     * The menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-media';

    /**
     * The section name
     *
     * @var string
     */
    protected $sectionName = 'rrze-settings-media-section';

    /**
     * The taxonomy section name
     *
     * @var string
     */
    protected $taxonomySectionName = 'rrze-settings-media-taxonomies-section';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @var string
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Media', 'rrze-settings'),
            __('Media', 'rrze-settings'),
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
        $this->siteOptions->taxonomies->taxonomy_attachment_document = !empty($input['taxonomy_attachment_document']) ? 1 : 0;
        $this->siteOptions->taxonomies->taxonomy_attachment_category = !empty($input['taxonomy_attachment_category']) ? 1 : 0;
        $this->siteOptions->taxonomies->taxonomy_attachment_tag = !empty($input['taxonomy_attachment_tag']) ? 1 : 0;

        unset($input['taxonomy_attachment_document'], $input['taxonomy_attachment_category'], $input['taxonomy_attachment_tag']);

        $input['sanitize_filename'] = !empty($input['sanitize_filename']) ? 1 : 0;

        $input['filter_nonimages_mimetypes'] = !empty($input['filter_nonimages_mimetypes']) ? 1 : 0;

        $input['enable_image_resize'] = !empty($input['enable_image_resize']) ? 1 : 0;

        $defaultMaxWidthHeight = $this->defaultOptions->media->max_width_height;
        $input['max_width_height'] = !empty($input['max_width_height']) && absint($input['max_width_height']) >= 1024 ? absint($input['max_width_height']) : $defaultMaxWidthHeight;

        $input['enable_sharpen_jpg_images'] = !empty($input['enable_sharpen_jpg_images']) ? 1 : 0;

        $input['enable_svg_support'] = !empty($input['enable_svg_support']) ? 1 : 0;

        $input['enable_filesize_column'] = !empty($input['enable_filesize_column']) ? 1 : 0;

        $input['mime_types'] = !empty($input['mime_types']) ? $this->parseMimeTypes($input['mime_types']) : '';

        $input['enable_file_replace'] = !empty($input['enable_file_replace']) ? 1 : 0;

        return $this->parseOptionsValidate($input, 'media');
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
            __('Media', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'sanitize_filename',
            __(
                "Sanitize filename",
                'rrze-settings'
            ),
            [$this, 'sanitizeFilenameField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'filter_nonimages_mimetypes',
            __(
                "Filter Non-Images",
                'rrze-settings'
            ),
            [$this, 'filterNonimagesMimetypesField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_image_resize',
            __('Automatic Image Resizing', 'rrze-settings'),
            [$this, 'enableImageResizeField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_sharpen_jpg_images',
            __('Sharpen JPG Images', 'rrze-settings'),
            [$this, 'enableSharpenJpgImagesField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_svg_support',
            __('SVG Support', 'rrze-settings'),
            [$this, 'svgSupportEnabledField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_file_replace',
            __('File Replace', 'rrze-settings'),
            [$this, 'enableFileReplaceField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_filesize_column',
            __('File Size Column', 'rrze-settings'),
            [$this, 'enableFileSizeColumnField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'mime_types',
            __('Custom Mime Types', 'rrze-settings'),
            [$this, 'mimeTypesField'],
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
            'taxonomy_attachment_document',
            __('Register Attachment Document', 'rrze-settings'),
            [$this, 'taxonomyAttachmentDocumentField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_field(
            'taxonomy_attachment_category',
            __('Register Attachment Category', 'rrze-settings'),
            [$this, 'taxonomyAttachmentCategoryField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_field(
            'taxonomy_attachment_tag',
            __('Register Attachment Tag', 'rrze-settings'),
            [$this, 'taxonomyAttachmentTagField'],
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
        esc_html_e('Network administrators can centrally manage media settings across the multisite network. Options include sanitizing filenames, filtering non‑image MIME types, enabling automatic image resizing and sharpening, supporting SVG uploads, replacing files, displaying file sizes, and defining custom MIME types—ensuring consistent, secure media handling on every website.', 'rrze-settings');
    }

    /**
     * Display the taxonomy section description
     *
     * @return void
     */
    public function taxonomySectionDescription()
    {
        esc_html_e('Configure taxonomies for media attachments across the multisite network.', 'rrze-settings');
    }

    /**
     * Renders the taxonomy attachment document field
     * 
     * @return void
     */
    public function taxonomyAttachmentDocumentField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-document', sprintf('%s[taxonomy_attachment_document]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_document, __('Register attachment document taxonomy', 'rrze-settings'));
    }

    /**
     * Renders the taxonomy attachment category field
     * 
     * @return void
     */
    public function taxonomyAttachmentCategoryField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-category', sprintf('%s[taxonomy_attachment_category]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_category, __('Register attachment category taxonomy', 'rrze-settings'));
    }

    /**
     * Renders the taxonomy attachment tag field
     * 
     * @return void
     */
    public function taxonomyAttachmentTagField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-tag', sprintf('%s[taxonomy_attachment_tag]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_tag, __('Register attachment tag taxonomy', 'rrze-settings'));
    }

    /**
     * Display the sanitize_filename field
     *
     * @return void
     */
    public function sanitizeFilenameField()
    {
        $this->renderCheckbox('rrze-settings-sanitize-filename', sprintf('%s[sanitize_filename]', $this->optionName), $this->siteOptions->media->sanitize_filename, __('Sanitize the filenames to avoid links with UTF-8 characters', 'rrze-settings'));
    }

    /**
     * Display the filter_nonimages_mimetypes field
     *
     * @return void
     */
    public function filterNonimagesMimetypesField()
    {
        $this->renderCheckbox('rrze-settings-filter-nonimages-mimetypes', sprintf('%s[filter_nonimages_mimetypes]', $this->optionName), $this->siteOptions->media->filter_nonimages_mimetypes, __('Filters the image sizes generated for non-image mime types', 'rrze-settings'));
    }

    /**
     * Display the enable_image_resize field
     *
     * @return void
     */
    public function enableImageResizeField()
    {
        $this->renderCheckbox('rrze-settings-enable-image-resize', sprintf('%s[enable_image_resize]', $this->optionName), $this->siteOptions->media->enable_image_resize, __('Enable automatic image resizing', 'rrze-settings'));
        printf(
            '<label for="image_max_width_height">%1$s <input name="%2$s" type="number" min="1024" class="small-text" id="image_max_width_height" aria-describedby="image-max-width-height" value="%3$s"></label><p class="screen-reader-text" id="image-max-width-height">%4$s</p>',
            esc_html__('Maximum width and height in pixels:', 'rrze-settings'),
            esc_attr(sprintf('%s[max_width_height]', $this->optionName)),
            esc_attr($this->siteOptions->media->max_width_height),
            esc_html__('Size in pixels', 'rrze-settings')
        );
    }

    /**
     * Display the enable_sharpen_jpg_images field
     *
     * @return void
     */
    public function enableSharpenJpgImagesField()
    {
        $this->renderCheckbox('rrze-settings-enable-sharpen-jpg-images', sprintf('%s[enable_sharpen_jpg_images]', $this->optionName), $this->siteOptions->media->enable_sharpen_jpg_images, __('Enables sharpening of JPG images', 'rrze-settings'));
    }

    /**
     * Display the enable_svg_support field
     *
     * @return void
     */
    public function svgSupportEnabledField()
    {
        $this->renderCheckbox('rrze-settings-enable-svg-support', sprintf('%s[enable_svg_support]', $this->optionName), $this->siteOptions->media->enable_svg_support, __('Enables support for SVG files', 'rrze-settings'));
    }

    /**
     * Display the enable_filesize_column field
     *
     * @return void
     */
    public function enableFileSizeColumnField()
    {
        $this->renderCheckbox('rrze-settings-enable-filesize-column', sprintf('%s[enable_filesize_column]', $this->optionName), $this->siteOptions->media->enable_filesize_column, __('Enables the file size column', 'rrze-settings'));
    }

    /**
     * Display the mime_types field
     *
     * @return void
     */
    public function mimeTypesField()
    {
        $mimeTypes = $this->getMimeTypes($this->siteOptions->media->mime_types);
        $this->renderTextarea('rrze-settings-mime-types', sprintf('%s[mime_types]', $this->optionName), $mimeTypes);
        $this->renderDescription(__('Enter custom Mime Types. Make sure to add the respective extensions in the Upload File Types settings.', 'rrze-settings'));
        $this->renderDescription(__('To leave a comment, type two forward slashes (//) followed by the text of your comment.', 'rrze-settings'));
        $this->renderDescription(__('One custom Mime Type per line.', 'rrze-settings'));
    }

    /**
     * Display the enable_file_replace field
     *
     * @return void
     */
    public function enableFileReplaceField()
    {
        $this->renderCheckbox('rrze-settings-enable-file-replace', sprintf('%s[enable_file_replace]', $this->optionName), $this->siteOptions->media->enable_file_replace, __('Allows to replace files of the same mime type', 'rrze-settings'));
    }

    /**
     * Parse the mime types
     *
     * @param string $input The input string
     * @return array|string The parsed mime types
     */
    protected function parseMimeTypes($input)
    {
        $input = explode(PHP_EOL, $input);
        $customMimeTypes = [];

        foreach ($input as $line) {
            $lineAry = explode('=', $line);
            $k = !empty($lineAry[0]) ? trim($lineAry[0]) : '';
            $l = !empty($lineAry[1]) ? trim($lineAry[1]) : '';
            if (!$k || !$l) {
                continue;
            }
            $v = explode(' ', $l);
            $v = !empty($v[0]) ? trim($v[0]) : '';
            if (!$v) {
                continue;
            }
            $c = explode('//', $l);
            $c = !empty($c[1]) ? trim($c[1]) : '?';
            $customMimeTypes[$k] = $k . '=' . $v . ' //' . $c;
        }

        return !empty($customMimeTypes) ? $customMimeTypes : '';
    }

    /**
     * Get the mime types
     *
     * @param array $mimeTypes The mime types
     * @return string The mime types as a string
     */
    protected function getMimeTypes($mimeTypes)
    {
        return is_array($mimeTypes) ? implode(PHP_EOL, $mimeTypes) : '';
    }
}
