<?php

namespace RRZE\Settings\Media\Replace;

defined('ABSPATH') || exit;

use RRZE\Settings\Media\Resize;
use WP_Error;

/**
 * Class Replace
 *
 * This class handles the replacement of media files in WordPress.
 *
 * @package RRZE\Settings\Media\Replace
 */
class Replace
{
    /**
     * Site options
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Is trash
     *
     * @var bool
     */
    protected $isTrash;

    /**
     * Messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Constructor
     *
     * @param object $siteOptions Site options
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        $this->isTrash = isset($_REQUEST['attachment-filter']) && 'trash' === $_REQUEST['attachment-filter'];

        add_action('admin_menu', [$this, 'mediaReplacePage']);
        add_filter('media_row_actions', [$this, 'mediaReplaceRowActions'], 10, 2);
        add_action('attachment_submitbox_misc_actions', [$this, 'mediaReplaceEdit'], 99);
        add_filter('attachment_fields_to_edit', [$this, 'mediaReplaceModalEdit'], 99, 2);
    }

    /**
     * Add the media replace page to the admin menu
     * 
     * @return void
     */
    public function mediaReplacePage()
    {
        add_management_page(
            __('Replace Media', 'rrze-settings'),
            __('Replace Media', 'rrze-settings'),
            'upload_files',
            'rrze-media-replace',
            [$this, 'getMediaReplaceIndex']
        );
    }

    /**
     * Get the URL for the media replace page
     *
     * @param int $postId Post ID
     * @return string URL for the media replace page
     */
    protected function mediaReplaceUrl($postId)
    {
        $urlArgs = [
            'page' => 'rrze-media-replace',
            'action' => 'media-replace',
            'goback' => 1,
            'id' => $postId,
            '_wpnonce' => wp_create_nonce('rrze-media-replace')
        ];

        $urlArgs = array_map('rawurlencode', $urlArgs);

        return add_query_arg($urlArgs, admin_url('tools.php'));
    }

