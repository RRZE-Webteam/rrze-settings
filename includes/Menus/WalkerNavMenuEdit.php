<?php

namespace RRZE\Settings\Menus;

defined('ABSPATH') || exit;

require_once(ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php');

/**
 * Custom Walker for Nav Menu Edit
 * 
 * This class extends the default Walker_Nav_Menu_Edit class to modify the output of the menu items in the admin area.
 */
class WalkerNavMenuEdit extends \Walker_Nav_Menu_Edit
{
    /**
     * Start the element output
     *
     * This method is called at the start of each menu item in the admin area.
     * It modifies the output to include a toggle link for items with children.
     *
     * @param string $output The current output.
     * @param object $item The current menu item.
     * @param int $depth The depth of the menu item.
     * @param object $args The arguments passed to the walker.
     * @param int $id The ID of the menu item.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $tempOutput = '';
        parent::start_el($tempOutput, $item, $depth, $args, $id);
        if ($args->walker->has_children) {
            $tempOutput = preg_replace(
                // @todo Check this regex from time to time!
                '/<a class="item-edit"/',
                '<a href="#" class="item-type hide-if-no-js hidden rrze-menus-toggle"></a><a class="item-edit"',
                $tempOutput,
                1
            );
        }
        $output .= $tempOutput;
    }
}
