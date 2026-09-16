<?php

namespace RRZE\Settings\CSP;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;

/**
 * Class Settings
 *
 * This class handles the settings for the Content Security Policy (CSP) in WordPress.
 *
 * @package RRZE\Settings\CSP
 */
class Settings extends MainSettings
{
    /**
     * The menu page slug.
     * 
     * @var string
     */
    protected $menuPage = 'rrze-settings-csp';

    /**
     * The section name for the general settings.
     * 
     * @var string
     */
    protected $generalSectionName = 'rrze-settings-csp-general-section';

    /**
     * The section name for the directives settings.
     * 
     * @var string
     */
    protected $directivesSectionName = 'rrze-settings-csp-directives-section';

    /**
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Content Security Police', 'rrze-settings'),
            __('CSP', 'rrze-settings'),
            'manage_options',
            $this->menuPage,
            [$this, 'optionsPage']
        );
    }

    /**
     * Validate the options
     * 
     * @param array $input The input data
     * @return object The validated options
     */
    public function optionsValidate($input)
    {
        $input['enabled'] = !empty($input['enabled']) ? 1 : 0;

        if (isset($input['default_src'])) {
            $input['default_src'] = $this->sanitizeDirectiveField($input, 'default_src');
        }

        if (isset($input['script_src'])) {
            $input['script_src'] = $this->sanitizeDirectiveField($input, 'script_src');
        }

        if (isset($input['style_src'])) {
            $input['style_src'] = $this->sanitizeDirectiveField($input, 'style_src');
        }

        if (isset($input['img_src'])) {
            $input['img_src'] = $this->sanitizeDirectiveField($input, 'img_src');
        }

        if (isset($input['font_src'])) {
            $input['font_src'] = $this->sanitizeDirectiveField($input, 'font_src');
        }

        if (isset($input['connect_src'])) {
            $input['connect_src'] = $this->sanitizeDirectiveField($input, 'connect_src');
        }

        if (isset($input['frame_src'])) {
            $input['frame_src'] = $this->sanitizeDirectiveField($input, 'frame_src');
        }

        return $this->parseOptionsValidate($input, 'csp');
    }