    /**
     * Add the media replace action to the media row actions
     *
     * @param array $actions Array of actions
     * @param object $post Post object
     * @return array Modified actions
     */
    public function mediaReplaceRowActions($actions, $post)
    {
        if ($this->isTrash || !current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(admin_url('tools.php?page=rrze-media-replace&action=media-replace&goback=1&id=' . $post->ID), 'rrze-media-replace');
        $actions['rrze_media_replace'] = '<a href="' . esc_url($url) . '" title="' . esc_attr(__('Replace this file.', 'rrze-settings')) . '">' . _x('Replace File', 'action of rrze-media-replace', 'rrze-settings') . '</a>';

        return $actions;
    }

    /**
     * Add the media replace button to the attachment edit screen
     *
     * @return void
     */
    public function mediaReplaceEdit()
    {
        global $post;

        if ($this->isTrash || !current_user_can('edit_post', $post->ID)) {
            return;
        }

        echo '<div class="misc-pub-section misc-pub-rrze-media">';
        echo '<a href="' . esc_url($this->mediaReplaceUrl($post->ID)) . '" class="button-secondary button-large" title="' . esc_attr(__('Replace this file.', 'rrze-settings')) . '">' . __('Replace File', 'rrze-settings') . '</a>';
        echo '</div>';
    }

    /**
     * Add the media replace button to the attachment edit modal
     *
     * @param array $formFields Form fields
     * @param object $post Post object
     * @return array Modified form fields
     */
    public function mediaReplaceModalEdit($formFields, $post)
    {
        $formFields['media_replace'] = [
            'label' => '',
            'input' => 'html',
            'html' => '<a href="' . esc_url($this->mediaReplaceUrl($post->ID)) . '" class="button-secondary button-large" title="' . esc_attr(__('Replace this file.', 'rrze-settings')) . '">' . __('Replace File', 'rrze-settings') . '</a>',
            'show_in_modal' => true,
            'show_in_edit' => false,
        ];

        return $formFields;
    }

    /**
     * Handle the file upload
     *
     * @param int $attachmentId Attachment ID
     * @return void|WP_Error
     */
    public function uploadFile($attachmentId)
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'posts';
        $currentAttachment = $wpdb->get_row($wpdb->prepare("SELECT guid, post_mime_type FROM $tableName WHERE ID = %d", $attachmentId));

        if (empty($currentAttachment)) {
            return new WP_Error('current-file-not-exist', __('Current file does not exist.', 'rrze-settings'));
        }

        $currentGuid = $currentAttachment->guid;

        $currentFiletype = $currentAttachment->post_mime_type;

        $currentFilename = substr($currentAttachment->guid, (strrpos($currentAttachment->guid, '/') + 1));

        $currentFile = get_attached_file($attachmentId);

        $currentPath = substr($currentFile, 0, (strrpos($currentFile, '/')));

        $currentFile = str_replace('//', '/', $currentFile);

        $currentFiletype = $currentAttachment->post_mime_type;

        $tmpFilename = !empty($_FILES['userfile']['tmp_name']) ? $_FILES['userfile']['tmp_name'] : '';
        if (!$tmpFilename) {
            return new WP_Error('file-not-selected', __('Please select a file to upload.', 'rrze-settings'));
        }

        if (!is_uploaded_file($tmpFilename)) {
            return new WP_Error('file-not-uploaded', __('It was not possible to upload the file.', 'rrze-settings'));
        }

        $newFilename = $_FILES['userfile']['name'];
        $newFilesize = $_FILES['userfile']['size'];

        $fileData = wp_check_filetype_and_ext($tmpFilename, $newFilename);

        if ($fileData['ext'] == '') {
            return new WP_Error('filetype-incorrect', __('This media type is not permitted for security reasons.', 'rrze-settings'));
        }

        $newFiletype = $fileData['type'];

        if ($currentFiletype != $newFiletype) {
            return new WP_Error('filetype-doesnot-match', __('The media type does not match existing file.', 'rrze-settings'));
        }

        $originalFileperms = @fileperms($currentFile) & 0777;

        $return = $this->deleteFiles($attachmentId, $currentFile);
        if (is_wp_error($return)) {
            return new WP_Error('delete-files', $return->get_error_message());
        }

        move_uploaded_file($tmpFilename, $currentFile);

        @chmod($currentFile, $originalFileperms);

        $params = [
            'file' => $currentFile,
            'type' => $newFiletype
        ];

        if ($this->siteOptions->media->enable_image_resize) {
            $resize = new Resize($this->siteOptions);
            $resize->handleReplace($params);
        }

        wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $currentFile));

        update_attached_file($attachmentId, $currentFile);

        $file_size = filesize($currentFile);
        update_post_meta($attachmentId, 'filesize', $file_size);
    }

    /**
     * Delete the files associated with the attachment
     *
     * @param int $attachmentId Attachment ID
     * @param string $currentFile Current file path
     * @return void|WP_Error
     */
    protected function deleteFiles($attachmentId, $currentFile)
    {
        $currentPath = substr($currentFile, 0, (strrpos($currentFile, "/")));

        if (file_exists($currentFile)) {
            clearstatcache();
            if (is_writable($currentFile)) {
                unlink($currentFile);
            } else {
                return new WP_Error(
                    'file-cannot-deleted',
                    sprintf(
                        /* translators: %s: Media filename. */
                        __('The file %s can not be deleted by the web server, most likely because the permissions on the file are wrong.', 'rrze-settings'),
                        $currentFile
                    )
                );
            }
        }

        $suffix = substr($currentFile, (strlen($currentFile) - 4));
        $prefix = substr($currentFile, 0, (strlen($currentFile) - 4));

        if (!in_array($suffix, ['.png', '.gif', '.jpg'])) {
            return;
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (empty($metadata)) {
            return;
        }

        foreach ($metadata['sizes'] as $data) {
            if (!isset($data['file']) || strlen($data['file']) == 0) {
                continue;
            }

            $filename = $currentPath . '/' . $data['file'];
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * Handle the media replace action
     *
     * @return void
     */
    public function getMediaReplaceIndex()
    {
        $action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $attachmentId = !empty($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;

        if (!get_attached_file($attachmentId)) {
            wp_redirect(admin_url('upload.php'));
            exit;
        }

        if ($this->isTrash || !current_user_can('edit_post', $attachmentId)) {
            wp_die(__('You do not have permission to upload files.', 'rrze-settings'));
        }

        wp_enqueue_style('rrze-media-replace');
        wp_enqueue_script('rrze-media-replace');
        wp_localize_script(
            'rrze-media-replace',
            'rrzeMediaReplace',
            [
                'nothing_selected' => __('Nothing selected.', 'rrze-settings')
            ]
        );

        switch ($action) {
            case 'media-replace':
                check_admin_referer('rrze-media-replace');
                $this->mediaReplace($attachmentId);
                return;
                break;

            case 'media-replace-upload':
                check_admin_referer('rrze-media-replace');
                $return = $this->uploadFile($attachmentId);
                if (is_wp_error($return)) {
                    wp_die($return->get_error_message());
                }
                $redirectUrl = "post.php?post={$attachmentId}&action=edit&message=1";
                break;

            default:
                $redirectUrl = 'upload.php';
        }

        wp_redirect(admin_url($redirectUrl));
        exit;
    }

    /**
     * Display the media replace page
     *
     * @param int $attachmentId Attachment ID
     * @return void
     */
    protected function mediaReplace($attachmentId)
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'posts';
        $currentAttachment = $wpdb->get_row($wpdb->prepare("SELECT guid, post_mime_type FROM $tableName WHERE ID = %d", $attachmentId));

        $currentGuid = $currentAttachment->guid;
        $currentFiletype = $currentAttachment->post_mime_type;

        $currentFilename = substr($currentAttachment->guid, (strrpos($currentAttachment->guid, "/") + 1));

        $data = [
            'attachment_id' => $attachmentId,
            'current_filename' => $currentFilename,
            'current_filetype' => $currentFiletype
        ];

        $this->view($data);
    }

    /**
     * Renders the view
     *
     * @param array $data Data to pass to the view
     * @return void
     */
    protected function view($data = [])
    {
        # @todo $messages property needs to be reviewed
        $data['messages'] = $this->messages;

        return include 'Views/index.php';
    }
}
