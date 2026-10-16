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
     * Adds a submenu page to the network admin menu
     * 
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('Content Security Policy (CSP)', 'rrze-settings'),
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
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getCspSections(): array
    {
        return [
            'rrze-settings-csp-activation' => [
                'title' => __('Activation and Scope', 'rrze-settings'),
                'callback' => 'activationSectionDescription',
            ],
            'rrze-settings-csp-sources' => [
                'title' => __('Default Rule and Source Format', 'rrze-settings'),
                'callback' => 'directivesSectionDescription',
            ],
            'rrze-settings-csp-code' => [
                'title' => __('Scripts and Styles', 'rrze-settings'),
                'callback' => 'codeSectionDescription',
            ],
            'rrze-settings-csp-assets' => [
                'title' => __('Images and Fonts', 'rrze-settings'),
                'callback' => 'assetsSectionDescription',
            ],
            'rrze-settings-csp-connections' => [
                'title' => __('Connections and Embedded Pages', 'rrze-settings'),
                'callback' => 'connectionsSectionDescription',
            ],
        ];
    }

    /**
     * Render the shared CSP form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage(): void
    {
        global $title;

        $sections = $this->getCspSections();
        echo '<div class="wrap rrze-settings-csp-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-csp-index" aria-labelledby="rrze-settings-csp-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-csp-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-csp" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-csp-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-csp-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
            echo '</div>';
            $this->{$section['callback']}();
            echo '<table class="form-table" role="presentation">';
            do_settings_fields($this->menuPage, $sectionId);
            echo '</table></section>';
        }
        submit_button(__('Save Changes', 'rrze-settings'), 'primary', $this->menuPage . '-submit-primary');
        echo '</form></div>';
    }

    /**
     * Register all source lists so the policy can be prepared before activation.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        foreach ($this->getCspSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'enabled',
            __('Enforce CSP', 'rrze-settings'),
            [$this, 'enabledField'],
            $this->menuPage,
            'rrze-settings-csp-activation'
        );

        add_settings_field(
            'default_src',
            __('Default Rule (default-src)', 'rrze-settings'),
            [$this, 'defaultSrcField'],
            $this->menuPage,
            'rrze-settings-csp-sources',
            ['label_for' => 'rrze-settings-default-src']
        );

        add_settings_field(
            'script_src',
            __('JavaScript (script-src)', 'rrze-settings'),
            [$this, 'scriptSrcField'],
            $this->menuPage,
            'rrze-settings-csp-code',
            ['label_for' => 'rrze-settings-script-src']
        );

        add_settings_field(
            'style_src',
            __('Stylesheets (style-src)', 'rrze-settings'),
            [$this, 'styleSrcField'],
            $this->menuPage,
            'rrze-settings-csp-code',
            ['label_for' => 'rrze-settings-style-src']
        );

        add_settings_field(
            'img_src',
            __('Images (img-src)', 'rrze-settings'),
            [$this, 'imgSrcField'],
            $this->menuPage,
            'rrze-settings-csp-assets',
            ['label_for' => 'rrze-settings-img-src']
        );

        add_settings_field(
            'font_src',
            __('Font Files (font-src)', 'rrze-settings'),
            [$this, 'fontSrcField'],
            $this->menuPage,
            'rrze-settings-csp-assets',
            ['label_for' => 'rrze-settings-font-src']
        );

        add_settings_field(
            'connect_src',
            __('Data Connections (connect-src)', 'rrze-settings'),
            [$this, 'connectSrcField'],
            $this->menuPage,
            'rrze-settings-csp-connections',
            ['label_for' => 'rrze-settings-connect-src']
        );

        add_settings_field(
            'frame_src',
            __('Embedded Pages (frame-src)', 'rrze-settings'),
            [$this, 'frameSrcField'],
            $this->menuPage,
            'rrze-settings-csp-connections',
            ['label_for' => 'rrze-settings-frame-src']
        );
    }

    /**
     * Introduce CSP and explain the network-wide scope.
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        echo '<p>', esc_html(__('Content Security Policy (CSP) tells the browser which sources a page may load and which code it may execute. These settings apply across the network to website responses handled by WordPress. Configure the source lists before enabling enforcement.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and input rules of the activation section.
     *
     * @return void
     */
    public function activationSectionDescription(): void
    {
        echo '<p>', esc_html(__('The policy is enforced by the browser when it receives the header. This page provides an enforcing policy, not a report-only mode. Missing sources can prevent scripts, styles or embedded content from loading.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and input rules of the sources section.
     *
     * @return void
     */
    public function directivesSectionDescription(): void
    {
        echo '<p>', esc_html(__('Enter one source per line, for example \'self\' or https://cdn.example.org. Enter values only, without directive names, semicolons or comments. Use straight single quotes for keywords; domains and schemes are not quoted.', 'rrze-settings')), '</p>';
        echo '<p>', esc_html(__('An empty field omits that directive; it does not mean block everything. The value \'none\' blocks the relevant resource type. If \'none\' is entered together with other sources, this plugin keeps only \'none\' when saving.', 'rrze-settings')), '</p>';
        $this->renderSettingDetails(__('Source Format', 'rrze-settings'), [
            __('The keyword \'self\' refers to the current origin, rather than every website in the network. An external host can be written as https://cdn.example.org. Replace example domains with the actual source hosts.', 'rrze-settings'),
            __('A wildcard such as *.example.org permits subdomains, not example.org itself. The values data: and blob: allow the corresponding URL types. A broad value such as https: allows all HTTPS hosts for that resource type.', 'rrze-settings'),
            __('This form does not provide a complete CSP syntax validator. Check the emitted response header and browser console after saving. A source allowed by this policy can still be blocked by another policy, browser rule or service.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the scope and input rules of the code section.
     *
     * @return void
     */
    public function codeSectionDescription(): void
    {
        echo '<p>', esc_html(__('These rules control JavaScript and CSS. An explicit source list replaces the default rule for that resource type. Include \'self\' again if files from the current website should remain allowed.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and input rules of the assets section.
     *
     * @return void
     */
    public function assetsSectionDescription(): void
    {
        echo '<p>', esc_html(__('Allow the actual sources of images and font files. A stylesheet and the font files it references may come from different hosts and therefore need entries in different rules.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and input rules of the connections section.
     *
     * @return void
     */
    public function connectionsSectionDescription(): void
    {
        echo '<p>', esc_html(__('Control connections made by page scripts and the pages loaded in frames. Allowing a frame does not automatically allow every script or connection used by the surrounding page.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain enforcement, retained values and response-header verification.
     *
     * @return void
     */
    public function enabledField(): void
    {
        $this->renderCheckbox('rrze-settings-enabled', $this->optionName . '[enabled]', $this->siteOptions->csp->enabled, __('Enable and enforce Content Security Policy', 'rrze-settings'));
        $this->renderDescription(__('Enabled: sends the Content-Security-Policy response header with the configured rules. Disabled: this module stops adding its header; the saved source lists remain available for editing.', 'rrze-settings'));
        $this->renderSettingDetails(__('Enforce CSP', 'rrze-settings'), [
            __('This applies to normal website responses through WordPress. Administration pages, static files and responses served directly from a cache or web server are not automatically covered by this hook.', 'rrze-settings'),
            __('After saving, load an affected website and inspect the Content-Security-Policy response header and browser console. Clear affected page caches if an old policy is still being served.', 'rrze-settings'),
            __('Other plugins, proxies or the web server may send their own CSP. Disabling this option does not remove those policies. This page provides neither a report destination nor a report viewer.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the default-src source list and its fallback behavior.
     *
     * @return void
     */
    public function defaultSrcField(): void
    {
        $this->renderTextarea('rrze-settings-default-src', $this->optionName . '[default_src]', $this->getDirectiveOption($this->siteOptions->csp->default_src, 'default_src'), 5, 55);
        $this->renderDescription(__('Provides the fallback for resource types without their own rule. Hosts collected from oEmbed providers and supported embed handlers can be added automatically to this list.', 'rrze-settings'));
        $this->renderSettingDetails(__('Default Rule (default-src)', 'rrze-settings'), [
            __('Example: with default-src set to \'self\' and img-src left blank, images use the default rule. If img-src has its own list, only that image list applies; default-src is not added to it.', 'rrze-settings'),
            __('The plugin adds collected embed hosts only when default-src is non-empty and not exactly \'none\'. These additions are not shown in the text field and can differ by website. Check the actual response header to see the effective list.', 'rrze-settings'),
            __('A blank field omits default-src. Resource types without another applicable directive then have no source restriction from this policy. The value \'none\' blocks the types that fall back to this rule; explicitly configured rules still apply.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the script-src source list and its fallback behavior.
     *
     * @return void
     */
    public function scriptSrcField(): void
    {
        $this->renderTextarea('rrze-settings-script-src', $this->optionName . '[script_src]', $this->getDirectiveOption($this->siteOptions->csp->script_src, 'script_src'), 5, 55);
        $this->renderDescription(__('Controls script sources and JavaScript execution. This can affect menus, forms, analytics and other interactive features. A blank field uses the default rule.', 'rrze-settings'));
        $this->renderSettingDetails(__('JavaScript (script-src)', 'rrze-settings'), [
            __('For an external script, add its actual host, for example https://scripts.example.org. Keep \'self\' if local script files are also needed. A host entry alone does not allow inline scripts.', 'rrze-settings'),
            __('The keyword \'unsafe-inline\' broadly permits inline JavaScript in a policy without nonces or hashes. The keyword \'unsafe-eval\' permits code generation from strings, such as eval(). These allowances reduce CSP protection and should reflect actual application requirements.', 'rrze-settings'),
            __('This page does not generate per-request nonces or add matching nonce attributes to scripts. Entering a nonce value here alone does not provide that integration.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the style-src source list and its fallback behavior.
     *
     * @return void
     */
    public function styleSrcField(): void
    {
        $this->renderTextarea('rrze-settings-style-src', $this->optionName . '[style_src]', $this->getDirectiveOption($this->siteOptions->csp->style_src, 'style_src'), 5, 55);
        $this->renderDescription(__('Controls CSS files and inline styling. Missing sources can change the appearance or make page elements unusable. A blank field uses the default rule.', 'rrze-settings'));
        $this->renderSettingDetails(__('Stylesheets (style-src)', 'rrze-settings'), [
            __('Example: \'self\' permits stylesheets from the current website; https://styles.example.org adds an external stylesheet host. Font files referenced by CSS are governed separately by font-src.', 'rrze-settings'),
            __('The keyword \'unsafe-inline\' broadly permits inline styles in a policy without nonces or hashes. Themes and plugins may depend on them. Removing this value can affect their layout.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the img-src source list and its fallback behavior.
     *
     * @return void
     */
    public function imgSrcField(): void
    {
        $this->renderTextarea('rrze-settings-img-src', $this->optionName . '[img_src]', $this->getDirectiveOption($this->siteOptions->csp->img_src, 'img_src'), 5, 55);
        $this->renderDescription(__('Controls image sources, including external thumbnails and avatars. A blank field uses the default rule; \'none\' blocks images under this directive.', 'rrze-settings'));
        $this->renderSettingDetails(__('Images (img-src)', 'rrze-settings'), [
            __('Example: \'self\' allows local images; https://images.example.org allows an external image host. The value data: permits images embedded as data URLs. Add blob: only if a feature uses blob URLs.', 'rrze-settings'),
            __('This controls loading in the browser. It does not change media upload permissions, delete stored images or prevent direct server access to an image URL.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the font-src source list and its fallback behavior.
     *
     * @return void
     */
    public function fontSrcField(): void
    {
        $this->renderTextarea('rrze-settings-font-src', $this->optionName . '[font_src]', $this->getDirectiveOption($this->siteOptions->csp->font_src, 'font_src'), 5, 55);
        $this->renderDescription(__('Controls downloaded font files, for example fonts referenced by @font-face. Blocked fonts can cause the browser to use fallback fonts. A blank field uses the default rule.', 'rrze-settings'));
        $this->renderSettingDetails(__('Font Files (font-src)', 'rrze-settings'), [
            __('Allow the host of the font file itself, for example https://fonts.example.org. The associated CSS may need a separate style-src entry. The value data: allows fonts embedded as data URLs.', 'rrze-settings'),
            __('This does not install fonts or control the WordPress Font Library. Font management and Google Fonts integration settings are configured separately under CMS > Functions and Services.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the connect-src source list and its fallback behavior.
     *
     * @return void
     */
    public function connectSrcField(): void
    {
        $this->renderTextarea('rrze-settings-connect-src', $this->optionName . '[connect_src]', $this->getDirectiveOption($this->siteOptions->csp->connect_src, 'connect_src'), 5, 55);
        $this->renderDescription(__('Controls destinations for browser requests made by scripts, such as fetch, XMLHttpRequest, WebSocket, EventSource and sendBeacon. A blank field uses the default rule.', 'rrze-settings'));
        $this->renderSettingDetails(__('Data Connections (connect-src)', 'rrze-settings'), [
            __('Example: allow https://api.example.org for an external API and wss://socket.example.org for a secure WebSocket connection. Use the actual protocol and host of the connection.', 'rrze-settings'),
            __('This is a browser-side restriction. It does not authorize REST API access, replace CORS checks or govern outgoing HTTP requests made by the WordPress server. Blocked connections are reported in the browser console.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain the frame-src source list and its fallback behavior.
     *
     * @return void
     */
    public function frameSrcField(): void
    {
        $this->renderTextarea('rrze-settings-frame-src', $this->optionName . '[frame_src]', $this->getDirectiveOption($this->siteOptions->csp->frame_src, 'frame_src'), 5, 55);
        $this->renderDescription(__('Controls which external pages this website may load in frames or iframes, for example a video player. A blank field falls back to the default rule in this plugin.', 'rrze-settings'));
        $this->renderSettingDetails(__('Embedded Pages (frame-src)', 'rrze-settings'), [
            __('Example: https://player.example.org allows frames from that host. If frame-src is filled in, embed hosts automatically added to default-src do not extend this list.', 'rrze-settings'),
            __('This does not decide which other websites may embed your website. That is the purpose of frame-ancestors, which is not configured on this page. The embedded provider can also refuse embedding through its own response headers.', 'rrze-settings'),
        ]);
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
        // WordPress collapses newlines and tabs to spaces before keyword checks.
        $inputAry = explode(' ', sanitize_text_field($input));
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
