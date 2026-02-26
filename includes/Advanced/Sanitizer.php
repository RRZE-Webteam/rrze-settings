<?php

namespace RRZE\Settings\Advanced;

defined('ABSPATH') || exit;

/**
 * Class Sanitizer
 *
 * Handles sanitization of specific setting fields in the Advanced Settings Page.
 *
 * @package RRZE\Settings\Advanced
 */
class Sanitizer
{
    /**
     * Sanitize a comma-separated string of CSS classes.
     *
     * @param string $classes_string The raw string from the input field.
     * @return string The sanitized string.
     * @since 2.1.1
     */
    public static function sanitize_css_classes(string $classes_string): string
    {
        if (empty($classes_string)) {
            return '';
        }

        $classes = explode(',', $classes_string);
        $sanitized_classes = [];
        foreach ($classes as $class) {
            $sanitized_classes[] = sanitize_html_class(trim($class));
        }

        return implode(', ', array_filter($sanitized_classes));
    }

    /**
     * Sanitize a comma-separated string of theme slugs.
     *
     * @param string $slugs_string The raw string from the input field.
     * @return string The sanitized string.
     * @since 2.1.1
     */
    public static function sanitize_theme_slugs(string $slugs_string): string
    {
        if (empty($slugs_string)) {
            return '';
        }

        $slugs = explode(',', $slugs_string);
        $sanitized_slugs = [];
        foreach ($slugs as $slug) {
            $sanitized_slugs[] = sanitize_text_field(trim($slug));
        }

        return implode(', ', array_filter($sanitized_slugs));
    }
}