    /**
     * Adds sections and fields to the settings page
     * 
     * @return void
     */
    public function networkAdminPage()
    {
        add_settings_section(
            $this->generalSectionName,
            __('CSP', 'rrze-settings'),
            [$this, 'mainSectionDescription'],
            $this->menuPage
        );

        add_settings_field(
            'enabled',
            __('Enable CSP', 'rrze-settings'),
            [$this, 'enabledField'],
            $this->menuPage,
            $this->generalSectionName
        );

        if ($this->siteOptions->csp->enabled) {
            add_settings_section(
                $this->directivesSectionName,
                __('Directives', 'rrze-settings'),
                [$this, 'directivesSectionDescription'],
                $this->menuPage
            );

            add_settings_field(
                'default_src',
                __('default-src', 'rrze-settings'),
                [$this, 'defaultSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'script_src',
                __('script-src', 'rrze-settings'),
                [$this, 'scriptSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'style_src',
                __('style-src', 'rrze-settings'),
                [$this, 'styleSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'img_src',
                __('img-src', 'rrze-settings'),
                [$this, 'imgSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'font_src',
                __('font-src', 'rrze-settings'),
                [$this, 'fontSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'connect_src',
                __('connect-src', 'rrze-settings'),
                [$this, 'connectSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );

            add_settings_field(
                'frame_src',
                __('frame-src', 'rrze-settings'),
                [$this, 'frameSrcField'],
                $this->menuPage,
                $this->directivesSectionName
            );
        }
    }

    /**
     * Display the main section description
     * 
     * @return void
     */
    public function mainSectionDescription()
    {
        esc_html_e("The Content-Security-Policy HTTP response header helps to reduce XSS risks on modern browsers by declaring, which dynamic resources are allowed to load.", 'rrze-settings');
    }

    /**
     * Display the directives section description
     * 
     * @return void
     */
    public function directivesSectionDescription()
    {
        esc_html_e("The Content-Security-Policy header value is made up of one or more directives (defined below). All of these directives ending in -src support similar values known as source lists.  Multiple source list values can be separated with a new line, except for 'none', which should be the only value.", 'rrze-settings');
    }

    /**
     * Display the enabled field
     * 
     * @return void
     */
    public function enabledField()
    {
        $this->renderCheckbox('rrze-settings-enabled', sprintf('%s[enabled]', $this->optionName), $this->siteOptions->csp->enabled, __('Enable Content Security Police', 'rrze-settings'));
    }

    /**
     * Display the default_src field
     * 
     * @return void
     */
    public function defaultSrcField()
    {
        $this->renderTextarea('rrze-settings-default-src', sprintf('%s[default_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->default_src, 'default_src'), 5, 55);
        $this->renderDescription(__('The default-src is the default policy for loading content such as JavaScript, Images, CSS, Fonts, AJAX requests, Frames, HTML5 Media.', 'rrze-settings'));
    }

    /**
     * Display the script_src field
     * 
     * @return void
     */
    public function scriptSrcField()
    {
        $this->renderTextarea('rrze-settings-script-src', sprintf('%s[script_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->script_src, 'script_src'), 5, 55);
        $this->renderDescription(__('Defines valid sources of JavaScript.', 'rrze-settings'));
    }

    /**
     * Display the style_src field
     * 
     * @return void
     */
    public function styleSrcField()
    {
        $this->renderTextarea('rrze-settings-style-src', sprintf('%s[style_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->style_src, 'style_src'), 5, 55);
        $this->renderDescription(__('Defines valid sources of stylesheets.', 'rrze-settings'));
    }

    /**
     * Display the img_src field
     * 
     * @return void
     */
    public function imgSrcField()
    {
        $this->renderTextarea('rrze-settings-img-src', sprintf('%s[img_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->img_src, 'img_src'), 5, 55);
        $this->renderDescription(__('Defines valid sources of images.', 'rrze-settings'));
    }

    /**
     * Display the font_src field
     * 
     * @return void
     */
    public function fontSrcField()
    {
        $this->renderTextarea('rrze-settings-font-src', sprintf('%s[font_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->font_src, 'font_src'), 5, 55);
        $this->renderDescription(__('Defines valid sources of fonts.', 'rrze-settings'));
    }

    /**
     * Display the connect_src field
     * 
     * @return void
     */
    public function connectSrcField()
    {
        $this->renderTextarea('rrze-settings-connect-src', sprintf('%s[connect_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->connect_src, 'connect_src'), 5, 55);
        $this->renderDescription(__('Applies to XMLHttpRequest (AJAX), WebSocket or EventSource. If not allowed the browser emulates a 400 HTTP status code.', 'rrze-settings'));
    }

    /**
     * Display the frame_src field
     * 
     * @return void
     */
    public function frameSrcField()
    {
        $this->renderTextarea('rrze-settings-frame-src', sprintf('%s[frame_src]', $this->optionName), $this->getDirectiveOption($this->siteOptions->csp->frame_src, 'frame_src'), 5, 55);
        $this->renderDescription(__('Defines valid sources for embedding the resource using <frame> or <iframe>.', 'rrze-settings'));
    }

    /**
     * Sanitize the directive field
     * 
     * @param array $input The input data
     * @param string $directive The directive name
     * @return string The sanitized directive value
     */
    protected function sanitizeDirectiveField(array $input, string $directive): string
    {
        $input = $input[$directive] ?? $this->defaultOptions->csp->$directive;
        $inputAry = explode(PHP_EOL, sanitize_text_field($input));
        $inputAry = array_filter(array_map('trim', $inputAry));
        if (empty($inputAry)) {
            // return $this->defaultOptions->csp->$directive;
            return '';
        }
        $none = '\'none\'';
        $isNone = false;
        foreach ($inputAry as $value) {
            if (stripslashes($value) == $none) {
                $isNone = true;
            }
        }
        if ($isNone) {
            $inputAry = [$none];
        }

        return implode(' ', $inputAry);
    }

    /**
     * Get the directive option
     * 
     * @param string $input The input data
     * @param string $directive The directive name
     * @return string The directive option value
     */
    protected function getDirectiveOption(string $input, string $directive): string
    {
        $inputAry = array_map('stripslashes', explode(' ', $input));
        $inputAry = array_filter(array_map('trim', $inputAry));
        if (empty($inputAry)) {
            // $inputAry = explode(' ', $this->defaultOptions->csp->$directive);
            // $inputAry = array_filter(array_map('trim', $inputAry));
            $inputAry = [];
        }
        return implode(PHP_EOL, $inputAry);
    }
}
