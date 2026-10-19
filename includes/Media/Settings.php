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
     * @return void
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
     * Define the ordered sections shared by navigation, rendering and registration.
     *
     * @return array<string, array{title: string, callback: string}> Section metadata.
     */
    protected function getMediaSections(): array
    {
        return [
            $this->sectionName => [
                'title' => __('Uploads and File Types', 'rrze-settings'),
                'callback' => 'mainSectionDescription',
            ],
            $this->sectionName . '-images' => [
                'title' => __('Image Processing', 'rrze-settings'),
                'callback' => 'imageSectionDescription',
            ],
            $this->sectionName . '-library' => [
                'title' => __('Media Library Tools', 'rrze-settings'),
                'callback' => 'librarySectionDescription',
            ],
            $this->taxonomySectionName => [
                'title' => __('Organize Media', 'rrze-settings'),
                'callback' => 'taxonomySectionDescription',
            ],
        ];
    }

    /**
     * Render the shared media form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage()
    {
        global $title;

        $sections = $this->getMediaSections();
        echo '<div class="wrap rrze-settings-media-page"><h1>', esc_html($title), '</h1>';
        echo '<nav id="rrze-settings-media-index" aria-labelledby="rrze-settings-media-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-media-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-media" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-media-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-media-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
            echo '</div>';
            $this->{$section['callback']}();
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($this->menuPage, $sectionId);
            echo '</table></section>';
        }
        submit_button(__('Save Changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo '</form></div>';
    }

    /**
     * Group media controls by task while preserving stored option keys.
     *
     * @return void
     */
    public function networkAdminPage()
    {
        foreach ($this->getMediaSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'sanitize_filename',
            __('Upload Filenames', 'rrze-settings'),
            [$this, 'sanitizeFilenameField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'enable_svg_support',
            __('SVG Processing', 'rrze-settings'),
            [$this, 'svgSupportEnabledField'],
            $this->menuPage,
            $this->sectionName
        );

        add_settings_field(
            'mime_types',
            __('Additional File Types', 'rrze-settings'),
            [$this, 'mimeTypesField'],
            $this->menuPage,
            $this->sectionName,
            ['label_for' => 'rrze-settings-mime-types']
        );

        add_settings_field(
            'filter_nonimages_mimetypes',
            __('PDF Preview Images', 'rrze-settings'),
            [$this, 'filterNonimagesMimetypesField'],
            $this->menuPage,
            $this->sectionName . '-images'
        );

        add_settings_field(
            'enable_image_resize',
            __('Resize Large Images', 'rrze-settings'),
            [$this, 'enableImageResizeField'],
            $this->menuPage,
            $this->sectionName . '-images'
        );

        add_settings_field(
            'max_width_height',
            __('Maximum Image Dimension', 'rrze-settings'),
            [$this, 'maxWidthHeightField'],
            $this->menuPage,
            $this->sectionName . '-images',
            ['label_for' => 'image_max_width_height']
        );

        add_settings_field(
            'enable_sharpen_jpg_images',
            __('Sharpen JPEG Images', 'rrze-settings'),
            [$this, 'enableSharpenJpgImagesField'],
            $this->menuPage,
            $this->sectionName . '-images'
        );

        add_settings_field(
            'enable_file_replace',
            __('Replace Media Files', 'rrze-settings'),
            [$this, 'enableFileReplaceField'],
            $this->menuPage,
            $this->sectionName . '-library'
        );

        add_settings_field(
            'enable_filesize_column',
            __('File Size in the Media Library', 'rrze-settings'),
            [$this, 'enableFileSizeColumnField'],
            $this->menuPage,
            $this->sectionName . '-library'
        );

        add_settings_field(
            'taxonomy_attachment_document',
            __('Document Groups', 'rrze-settings'),
            [$this, 'taxonomyAttachmentDocumentField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_field(
            'taxonomy_attachment_category',
            __('Media Categories', 'rrze-settings'),
            [$this, 'taxonomyAttachmentCategoryField'],
            $this->menuPage,
            $this->taxonomySectionName
        );

        add_settings_field(
            'taxonomy_attachment_tag',
            __('Media Tags', 'rrze-settings'),
            [$this, 'taxonomyAttachmentTagField'],
            $this->menuPage,
            $this->taxonomySectionName
        );
    }

    /**
     * Explain network scope and the purpose of upload settings.
     *
     * @return void
     */
    public function mainSectionDescription()
    {
        echo '<p>', esc_html(__('These settings apply to all websites in the network after saving. Configure filenames, SVG processing and additional upload types here. Saving this page does not reprocess existing media.', 'rrze-settings')), '</p>';
    }

    /**
     * Distinguish processing future files from changing existing media.
     *
     * @return void
     */
    public function imageSectionDescription()
    {
        echo '<p>', esc_html(__('Control previews and image processing during uploads or when image sizes are generated again. The pixel limit below belongs to this plugin’s resizing feature; other WordPress and theme image settings still apply.', 'rrze-settings')), '</p>';
    }

    /**
     * Describe tools available to editors in each website’s media library.
     *
     * @return void
     */
    public function librarySectionDescription()
    {
        echo '<p>', esc_html(__('Add tools for replacing files and checking their size in each website’s media library.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain independent media classifications and what happens when disabled.
     *
     * @return void
     */
    public function taxonomySectionDescription()
    {
        echo '<p>', esc_html(__('These classification systems help organize and filter media. Each website maintains its own terms and assignments. Switching a system off hides its management and filters; stored terms, assignments and files are retained.', 'rrze-settings')), '</p>';
    }

    /**
     * Render upload filenames with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function sanitizeFilenameField()
    {
        $this->renderCheckbox('rrze-settings-sanitize-filename', sprintf('%s[sanitize_filename]', $this->optionName), $this->siteOptions->media->sanitize_filename, __('Simplify filenames when uploading', 'rrze-settings'));
        $this->renderDescription(__('Replaces supported accented characters and umlauts, removes remaining non-ASCII characters and lowercases the file extension. Existing filenames and URLs stay unchanged.', 'rrze-settings'));
        $this->renderSettingDetails(__('Upload Filenames', 'rrze-settings'), [
            __('The additional conversion replaces ä with ae and ß with ss, for example. WordPress may already normalize a filename before this additional step.', 'rrze-settings'),
            __('Disabled: WordPress continues its own filename cleanup. The setting does not rename files already in the media library or change their title, caption or alternative text.', 'rrze-settings'),
        ]);
    }

    /**
     * Render svg processing with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function svgSupportEnabledField()
    {
        $this->renderCheckbox('rrze-settings-enable-svg-support', sprintf('%s[enable_svg_support]', $this->optionName), $this->siteOptions->media->enable_svg_support, __('Sanitize SVG uploads and display them in the media library', 'rrze-settings'));
        $this->renderDescription(__('Adds SVG previews and cleans SVG files during regular uploads. SVG must also be permitted as an upload type; this checkbox alone does not enable that file type.', 'rrze-settings'));
        $this->renderSettingDetails(__('SVG Processing', 'rrze-settings'), [
            __('For an SVG type mapping, use svg=image/svg+xml //Vector graphics under Additional File Types. Keep this consistent with Upload file types under Network Admin > Settings > Network Settings.', 'rrze-settings'),
            __('The upload cleaner removes SVG elements and attributes outside its allowed list. If cleaning fails, the regular upload is rejected. Existing SVG files are not cleaned retroactively; the separate Replace File dialog does not use this upload cleaner.', 'rrze-settings'),
            __('Disabled: the additional SVG previews and upload cleaning are removed. Existing files remain stored. This does not revoke an SVG upload permission configured elsewhere.', 'rrze-settings'),
        ]);
    }

    /**
     * Render pdf preview images with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function filterNonimagesMimetypesField()
    {
        $this->renderCheckbox('rrze-settings-filter-nonimages-mimetypes', sprintf('%s[filter_nonimages_mimetypes]', $this->optionName), $this->siteOptions->media->filter_nonimages_mimetypes, __('Skip generating preview images for PDFs', 'rrze-settings'));
        $this->renderDescription(__('Suppresses the fallback image sizes WordPress generates for non-image files, currently PDF previews. PDF uploads and downloads remain available.', 'rrze-settings'));
        $this->renderSettingDetails(__('PDF Preview Images', 'rrze-settings'), [
            __('Example: a newly uploaded PDF is shown with a file icon instead of a preview of its first page. Image sizes for regular photos are unaffected.', 'rrze-settings'),
            __('Existing preview files are not deleted. Disabled: WordPress can generate PDF previews again if the server supports PDF processing. Merely switching this off does not generate missing previews for existing PDFs.', 'rrze-settings'),
        ]);
    }

    /**
     * Render resize large images with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function enableImageResizeField()
    {
        $this->renderCheckbox('rrze-settings-enable-image-resize', sprintf('%s[enable_image_resize]', $this->optionName), $this->siteOptions->media->enable_image_resize, __('Reduce oversized images during upload and replacement', 'rrze-settings'));
        $this->renderDescription(__('Scales JPEG, PNG and GIF files proportionally to the pixel limit below. If the result uses less disk space, it replaces the uploaded original; this feature keeps no separate copy of the larger original.', 'rrze-settings'));
        $this->renderSettingDetails(__('Resize Large Images', 'rrze-settings'), [
            __('Example: a 4000 × 3000 image is reduced to 2000 × 1500 at a limit of 2000 pixels, provided the result has a smaller file size. Smaller images are not enlarged and the image is not cropped.', 'rrze-settings'),
            __('A filename containing the lowercase text noresize, such as poster-noresize.jpg, bypasses this plugin’s resizing. WordPress or other plugins may still resize the file. WebP, AVIF and SVG are not processed by this feature.', 'rrze-settings'),
            __('Applies to new uploads and files replaced using this plugin, not to a batch of existing media. When replacing a file, the retained destination filename determines the noresize exception. For animated GIFs, check whether the server’s image processing preserves animation.', 'rrze-settings'),
        ]);
    }

    /**
     * Render sharpen jpeg images with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function enableSharpenJpgImagesField()
    {
        $this->renderCheckbox('rrze-settings-enable-sharpen-jpg-images', sprintf('%s[enable_sharpen_jpg_images]', $this->optionName), $this->siteOptions->media->enable_sharpen_jpg_images, __('Sharpen JPEG files generated by WordPress', 'rrze-settings'));
        $this->renderDescription(__('Sharpens JPEG files when WordPress saves processed images, for example preview sizes. Requires the Imagick PHP extension; without it, this setting has no effect.', 'rrze-settings'));
        $this->renderSettingDetails(__('Sharpen JPEG Images', 'rrze-settings'), [
            __('Processing also normalizes the image contrast and saves JPEGs at quality 92. It can change appearance and file size; it is not a lossless optimization. PNG and GIF files are not sharpened.', 'rrze-settings'),
            __('Can be used independently of Resize Large Images. Switching it on does not reprocess existing files; switching it off does not undo earlier sharpening.', 'rrze-settings'),
        ]);
    }

    /**
     * Render replace media files with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function enableFileReplaceField()
    {
        $this->renderCheckbox('rrze-settings-enable-file-replace', sprintf('%s[enable_file_replace]', $this->optionName), $this->siteOptions->media->enable_file_replace, __('Offer Replace File in the media library', 'rrze-settings'));
        $this->renderDescription(__('Allows an existing media file to be overwritten with a file of the same MIME type. The media entry and filename are retained. The old file is not kept as a version.', 'rrze-settings'));
        $this->renderSettingDetails(__('Replace Media Files', 'rrze-settings'), [
            __('Example: replace a PDF with its revised PDF version. Existing links to the retained file address use the new content; browser or website caches may still show the previous version temporarily.', 'rrze-settings'),
            __('The action is available in the media list and attachment edit screen to users who may upload files and edit the attachment. A JPEG cannot be replaced with a PDF. Image metadata and generated sizes are updated; enabled image resizing also applies.', 'rrze-settings'),
            __('Disabled: the additional replacement action is removed. Previously replaced files remain in their current state.', 'rrze-settings'),
        ]);
    }

    /**
     * Render file size in the media library with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function enableFileSizeColumnField()
    {
        $this->renderCheckbox('rrze-settings-enable-filesize-column', sprintf('%s[enable_filesize_column]', $this->optionName), $this->siteOptions->media->enable_filesize_column, __('Show the Size column in the media list', 'rrze-settings'));
        $this->renderDescription(__('Displays the size of the attached file in the media library’s list view. This is the file size in bytes, KB or MB, not the image dimensions in pixels.', 'rrze-settings'));
        $this->renderSettingDetails(__('File Size in the Media Library', 'rrze-settings'), [
            __('The value covers the attached file only, not the sum of its generated image sizes or all storage used by a website. Missing values are read from accessible local files when the column is displayed.', 'rrze-settings'),
            __('The column offers sorting by file size. Files without a saved size can be absent from sorted results until their size has been recorded. If the column is hidden, switch to list view and check Screen Options.', 'rrze-settings'),
        ]);
    }

    /**
     * Render document groups with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function taxonomyAttachmentDocumentField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-document', sprintf('%s[taxonomy_attachment_document]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_document, __('Enable document groups for media', 'rrze-settings'));
        $this->renderDescription(__('Adds the hierarchical Documents classification to media. Use it for groups such as Regulations > Examination Regulations; it is independent of media categories.', 'rrze-settings'));
        $this->renderSettingDetails(__('Document Groups', 'rrze-settings'), [
            __('The name Documents describes a classification, not a file-type restriction: images and other attachments can also be assigned. This neither converts files to PDF nor enables PDF uploads.', 'rrze-settings'),
            __('Create terms under Media > Documents on each website and assign them when editing media. Parent and child terms are supported. Filters appear when assigned media and corresponding terms are available.', 'rrze-settings'),
        ]);
    }

    /**
     * Render media categories with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function taxonomyAttachmentCategoryField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-category', sprintf('%s[taxonomy_attachment_category]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_category, __('Enable categories for media', 'rrze-settings'));
        $this->renderDescription(__('Adds hierarchical categories for attachments, for example Events > Conferences. These categories are separate from post and page categories.', 'rrze-settings'));
        $this->renderSettingDetails(__('Media Categories', 'rrze-settings'), [
            __('Manage the terms under Media > Categories on each website and assign them to media. Category filters help narrow down the library and media selection dialogs when terms are in use.', 'rrze-settings'),
            __('Categories organize media logically. They do not create folders, move files, change file URLs or restrict access to the files.', 'rrze-settings'),
        ]);
    }

    /**
     * Render media tags with scope, examples and existing-media guidance.
     *
     * @return void
     */
    public function taxonomyAttachmentTagField()
    {
        $this->renderCheckbox('rrze-settings-taxonomy-attachment-tag', sprintf('%s[taxonomy_attachment_tag]', $this->optionName), $this->siteOptions->taxonomies->taxonomy_attachment_tag, __('Enable tags for media', 'rrze-settings'));
        $this->renderDescription(__('Adds freely combinable keywords for attachments, for example Campus, Research or Team. Tags have no parent-child hierarchy and are separate from post and page tags.', 'rrze-settings'));
        $this->renderSettingDetails(__('Media Tags', 'rrze-settings'), [
            __('Manage terms under Media > Tags and assign multiple tags to a file when useful. Tags help find media across categories, without changing the file or automatically displaying tags on the website.', 'rrze-settings'),
        ]);
    }

    /**
     * Render the dependent pixel limit with an explicit label and server-aligned minimum.
     *
     * @return void
     */
    public function maxWidthHeightField()
    {
        $this->renderInput('number', 'image_max_width_height', sprintf('%s[max_width_height]', $this->optionName), $this->siteOptions->media->max_width_height, 'small-text', [
            'min' => 1024,
            'step' => 1,
            'aria-describedby' => 'image-max-width-height',
        ]);
        echo ' ', esc_html(__('pixels', 'rrze-settings'));
        printf('<p class="description" id="image-max-width-height">%s</p>', esc_html(__('Used only when Resize Large Images is enabled. Applies to both width and height. Minimum: 1024 pixels; default: 2000 pixels. Empty or smaller values are saved as the default.', 'rrze-settings')));
    }

    /**
     * Explain the exact MIME mapping format and its relationship to upload permissions.
     *
     * @return void
     */
    public function mimeTypesField()
    {
        $mimeTypes = $this->getMimeTypes($this->siteOptions->media->mime_types);
        $this->renderTextarea('rrze-settings-mime-types', sprintf('%s[mime_types]', $this->optionName), $mimeTypes);
        $this->renderDescription(__('Maps additional file extensions to MIME types and adds them to the allowed upload types. Existing files are not converted. Enter one mapping per line.', 'rrze-settings'));
        $this->renderSettingDetails(__('Additional File Types', 'rrze-settings'), [
            __('Format: extension=mime/type //Comment. Example: csv=text/csv //Tables. Use lowercase extensions without a leading dot; separate multiple extensions with |, for example svg|svgz=image/svg+xml //Vector graphics. Put a space before the optional // comment.', 'rrze-settings'),
            __('Keep these entries consistent with Upload file types under Network Admin > Settings > Network Settings. This plugin adds its mappings to the allowed types; the network field alone does not necessarily remove a type added here.', 'rrze-settings'),
            __('A mapping describes a file type; it does not add a preview generator or clean the file content. For SVG processing, use the separate SVG option above. Empty: this plugin adds no custom mappings; WordPress and other plugins may still allow those types.', 'rrze-settings'),
        ]);
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
