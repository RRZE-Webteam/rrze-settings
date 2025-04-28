<?php

namespace RRZE\Settings\Taxonomies;

defined('ABSPATH') || exit;

use Walker_CategoryDropdown;

/**
 * AttachmentDocumentDropdown class
 *
 * @package RRZE\Settings\Taxonomies
 */
class AttachmentDocumentDropdown extends Walker_CategoryDropdown
{
    /**
     * Start element
     */
    public function start_el(&$output, $category, $depth = 0, $args = [], $id = 0)
    {
        $pad = str_repeat('&nbsp;', $depth * 3);

        $catName = apply_filters('list_cats', $category->name, $category);

        $output .= ',{"term_id":"' . $category->term_id . '",';

        $output .= '"term_name":"' . $pad . esc_attr($catName);
        if ($args['show_count']) {
            $output .= '&nbsp;&nbsp;(' . $category->count . ')';
        }
        $output .= '"}';
    }
}
