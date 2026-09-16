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
            add_filter('wp_die_handler', [$this, 'getWpDieHandler']);
        }
    }

    /**
     * Return the custom wp_die callback.
     *
     * @return array
     */
    public function getWpDieHandler(): array
    {
        return [$this, 'wpDieHandlerDefault'];
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
        $data['admin_email'] = function_exists('get_option') ? sanitize_email(get_option('admin_email')) : '';
        $data['labels'] = [
            'site_header' => __('Site header', 'rrze-settings'),
            'site_footer' => __('Site footer', 'rrze-settings'),
            'error' => __('Error', 'rrze-settings'),
            'error_heading' => __('Error accessing the website', 'rrze-settings'),
            'webmaster_contact' => __('Contact the webmaster of this website:', 'rrze-settings'),
            'legal_notices' => __('Legal notices', 'rrze-settings'),
            'imprint' => __('Imprint', 'rrze-settings'),
            'privacy' => __('Privacy policy', 'rrze-settings'),
            'accessibility' => __('Accessibility statement', 'rrze-settings'),
        ];

        $this->logErrorPage($message, $title, $r, $data);

        $data['back_link'] = '';
        if (isset($r['back_link']) && $r['back_link']) {
            $backText = function_exists('__') ? __('&laquo; Back', 'rrze-settings') : '&laquo; Back';
            $data['back_link'] = '<a href="javascript:history.back()">' . esc_html($backText) . '</a>';
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

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template receives sanitized wp_die data and contains full document markup.
            echo Templates::getContent('wp-die-handler/default.html', $data);
        } else {
            echo $data['message'] ? '<p>' . wp_kses_post($data['message']) . '</p>' . PHP_EOL : '';
            echo '</body>' . PHP_EOL;
            echo '</html>';
        }

        if ($r['exit']) {
            die();
        }
    }

    /**
     * Log the context in which the custom error page was called.
     *
     * @param mixed  $message Error message.
     * @param string $title Error title.
     * @param array  $args Processed wp_die arguments.
     * @param array  $data Template data.
     * @return void
     */
    protected function logErrorPage($message, string $title, array $args, array $data): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
        $context = [
            'plugin' => 'rrze-settings',
            'handler' => 'wp_die_handler',
            'message' => wp_strip_all_tags(is_scalar($message) ? (string) $message : wp_json_encode($message)),
            'error_title' => sanitize_text_field($title),
            'response_code' => isset($args['response']) ? absint($args['response']) : 0,
            'site' => [
                'blog_id' => get_current_blog_id(),
                'name' => $data['page_title'],
                'url' => home_url('/'),
                'admin_email' => $data['admin_email'],
            ],
            'request' => [
                'method' => isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'])) : '',
                'path' => is_string($requestUri) ? $requestUri : '',
                'is_admin' => is_admin(),
                'is_ajax' => wp_doing_ajax(),
                'is_rest' => defined('REST_REQUEST') && REST_REQUEST,
            ],
            'user_id' => get_current_user_id(),
            'calling_plugin' => $this->getCallingPlugin(),
        ];

        do_action('rrze.log.error', 'RRZE-Settings: Fehlerseite aufgerufen', $context);
    }

    /**
     * Determine the first plugin file that called wp_die().
     *
     * @return string
     */
    protected function getCallingPlugin(): string
    {
        $pluginDirectory = wp_normalize_path(WP_PLUGIN_DIR) . '/';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $frame) {
            if (empty($frame['file'])) {
                continue;
            }

            $file = wp_normalize_path($frame['file']);
            if (strpos($file, $pluginDirectory) !== 0) {
                continue;
            }

            $relativePath = substr($file, strlen($pluginDirectory));
            $parts = explode('/', $relativePath);
            if (!empty($parts[0]) && $parts[0] !== 'rrze-settings') {
                return sanitize_key($parts[0]);
            }
        }

        return '';
    }
}
