<?php

namespace RRZE\Settings\Media\Replace\Views;

defined('ABSPATH') || exit;

$url = wp_nonce_url(admin_url('tools.php?page=rrze-media-replace&noheader=true&action=media-replace-upload&id=' . $data['attachment_id']), 'rrze-media-replace');
?>
<div class="wrap">
    <?php
    foreach ($data['messages'] as $message) :
        if (is_wp_error($message)) : ?>
            <div class="error">
                <p>
                    <?php printf(
                        /* translators: %s: Error message. */
                        esc_html__('Error: %s', 'rrze-settings'),
                        esc_html($message->get_error_message())
                    ); ?>
                </p>
            </div>
        <?php else : ?>
            <div class="updated">
                <p><?php echo esc_html($message); ?></p>
            </div>
    <?php endif;
    endforeach; ?>
    <h2>
        <?php esc_html_e('Replace Media File', 'rrze-settings'); ?>
    </h2>

    <div id="message" class="notice notice-warning is-dismissible">
        <p><?php printf(
                /* translators: %s: Media filename. */
                esc_html__('You are about to replace the media file "%s".', 'rrze-settings'),
                esc_html($data['current_filename'])
            ); ?></p>
    </div>

    <form enctype="multipart/form-data" method="post" action="<?php echo esc_url($url); ?>">
        <input type="hidden" name="ID" value="<?php echo esc_attr($data['attachment_id']); ?>" />

        <label class="rrze-media-type-file-input">
            <?php esc_html_e('Select a file', 'rrze-settings'); ?>
            <input type="file" name="userfile" class="rrze-media-upload" />
        </label>
        <span class="rrze-media-upload-value"><?php esc_html_e('Nothing selected.', 'rrze-settings'); ?></span>

        <p class="description">
            <?php printf(
                /* translators: %1$s: Mime type, %2$s: Media filename. */
                esc_html__('The file must be of the same media type (%1$s) as the one being replaced. The name of the file (%2$s) will stay the same.', 'rrze-settings'),
                esc_html($data['current_filetype']),
                esc_html($data['current_filename'])
            ); ?>
        </p>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Upload', 'rrze-settings'); ?>" /> <a href="#" onclick="history.back();" class="button-secondary"><?php esc_html_e('Cancel', 'rrze-settings'); ?></a>
        </p>
    </form>
</div>
