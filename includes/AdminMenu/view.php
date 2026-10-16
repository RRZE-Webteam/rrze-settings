<?php
/**
 * Network navigation form. All menu labels are text, never links or HTML.
 *
 * @var array $preferences Current network preferences.
 * @var array $defaults Bundled catalogue entries.
 * @var array $catalog All known catalogue entries.
 * @var string[] $ids Effective ordering identifiers.
 * @var array $pending Discovered entries awaiting acceptance.
 */

namespace RRZE\Settings\AdminMenu;

defined('ABSPATH') || exit;
?>
<div class="wrap rrze-admin-navigation">
    <h1><?php esc_html_e('Admin Navigation', 'rrze-settings'); ?></h1>
    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Navigation settings saved.', 'rrze-settings'); ?></p></div>
    <?php endif; ?>
    <p><?php esc_html_e('Set the order of plugin pages under Settings in the dashboards of all websites in this network. The bundled catalogue is used until you save your own order.', 'rrze-settings'); ?></p>
    <p><?php esc_html_e('WordPress settings remain at the top. Only existing menu items are reordered; plugin activation and user permissions still determine which items are visible. Other admin menus are not affected.', 'rrze-settings'); ?></p>

    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=' . Settings::PAGE)); ?>" id="rrze-admin-navigation-form">
        <?php wp_nonce_field(Settings::PAGE); ?>
        <p><label><input type="checkbox" name="enabled" value="1" <?php checked($preferences['enabled']); ?>> <?php esc_html_e('Apply the network order to website Settings menus', 'rrze-settings'); ?></label></p>
        <p class="description"><?php esc_html_e('Enabled by default. When disabled, WordPress and plugins determine the order. Your saved priorities are retained.', 'rrze-settings'); ?></p>

        <h2><?php esc_html_e('Plugin menu order', 'rrze-settings'); ?></h2>
        <p id="rrze-navigation-instructions"><?php esc_html_e('Move entries with the arrow buttons or drag handles, or enter a position. Changes take effect after saving. Without JavaScript, positions are sorted numerically when saved; equal positions retain their previous order.', 'rrze-settings'); ?></p>
        <p class="description"><?php esc_html_e('This list is a network catalogue, not a list of active plugins on a particular website. Unavailable entries are skipped. Newly bundled entries are appended to your saved order.', 'rrze-settings'); ?></p>
        <div class="rrze-navigation-toolbar">
            <button type="submit" name="intent" value="save" class="button button-primary"><?php esc_html_e('Save Changes', 'rrze-settings'); ?></button>
            <button type="submit" name="intent" value="reset" class="button"><?php esc_html_e('Restore default order', 'rrze-settings'); ?></button>
            <span id="rrze-navigation-dirty" role="status"></span>
        </div>
        <p class="description"><?php esc_html_e('Restoring the default order saves immediately. Accepted additions stay at the end; the activation setting stays unchanged.', 'rrze-settings'); ?></p>
        <div class="rrze-navigation-scroll" role="region" aria-label="<?php esc_attr_e('Sortable plugin menu entries', 'rrze-settings'); ?>" tabindex="0">
            <ol id="rrze-navigation-order" aria-describedby="rrze-navigation-instructions">
                <?php foreach ($ids as $index => $id) : $entry = $catalog[$id]; ?>
                    <li class="rrze-navigation-row" data-label="<?php echo esc_attr($entry['label']); ?>">
                        <span class="rrze-navigation-handle hide-if-no-js" aria-hidden="true">⠿</span>
                        <label class="screen-reader-text" for="rrze-position-<?php echo esc_attr($id); ?>"><?php
                            /* translators: %s: Menu label. */
                            printf(esc_html__('Position of %s', 'rrze-settings'), esc_html($entry['label']));
                        ?></label>
                        <input class="rrze-navigation-position" id="rrze-position-<?php echo esc_attr($id); ?>" type="number" min="1" max="1000" name="positions[<?php echo esc_attr($id); ?>]" value="<?php echo esc_attr($index + 1); ?>">
                        <div class="rrze-navigation-label"><strong><?php echo esc_html($entry['label']); ?></strong><small><?php echo esc_html(implode(' / ', $entry['slugs'])); ?></small></div>
                        <div class="rrze-navigation-buttons hide-if-no-js">
                            <button type="button" class="button rrze-navigation-up" aria-label="<?php
                                /* translators: %s: Menu label. */
                                echo esc_attr(sprintf(__('Move %s up', 'rrze-settings'), $entry['label']));
                            ?>">↑</button>
                            <button type="button" class="button rrze-navigation-down" aria-label="<?php
                                /* translators: %s: Menu label. */
                                echo esc_attr(sprintf(__('Move %s down', 'rrze-settings'), $entry['label']));
                            ?>">↓</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <p class="screen-reader-text" id="rrze-navigation-announcement" aria-live="polite" aria-atomic="true"></p>

        <h2><?php esc_html_e('Newly detected menu entries', 'rrze-settings'); ?></h2>
        <p><?php esc_html_e('Additional entries are detected when a network administrator visits a website dashboard. They remain after the configured entries until you accept them here. Visit websites with additional plugins to extend this list.', 'rrze-settings'); ?></p>
        <?php if ($pending) : ?>
            <p><?php esc_html_e('Select entries and save to append them to the sortable list. You can then change their positions.', 'rrze-settings'); ?></p>
            <ul class="rrze-navigation-pending">
                <?php foreach ($pending as $id => $entry) : ?>
                    <li><label><input type="checkbox" name="include[]" value="<?php echo esc_attr($id); ?>"> <?php echo esc_html($entry['label']); ?> <code><?php echo esc_html(implode(' / ', $entry['slugs'])); ?></code></label></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e('No additional menu entries have been detected.', 'rrze-settings'); ?></p>
        <?php endif; ?>
        <p class="submit"><button type="submit" name="intent" value="save" class="button button-primary"><?php esc_html_e('Save Changes', 'rrze-settings'); ?></button></p>
    </form>
</div>
