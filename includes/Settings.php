<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

use RRZE\Settings\Options;

/**
 * Class Settings
 *
 * This class handles the settings for the plugin.
 *
 * @package RRZE\Settings
 */
class Settings
{
    /**
     * Option name
     * 
     * @var string
     */
    protected $optionName;

    /**
     * Options
     * 
     * @var object
     */
    protected $options;

    /**
     * Site options
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Default options
     * 
     * @var object
     */
    protected $defaultOptions;

    /**
     * Menu page slug
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings';

    /**
     * Settings section name
     * 
     * @var string
     */
    protected $sectionName;

    /**
     * Constructor
     *
     * @param string $optionName
     * @param object $options
     * @param object $siteOptions
     * @param object $defaultOptions
     * @return void
     */
    public function __construct($optionName, $options, $siteOptions, $defaultOptions)
    {
        $this->optionName = $optionName;
        $this->options = $options;
        $this->siteOptions = $siteOptions;
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        if (is_network_admin()) {
            add_action('network_admin_menu', [$this, 'networkAdminMenu']);
            add_action('network_admin_menu', [$this, 'settingsUpdate']);
            add_action('network_admin_menu', [$this, 'networkAdminPage']);
        } else {
            add_action('admin_menu', [$this, 'adminPage']);
        }
    }

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_menu_page(
            __('CMS', 'rrze-settings'),
            __('CMS', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            '__return_false',
            'dashicons-admin-settings'
        );
    }

    /**
     * Options page
     *
     * @return void
     */
    public function optionsPage()
    {
        global $title;
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($title) . '</h1>';
        echo '<form method="post">';
        do_settings_sections($this->menuPage);
        settings_fields($this->menuPage);
        submit_button(__('Save Changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo '</form>';
        echo '</div>';
    }

    /**
     * Adds sections and fields to the settings page
     *
     * @return void
     */
    public function networkAdminPage() {}

    /**
     * Registers a setting and its data
     *
     * @return void
     */
    public function adminPage() {}

    /**
     * Validate the options
     *
     * @param array $input The input data
     * @return array The validated options
     */
    public function optionsValidate($input)
    {
        return $input;
    }

    /**
     * Parse options validate
     *
     * @param array $input
     * @param string $type
     * @return object
     */
    protected function parseOptionsValidate(array $input, string $type)
    {
        if (is_network_admin()) {
            $this->siteOptions->{$type} = (object) wp_parse_args($input, (array) $this->siteOptions->{$type});
        } else {
            $this->options->{$type} = (object) wp_parse_args($input, (array) $this->options->{$type});
        }
        return is_network_admin() ? $this->siteOptions : $this->options;
    }

    /**
     * Settings update
     *
     * @return void
     */
    public function settingsUpdate()
    {
        if (is_network_admin() && isset($_POST[$this->menuPage . '-submit-primary'])) {
            check_admin_referer($this->menuPage . '-options');
            $input = isset($_POST[$this->optionName]) ? $_POST[$this->optionName] : [];
            $siteOptions = $this->optionsValidate($input);
            if (is_object($siteOptions)) {
                update_site_option($this->optionName, $siteOptions);
                $this->siteOptions = Options::getSiteOptions();
                add_action('network_admin_notices', [$this, 'settingsUpdateNotice']);
            }
        }
    }

    /**
     * Settings update notice
     *
     * @return void
     */
    public function settingsUpdateNotice()
    {
        $class = 'notice updated';
        $message = __('Settings saved.', 'rrze-settings');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    /**
     * Render a checkbox setting.
     *
     * @param string $id Field ID.
     * @param string $name Field name.
     * @param mixed  $value Current value.
     * @param string $label Field label.
     * @return void
     */
    protected function renderCheckbox(string $id, string $name, $value, string $label): void
    {
        printf(
            '<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
            esc_attr($id),
            esc_attr($name),
            checked($value, 1, false),
            esc_html($label)
        );
    }

    /**
     * Render a text-like input setting.
     *
     * @param string $type Input type.
     * @param string $id Field ID.
     * @param string $name Field name.
     * @param mixed  $value Current value.
     * @param string $class CSS class.
     * @param array  $attributes Additional attributes.
     * @return void
     */
    protected function renderInput(string $type, string $id, string $name, $value, string $class = 'regular-text', array $attributes = []): void
    {
        printf(
            '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="%5$s"%6$s>',
            esc_attr($type),
            esc_attr($id),
            esc_attr($name),
            esc_attr($value),
            esc_attr($class),
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped in renderAttributes().
            $this->renderAttributes($attributes)
        );
    }

    /**
     * Render a textarea setting.
     *
     * @param string $id Field ID.
     * @param string $name Field name.
     * @param mixed  $value Current value.
     * @param int    $rows Rows.
     * @param int    $cols Columns.
     * @return void
     */
    protected function renderTextarea(string $id, string $name, $value, int $rows = 5, int $cols = 50): void
    {
        printf(
            '<textarea id="%1$s" cols="%2$d" rows="%3$d" name="%4$s">%5$s</textarea>',
            esc_attr($id),
            absint($cols),
            absint($rows),
            esc_attr($name),
            esc_textarea($value)
        );
    }

    /**
     * Render a field description.
     *
     * @param string $description Description.
     * @return void
     */
    protected function renderDescription(string $description): void
    {
        printf('<p class="description">%s</p>', esc_html($description));
    }

    /**
     * Render HTML attributes.
     *
     * @param array $attributes Attributes.
     * @return string Rendered attributes.
     */
    protected function renderAttributes(array $attributes): string
    {
        $output = '';
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $output .= ' ' . esc_attr($name);
                continue;
            }

            $output .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
        }

        return $output;
    }
}
