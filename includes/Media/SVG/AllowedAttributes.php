<?php

namespace RRZE\Settings\Media\SVG;

defined('ABSPATH') || exit;

/**
 * Class AllowedAttributes
 * 
 * This class handles the allowed attributes for SVG files.
 * 
 * @package RRZE\Settings\Media\SVG
 */
class AllowedAttributes extends \enshrined\svgSanitize\data\AllowedAttributes
{
    /**
     * Get allowed SVG attributes
     *
     * @return array
     */
    public static function getAttributes()
    {
        return apply_filters('rrze_settings_svg_allowed_attributes', parent::getAttributes());
    }
}
