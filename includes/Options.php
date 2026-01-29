<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

/**
 * Options class
 * 
 * @package RRZE\Settings
 */
class Options
{
    /**
     * @var string
     */
    const OPTION_NAME = 'rrze_settings';

    /**
     * Default options
     * 
     * @return array
     */
    protected static function defaultOptions(): array
    {
        $options = [
            'general' => [
                'textdomain_fallback' => 0,
                'disable_welcome_panel' => 0,
                'disable_xmlrpc' => 0,
                'disable_admin_email_verification' => 0,
                'disable_emoji' => 0,
                'disable_google_fonts' => 0,
                'custom_error_page' => 0,
                'white_label' => 0
            ],
            'csp' => [
                'enabled' => '0',
                'default_src' => '\'self\'',
                'script_src' => '\'self\' \'unsafe-inline\'',
                'style_src' => '\'self\' \'unsafe-inline\'',
                'img_src' => '\'self\' data: *.gravatar.com',
                'font_src' => '\'self\' data:',
                'connect_src' => '\'self\'',
                'frame_src' => '\'self\'',
            ],
            'heartbeat' => [
                'disable_frontend' => '0',
                'disable_admin_non_editor' => '0',
                'force_js_slow' => '0',
                'editor_interval' => 15,
                'admin_interval' => 60,
                'role_overrides' => [
                    'administrator' => ['editor' => 15, 'default' => 60],
                    'editor'        => ['editor' => 30, 'default' => 90],
                    'author'        => ['editor' => 45, 'default' => 120],
                ],
                'admin_allowlist_hooks' => ['index.php'],
            ],
            'media' => [
                'sanitize_filename' => 0,
                'filter_nonimages_mimetypes' => 0,
                'enable_image_resize' => 0,
                'max_width_height' => 2000,
                'enable_sharpen_jpg_images' => 0,
                'enable_svg_support' => 0,
                'enable_filesize_column' => 0,
                'enable_file_replace' => 0,
                'enable_lazy_load' => 0,
                'mime_types' => '',
            ],
            'menus' => [
                'expand_collapse_menus' => 0,
                'menus_custom_column' => 0,
                'menus_custom_fields' => 0,
                'enhanced_menu_search' => 0,
            ],
            'plugins' => [
                'cf7_dequeue' => 0,
                'rrze_newsletter_global_settings' => 0,
                'rrze_newsletter_mail_queue_send_limit' => 15,
                'rrze_newsletter_mail_queue_max_retries' => 1,
                'rrze_newsletter_disable_subscription' => 0,
                'rrze_newsletter_sender_allowed_domains' => '',
                'rrze_newsletter_recipient_allowed_domains' => '',
                'rrze_newsletter_exceptions' => '',
                'cms_workflow_not_allowed_post_types' => '',
                'siteimprove_crawler_ip_addresses' => '',
                'wpseo_disable_metaboxes' => 0,
                'the_seo_framework_activate' => 0,
                'ws_form_license_key' => '',
                'ws_form_action_pdf_license_key' => '',
                'ws_form_not_allowed_field_types' => '',
                'ws_form_exceptions' => '',
                'dip_apiKey' => '',
                'faudir_public_apiKey' => '',
                'bite_api_key' => '',
                'dip_edu_api_key' => '',
                'rrze_search_engine_keys' => '',
                'rrze_search_limit_daily' => null,
                'rrze_search_limit_weekly' => null,
                'rrze_search_limit_monthly' => null,
                'rrze_search_limit_yearly' => null,
                'rrze_webt_api_url' => '',
                'rrze_webt_application_name' => '',
                'rrze_webt_password' => '',
                'rrze_webt_exceptions' => '',
            ],
            'rest' => [
                'disabled' => '0',
                'restwhite' => ['oembed'],
                'restnetwork' => [],
            ],
            'taxonomies' => [
                'exclude_nosearch_posts' => 0,
                'taxonomy_attachment_document' => 0,
                'taxonomy_attachment_category' => 0,
                'taxonomy_attachment_tag' => 0,
                'taxonomy_page_category' => 0,
                'taxonomy_page_tag' => 0,
            ],
            'tools' => [
                'disable_delete_site' => 0,
                'disable_privacy_options' => 0
            ],
            'users' => [
                'users_search' => 0,
                'pages_author_role' => 0,
                'super_author_role' => 0,
                'contact_page' => 0,
                'can_view_debug_log' => [],
            ],
            'writing' => [
                'enable_post_lock' => 0,
                'post_lock' => 150,
                'autosave_interval' => 60,
                'sync_autosave' => 0,
                'enable_block_editor' => 0,
                'try_enable_block_editor' => 0,
                'enable_classic_editor' => 1,
                'allowed_post_types' => '',
                'themes_exceptions' => '',
                'websites_exceptions' => '',
                'allowed_block_types' => '',
                'disabled_block_types' => '',
                'disable_block_directory_assets' => 1,
                'disable_remote_block_patterns' => 1,
                'disable_openverse_media' => 1,
                'disable_font_library_ui' => 1,
                'disable_code_editor' => 1,
                'code_editor_websites_exceptions' => '',
                'disable_custom_fields_metabox' => 0,
                'deactivated_plugins' => ''
            ],
            'mail' => [
                'sender' => '',
                'allowed_domains' => [],
                'admin_email_exceptions' => '',
            ],
            'discussion' => [
                'default_settings' => 1,
                'disable_avatars' => 0,
            ],
            'advanced' => [
                'frontend_style' => '',
                'backend_style' => '',
            ],
            'posts' => [
                'last_modified_custom_column' => 0,
                'page_list_table_dropdown' => 0
            ],
        ];

        return $options;
    }

    /**
     * Returns the default options
     * 
     * @return object
     */
    public static function getDefaultOptions(): object
    {
        $options = self::defaultOptions();
        return self::parseOptions($options);
    }

    /**
     * Returns the options
     * 
     * @param  boolean $network Is it a network option?
     * @return object Parsed options
     */
    public static function getOptions(bool $network = false): object
    {
        if ($network) {
            $options = (array) get_site_option(self::OPTION_NAME);
        } else {
            $options = (array) get_option(self::OPTION_NAME);
        }

        return self::parseOptions($options);
    }

    /**
     * Returns the site options
     * 
     * @return object
     */
    public static function getSiteOptions(): object
    {
        return self::getOptions(true);
    }

    /**
     * Returns the name of the option
     * 
     * @return string
     */
    public static function getOptionName(): string
    {
        return self::OPTION_NAME;
    }

    /**
     * Returns parsed options
     * 
     * @param  array $options Options to parse
     * @return object Parsed options
     */
    public static function parseOptions(array $options): object
    {
        $defaults = self::defaultOptions();
        $options = wp_parse_args($options, $defaults);
        $options = (object) array_intersect_key($options, $defaults);
        foreach ($defaults as $key => $value) {
            if (is_array($value)) {
                $options->$key = wp_parse_args($options->$key, $value);
                $options->$key = (object) array_intersect_key($options->$key, $value);
            }
        }
        return $options;
    }
}
