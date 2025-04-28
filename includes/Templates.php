<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

/**
 * Templates class
 * 
 * This class provides methods to manage and parse templates.
 * 
 * @package RRZE\Settings
 */
class Templates
{
    /**
     * Get the content of a template file
     * 
     * @param string $template The name of the template file
     * @param array $data The data to be passed to the template
     * @return string The parsed content of the template
     */
    public static function getContent(string $template = '', array $data = []): string
    {
        return self::parseContent($template, $data);
    }

    /**
     * Parse the content of a template file
     * 
     * @param string $template The name of the template file
     * @param array $data The data to be passed to the template
     * @return string The parsed content of the template
     */
    protected static function parseContent(string $template, array $data): string
    {
        $content = self::getTemplate($template);
        if (empty($content)) {
            return '';
        }
        if (empty($data)) {
            return $content;
        }
        return (new Parser())->parse($content, $data);
    }

    /**
     * Get the content of a template file
     * 
     * @param string $template The name of the template file
     * @return string The content of the template file
     */
    protected static function getTemplate(string $template): string
    {
        $content = '';
        $templateFile = sprintf(
            '%1$stemplates/%2$s',
            plugin()->getDirectory(),
            $template
        );
        if (is_readable($templateFile)) {
            ob_start();
            include($templateFile);
            $content = ob_get_contents();
            @ob_end_clean();
        }
        return $content;
    }
}
