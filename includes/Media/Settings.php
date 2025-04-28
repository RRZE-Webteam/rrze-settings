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
        $input['sanitize_filename'] = !empty($input['sanitize_filename']) ? 1 : 0;

        $input['filter_nonimages_mimetypes'] = !empty($input['filter_nonimages_mimetypes']) ? 1 : 0;

        $input['enhanced_media_search'] = !empty($input['enhanced_media_search']) ? 1 : 0;

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

        // @todo This needs to be reviewed.
        // add_settings_field(
        //     'enhanced_media_search',
        //     __('Enhanced Search', 'rrze-settings'),
        //     [$this, 'enhancedMediaSearchField'],
        //     $this->menuPage,
        //     $this->sectionName
        // );

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
     * Display the sanitize_filename field
     *
     * @return void
     */
    public function sanitizeFilenameField()
    {
?>
        <label>
            <input type="checkbox" id="rrze-settings-sanitize-filename" name="<?php printf('%s[sanitize_filename]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->sanitize_filename, 1); ?>>
            <?php _e("Sanitize the filenames to avoid links with UTF-8 characters", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the filter_nonimages_mimetypes field
     *
     * @return void
     */
    public function filterNonimagesMimetypesField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-filter-nonimages-mimetypes" name="<?php printf('%s[filter_nonimages_mimetypes]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->filter_nonimages_mimetypes, 1); ?>>
            <?php _e("Filters the image sizes generated for non-image mime types", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the enhanced_media_search field
     *
     * @return void
     */
    public function enhancedMediaSearchField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-enhanced-media-search" name="<?php printf('%s[enhanced_media_search]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enhanced_media_search, 1); ?>>
            <?php _e("Enables enhanced media search", 'rrze-settings'); ?>
        </label>
    <?php
    }

    /**
     * Display the enable_image_resize field
     *
     * @return void
     */
    public function enableImageResizeField()
    {
    ?>
        <label>
            <input type="checkbox" id="rrze-settings-enable-image-resize" name="<?php printf('%s[enable_image_resize]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enable_image_resize, 1); ?>>
            <?php printf(
                /* translators: %s: Number of pixels. */
                __('Maximum width and height: %s pixels', 'rrze-settings'),
                '</label>
                <label><input name="' . sprintf('%s[max_width_height]', $this->optionName) . '" type="number"  min="1024" style="width: 75px" id="image_max_width_height" aria-describedby="image-max-width-height" value="' . esc_attr($this->siteOptions->media->max_width_height) . '"></label>'
            ); ?>
            <p class="screen-reader-text" id="image-max-width-height">
                <?php _e('Size in pixels', 'rrze-settings'); ?>
            </p>
        <?php
    }

    /**
     * Display the enable_sharpen_jpg_images field
     *
     * @return void
     */
    public function enableSharpenJpgImagesField()
    {
        ?>
            <label>
                <input type="checkbox" id="rrze-settings-enable-sharpen-jpg-images" name="<?php printf('%s[enable_sharpen_jpg_images]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enable_sharpen_jpg_images, 1); ?>>
                <?php _e("Enables sharpening of JPG images", 'rrze-settings'); ?>
            </label>
        <?php
    }

    /**
     * Display the enable_svg_support field
     *
     * @return void
     */
    public function svgSupportEnabledField()
    {
        ?>
            <label>
                <input type="checkbox" id="rrze-settings-enable-svg-support" name="<?php printf('%s[enable_svg_support]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enable_svg_support, 1); ?>>
                <?php _e("Enables support for SVG files", 'rrze-settings'); ?>
            </label>
        <?php
    }

    /**
     * Display the enable_filesize_column field
     *
     * @return void
     */
    public function enableFileSizeColumnField()
    {
        ?>
            <label>
                <input type="checkbox" id="rrze-settings-enable-filesize-column" name="<?php printf('%s[enable_filesize_column]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enable_filesize_column, 1); ?>>
                <?php _e("Enables the file size column", 'rrze-settings'); ?>
            </label>
        <?php
    }

    /**
     * Display the mime_types field
     *
     * @return void
     */
    public function mimeTypesField()
    {
        $mimeTypes = $this->getMimeTypes($this->siteOptions->media->mime_types); ?>
            <textarea id="rrze-settings-mime-types" cols="50" rows="5" name="<?php printf('%s[mime_types]', $this->optionName); ?>"><?php echo $mimeTypes; ?></textarea>
            <p class="description"><?php _e("Enter custom Mime Types. Make sure to add the respective extensions in the Upload File Types settings.", 'rrze-settings'); ?></p>
            <p class="description"><?php _e("To leave a comment, type two forward slashes (//) followed by the text of your comment.", 'rrze-settings'); ?></p>
            <p class="description"><?php _e("One custom Mime Type per line.", 'rrze-settings'); ?></p>
        <?php
    }

    /**
     * Display the enable_file_replace field
     *
     * @return void
     */
    public function enableFileReplaceField()
    {
        ?>
            <label>
                <input type="checkbox" id="rrze-settings-enable-file-replace" name="<?php printf('%s[enable_file_replace]', $this->optionName); ?>" value="1" <?php checked($this->siteOptions->media->enable_file_replace, 1); ?>>
                <?php _e('Allows to replace files of the same mime type', 'rrze-settings'); ?>
            </label>
    <?php
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
