<?php

namespace RRZE\Settings\Media\SVG;

defined('ABSPATH') || exit;

/**
 * Class AllowedTags
 *
 * This class handles the allowed tags for SVG files.
 *
 * @package RRZE\Settings\Media\SVG
 */
class AllowedTags extends \enshrined\svgSanitize\data\AllowedTags
{
    /**
     * Get allowed SVG tags
     *
     * @return array
     */
    public static function getTags()
    {
        return apply_filters('rrze_settings_svg_allowed_tags', parent::getTags());
    }
}
