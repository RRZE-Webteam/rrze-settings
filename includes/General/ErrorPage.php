<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

use RRZE\Settings\Templates;

/**
 * ErrorPage class
 *
 * @package RRZE\Settings\General
 */
class ErrorPage
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options object
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
        // Enables custom error page
        if ($this->siteOptions->general->custom_error_page) {
            // Filters the callback for killing WordPress execution for all non-Ajax, non-JSON, non-XML requests.
            // https://developer.wordpress.org/reference/hooks/wp_die_handler/
            add_filter('wp_die_handler', fn() => [$this, 'wpDieHandlerDefault']);
        }
    }

    /**
     * Default wp_die handler
     *
     * @param string $message Message to display
     * @param string $title Title of the error page
     * @param array $args Additional arguments
     * @return void
     */
    public function wpDieHandlerDefault($message, $title = '', $args = [])
    {
        list($message, $title, $r) = _wp_die_process_input($message, $title, $args);

        $data = [];

        $data['message'] = '';
        if (is_string($message)) {
            if (! empty($r['additional_errors'])) {
                $message = array_merge(
                    [$message],
                    wp_list_pluck($r['additional_errors'], 'message')
                );
                $message = "<ul>\n\t\t<li>" . implode("</li>\n\t\t<li>", $message) . "</li>\n\t</ul>";
            }
            $data['message'] = $message;
        }

        $data['title'] = $title;
        $data['page_title'] = function_exists('get_bloginfo') ? get_bloginfo('name') : '';

        $data['back_link'] = '';
        if (isset($r['back_link']) && $r['back_link']) {
            $backText = function_exists('__') ? __('&laquo; Back') : '&laquo; Back';
            $data['back_link'] = '<a href="javascript:history.back()">' . $backText . '</a>';
        }

        if (! did_action('admin_head')) {
            if (! headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
                status_header($r['response']);
                nocache_headers();
            }

            $dirAttr = '';
            $textDirection = $r['text_direction'];
            if (function_exists('language_attributes') && function_exists('is_rtl')) {
                $dirAttr = get_language_attributes();
            } else {
                $dirAttr = "dir='$textDirection'";
            }
            $data['dir_attr'] = $dirAttr;
            $data['is_rtl'] = $textDirection == 'rtl' ? 'is_rtl' : '';

            echo Templates::getContent('wp-die-handler/default.html', $data);
        } else {
            echo $data['message'] ? '<p>' . $data['message'] . '</p>' . PHP_EOL : '';
            echo '</body>' . PHP_EOL;
            echo '</html>';
        }

        if ($r['exit']) {
            die();
        }
    }
}
