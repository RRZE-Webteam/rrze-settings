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
                        __('Error: %s', 'rrze-settings'),
                        $message->get_error_message()
                    ); ?>
                </p>
            </div>
        <?php else : ?>
            <div class="updated">
                <p><?php echo $message; ?></p>
            </div>
    <?php endif;
    endforeach; ?>
    <h2>
        <?php _e('Replace Media File', 'rrze-media'); ?>
    </h2>

    <div id="message" class="notice notice-warning is-dismissible">
        <p><?php printf(
                /* translators: %s: Media filename. */
                __('You are about to replace the media file "%s".', 'rrze-settings'),
                $data['current_filename']
            ); ?></p>
    </div>

    <form enctype="multipart/form-data" method="post" action="<?php echo $url; ?>">
        <input type="hidden" name="ID" value="<?php echo $data['attachment_id']; ?>" />

        <label class="rrze-media-type-file-input">
            <?php _e("Select a file", 'rrze-settings'); ?>
            <input type="file" name="userfile" class="rrze-media-upload" />
        </label>
        <span class="rrze-media-upload-value"><?php _e('Nothing selected.', 'rrze-media'); ?></span>

        <p class="description">
            <?php printf(
                /* translators: %1$s: Mime type, %2$s: Media filename. */
                __('The file must be of the same media type (%1$s) as the one being replaced. The name of the file (%2$s) will stay the same.', 'rrze-settings'),
                $data['current_filetype'],
                $data['current_filename']
            ); ?>
        </p>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e("Upload", 'rrze-settings'); ?>" /> <a href="#" onclick="history.back();" class="button-secondary"><?php _e("Cancel", 'rrze-settings'); ?></a>
        </p>
    </form>
</div>