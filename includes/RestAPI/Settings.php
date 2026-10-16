<?php

namespace RRZE\Settings\RestAPI;

defined('ABSPATH') || exit;

use RRZE\Settings\Settings as MainSettings;
use RRZE\Settings\Library\Network\IPUtils;

/**
 * Class Settings
 *
 * This class handles the settings for the REST API section of the plugin.
 *
 * @package RRZE\Settings\RestAPI
 */
class Settings extends MainSettings
{
    /**
     * Menu page slug
     *
     * @var string
     */
    protected $menuPage = 'rrze-settings-rest-api';

    /**
     * Adds a submenu page to the network admin menu
     *
     * @return void
     */
    public function networkAdminMenu()
    {
        add_submenu_page(
            'rrze-settings',
            __('REST API', 'rrze-settings'),
            __('REST API', 'rrze-settings'),
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
        $input['disabled'] = !empty($input['disabled']) ? 1 : 0;

        if (isset($input['restnetwork'])) {
            $input['restnetwork'] = $this->sanitizeRestnetworkField($input['restnetwork']);
        }

        if (isset($input['restwhite'])) {
            $input['restwhite'] = $this->sanitizeRestwhiteField($input['restwhite']);
        }

        $input['restpublic'] = $this->sanitizePublicRestEndpoints($input['restpublic'] ?? []);

        return $this->parseOptionsValidate($input, 'rest');
    }

    /**
     * Define sections shared by navigation, rendering and field registration.
     *
     * @return array<string, array{title: string, callback: string}>
     */
    protected function getRestApiSections(): array
    {
        return [
            'rrze-settings-rest-api-access' => [
                'title' => __('Access Without Authentication', 'rrze-settings'),
                'callback' => 'accessSectionDescription',
            ],
            'rrze-settings-rest-api-exceptions' => [
                'title' => __('IP and Route Exceptions', 'rrze-settings'),
                'callback' => 'exceptionsSectionDescription',
            ],
            'rrze-settings-rest-api-endpoints' => [
                'title' => __('Public Plugin Endpoints', 'rrze-settings'),
                'callback' => 'endpointsSectionDescription',
            ],
        ];
    }

    /**
     * Render the shared REST API form with an index and focusable section targets.
     *
     * @return void
     */
    public function optionsPage(): void
    {
        global $title;

        $sections = $this->getRestApiSections();
        echo '<div class="wrap rrze-settings-rest-api-page"><h1>', esc_html($title), '</h1>';
        $this->mainSectionDescription();
        echo '<nav id="rrze-settings-rest-api-index" aria-labelledby="rrze-settings-rest-api-index-title" tabindex="-1">';
        echo '<h2 id="rrze-settings-rest-api-index-title">', esc_html(__('On this page', 'rrze-settings')), '</h2><ul>';
        foreach ($sections as $sectionId => $section) {
            printf('<li><a href="#%1$s">%2$s</a></li>', esc_attr($sectionId), esc_html($section['title']));
        }
        echo '</ul></nav><form method="post">';
        settings_fields($this->menuPage);
        foreach ($sections as $sectionId => $section) {
            printf('<section id="%1$s" class="rrze-settings-rest-api" aria-labelledby="%1$s-title" tabindex="-1">', esc_attr($sectionId));
            echo '<div class="rrze-settings-rest-api-heading">';
            printf('<h2 id="%1$s-title">%2$s</h2>', esc_attr($sectionId), esc_html($section['title']));
            printf('<a href="#rrze-settings-rest-api-index">%1$s<span class="screen-reader-text">: %2$s</span></a>', esc_html__('Back to overview', 'rrze-settings'), esc_html($section['title']));
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
     * Register all controls, including IP exceptions while the restriction is off.
     *
     * @return void
     */
    public function networkAdminPage(): void
    {
        foreach ($this->getRestApiSections() as $sectionId => $section) {
            add_settings_section($sectionId, $section['title'], [$this, $section['callback']], $this->menuPage);
        }

        add_settings_field(
            'disabled',
            __('General Restriction', 'rrze-settings'),
            [$this, 'disabledField'],
            $this->menuPage,
            'rrze-settings-rest-api-access'
        );

        add_settings_field(
            'restnetwork',
            __('Allowed IP Addresses and Networks', 'rrze-settings'),
            [$this, 'restnetworkField'],
            $this->menuPage,
            'rrze-settings-rest-api-exceptions',
            ['label_for' => 'rrze-settings-restnetwork']
        );

        add_settings_field(
            'restwhite',
            __('Allowed Namespaces and Routes', 'rrze-settings'),
            [$this, 'restwhiteField'],
            $this->menuPage,
            'rrze-settings-rest-api-exceptions',
            ['label_for' => 'rrze-settings-restwhite']
        );

        add_settings_field(
            'restpublic',
            __('Plugin Approvals', 'rrze-settings'),
            [$this, 'publicRestEndpointsField'],
            $this->menuPage,
            'rrze-settings-rest-api-endpoints'
        );
    }

    /**
     * Introduce REST routes and the network-wide scope of the form.
     *
     * @return void
     */
    public function mainSectionDescription(): void
    {
        echo '<p>', esc_html(__('The REST API lets WordPress, plugins and external applications exchange data. Configure access without authentication across all websites in the network. A route is an API path; a namespace groups related routes.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and precedence of the access section.
     *
     * @return void
     */
    public function accessSectionDescription(): void
    {
        echo '<p>', esc_html(__('The general restriction controls REST requests without a recognized WordPress user. Approved exceptions can permit access; endpoint permissions and private-site restrictions still apply.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and precedence of the exceptions section.
     *
     * @return void
     */
    public function exceptionsSectionDescription(): void
    {
        echo '<p>', esc_html(__('IP addresses and routes provide two independent exceptions to the general restriction. A match in either list is sufficient for this restriction. Registered plugin endpoints always need their own approval below.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the scope and precedence of the endpoints section.
     *
     * @return void
     */
    public function endpointsSectionDescription(): void
    {
        echo '<p>', esc_html(__('These plugin endpoints require explicit approval for requests without authentication, even when the general restriction is off or an IP or route exception matches. Approval covers only the exact route and the listed HTTP methods.', 'rrze-settings')), '</p>';
        echo '<p>', esc_html(__('For visitors without access to a website protected by RRZE Private Site, the site administrator must also select a local exception under Settings > Reading > Public REST API Routes. Those public exceptions support GET and HEAD only.', 'rrze-settings')), '</p>';
    }

    /**
     * Explain the general anonymous-access restriction and authenticated requests.
     *
     * @return void
     */
    public function disabledField(): void
    {
        $this->renderCheckbox('rrze-settings-disabled', $this->optionName . '[disabled]', $this->siteOptions->rest->disabled, __('Restrict REST requests without authentication', 'rrze-settings'));
        $this->renderDescription(__('Enabled: requests without authentication are denied with HTTP 403 unless an approved exception applies. Requests authenticated by WordPress are not blocked by this setting.', 'rrze-settings'));
        $this->renderSettingDetails(__('General Restriction', 'rrze-settings'), [
            __('Disabled: ordinary REST routes are no longer restricted by this setting. Registered plugin endpoints still require their own checkbox approval, and other access checks remain in place.', 'rrze-settings'),
            __('Example: a public search, form or external application may need REST access without a WordPress login. Allow the required route or registered endpoint instead of assuming that all public features use authenticated requests.', 'rrze-settings'),
            __('A browser login alone is not enough for cookie-based REST authentication: the request also needs a valid REST nonce. Requests without that nonce are treated as anonymous. Application-password authentication can also identify a WordPress user.', 'rrze-settings'),
            __('This setting does not make the public website private, change user roles or control XML-RPC. XML-RPC is configured under RRZE Settings > Functions and Services.', 'rrze-settings'),
        ]);
    }

    /**
     * Render persistent IP exceptions with supported input formats and proxy guidance.
     *
     * @return void
     */
    public function restnetworkField(): void
    {
        $this->renderTextarea('rrze-settings-restnetwork', $this->optionName . '[restnetwork]', $this->getRestnetworkOption(), 6, 46);
        $this->renderDescription(__('One IPv4/IPv6 address or CIDR network per line. Matching visitors bypass the general restriction for ordinary routes, regardless of HTTP method. These entries take effect only while the general restriction is enabled.', 'rrze-settings'));
        $this->renderSettingDetails(__('Allowed IP Addresses and Networks', 'rrze-settings'), [
            __('Examples: 192.0.2.25 for one address, 198.51.100.0/24 for an IPv4 network, or 2001:db8::/64 for an IPv6 network. These are documentation addresses; replace them with the actual client addresses.', 'rrze-settings'),
            __('Add a comment after an address using #, for example 192.0.2.25 # Import service. Hostnames, URLs and start-end ranges are not supported. Invalid entries are omitted when saving; valid entries are stored in CIDR notation.', 'rrze-settings'),
            __('An IP exception does not replace a registered endpoint approval or grant access to a private website. A blank list provides no IP exceptions. Switching off the general restriction retains the list for later use.', 'rrze-settings'),
            __('The client address detected by the server is used. Behind a reverse proxy, trusted proxies must be configured separately by the server administrator. This field grants visitor access; it does not configure proxy trust.', 'rrze-settings'),
        ]);
    }

    /**
     * Explain route prefixes, method scope and their separate use by private sites.
     *
     * @return void
     */
    public function restwhiteField(): void
    {
        $this->renderTextarea('rrze-settings-restwhite', $this->optionName . '[restwhite]', $this->getRestwhiteOption(), 7, 46);
        $this->renderDescription(__('One namespace or route per line, for example /wp/v2/pages. Each entry permits the route and its subroutes through the general restriction, for all HTTP methods. Endpoint permissions still apply.', 'rrze-settings'));
        $this->renderDescription(__('RRZE Private Site also offers these entries as local public exceptions, even when the general restriction is off. The site administrator must select them there; local public exceptions allow GET and HEAD only.', 'rrze-settings'));
        $this->renderSettingDetails(__('Allowed Namespaces and Routes', 'rrze-settings'), [
            __('Example: /wp/v2/pages includes /wp/v2/pages/42, but not /wp/v2/pages-other. The namespace /wp/v2 includes all routes beneath it and is therefore broader than a single route.', 'rrze-settings'),
            __('Use the API path without a domain, /wp-json prefix, query parameters or # comments. Entries are case-sensitive. Leading and trailing slashes are optional; wildcards are not supported. The entry pages does not match /wp/v2/pages.', 'rrze-settings'),
            __('A registered plugin endpoint within an allowed namespace still needs its own checkbox approval. The namespace exception does not replace it.', 'rrze-settings'),
            __('Removing an entry also removes the corresponding public exception from private sites. Changing its path requires a new local selection. A blank list provides no namespace or route exceptions.', 'rrze-settings'),
        ]);
    }

    /**
     * Render individually labelled plugin approvals with route and method context.
     *
     * @return void
     */
    public function publicRestEndpointsField(): void
    {
        $endpoints = $this->getPublicRestEndpoints();
        $selected = $this->siteOptions->rest->restpublic ?? [];
        $selected = is_array($selected) ? $selected : [];

        if (empty($endpoints)) {
            $this->renderDescription(__('No plugin endpoints are available for approval yet. If a plugin is active only on an individual website, visit that website once and reload this settings page.', 'rrze-settings'));
        } else {
            echo '<fieldset class="rrze-settings-rest-endpoints"><legend class="screen-reader-text">', esc_html(__('Approve plugin endpoints for access without authentication', 'rrze-settings')), '</legend>';
            foreach ($endpoints as $id => $endpoint) {
                $fieldId = 'rrze-settings-rest-public-' . $id;
                $descriptionIds = $fieldId . '-route';
                if ($endpoint['description'] !== '') {
                    $descriptionIds .= ' ' . $fieldId . '-description';
                }
                echo '<div class="rrze-settings-rest-endpoint">';
                printf(
                    '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s[restpublic][]" value="%3$s" %4$s aria-describedby="%5$s"> <strong>%6$s</strong></label>',
                    esc_attr($fieldId),
                    esc_attr($this->optionName),
                    esc_attr($id),
                    checked(in_array($id, $selected, true), true, false),
                    esc_attr($descriptionIds),
                    esc_html($endpoint['label'])
                );
                printf(
                    '<p id="%1$s-route" class="rrze-settings-rest-endpoint-route"><span>%2$s</span> <code>%3$s</code><br><span>%4$s</span> <code>%5$s</code></p>',
                    esc_attr($fieldId),
                    esc_html(__('Methods:', 'rrze-settings')),
                    esc_html(implode(', ', $endpoint['methods'])),
                    esc_html(__('Route:', 'rrze-settings')),
                    esc_html($endpoint['route'])
                );
                if ($endpoint['description'] !== '') {
                    printf('<p id="%1$s-description" class="description">%2$s</p>', esc_attr($fieldId), esc_html($endpoint['description']));
                }
                echo '</div>';
            }
            echo '</fieldset>';
        }
        $this->renderSettingDetails(__('Plugin Approvals', 'rrze-settings'), [
            __('GET reads content; HEAD requests response headers without the content body. POST can submit data. An approval includes only the methods listed for that endpoint, not its subroutes.', 'rrze-settings'),
            __('The endpoint still checks its own permissions, for example a valid signed download URL. Approval does not bypass these checks or other access-control plugins.', 'rrze-settings'),
            __('Private-site public exceptions require both network approval and a local selection. POST-only endpoints are not offered as public exceptions there. Visitors who already have site access do not need an additional local checkbox; the network rules still apply.', 'rrze-settings'),
            __('Only endpoints declared by plugins appear here; this is not a list of every WordPress REST route. Remembered declarations may come from other websites. Approval applies where the provider declares the endpoint on the current website. Unchecking an endpoint also disables its local public exception.', 'rrze-settings'),
        ]);
    }

    /**
     * Get the REST network option
     *
     * @return string The formatted REST network option
     */
    protected function getRestnetworkOption()
    {
        $option = $this->siteOptions->rest->restnetwork;
        if (empty($option) || !is_array($option)) {
            return '';
        }

        $renderedOption = [];
        $option = array_filter($option);
        foreach ($option as $value) {
            $valueAry = explode('#', $value);
            $valueAry = array_filter(array_map('trim', $valueAry));
            $ipRange = $valueAry[0] ?? '';
            $comment = $valueAry[1] ?? '';
            if (!empty($ipRange)) {
                $renderedOption[] = $comment ? $ipRange . ' #' . $comment : $ipRange;
            }
        }

        return !empty($renderedOption) ? implode(PHP_EOL, $renderedOption) : '';
    }

    /**
     * Sanitize the REST network field
     *
     * @param string $input The input data
     * @return array The sanitized IP ranges
     */
    protected function sanitizeRestnetworkField($input)
    {
        if (empty($input)) {
            return [];
        }
        $ipRanges = [];
        $comments = [];
        $inputAry = array_filter(array_map('trim', explode(PHP_EOL, sanitize_textarea_field($input))));
        foreach ($inputAry as $key => $value) {
            $valueAry = explode('#', $value);
            $valueAry = array_filter(array_map('trim', $valueAry));
            $ipRange = $valueAry[0] ?? '';
            $comment = $valueAry[1] ?? '';
            if (!empty($ipRange)) {
                $ipRanges[$key] = $ipRange;
                $comments[$key] = $comment;
            }
        }

        $ipRanges = $this->getIpRanges($ipRanges);
        if (!empty($ipRanges)) {
            foreach ($ipRanges as $key => $value) {
                $comment = $comments[$key] ?? '';
                $ipRanges[$key] = $comment ? $value . ' #' . $comments[$key] : $value;
            }
        }

        return $ipRanges;
    }

    /**
     * Get the IP ranges from the input data
     *
     * @param array $data The input data
     * @return array The sanitized IP ranges
     */
    protected function getIpRanges($data)
    {
        $ipRanges = [];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                $sanitizedValue = IPUtils::sanitizeIpRange($value);
                if (!is_null($sanitizedValue)) {
                    $ipRanges[$key] = $sanitizedValue;
                }
            }
        }
        return $ipRanges;
    }

    /**
     * Get the REST white option
     *
     * @return string The formatted REST white option
     */
    protected function getRestwhiteOption()
    {
        $option = $this->siteOptions->rest->restwhite;
        if (!empty($option) && is_array($option)) {
            return implode(PHP_EOL, $option);
        }
        return '';
    }

    /**
     * Sanitize the REST white field
     *
     * @param string $input The input data
     * @return array|string The sanitized REST white option
     */
    protected function sanitizeRestwhiteField($input)
    {
        if (!empty($input)) {
            return array_filter(array_map('trim', explode(PHP_EOL, sanitize_textarea_field($input))));
        }
        return '';
    }

    /**
     * Validate the registered public endpoints selected by the network administrator.
     *
     * @param mixed $input Submitted endpoint IDs.
     * @return array Valid registered endpoint IDs.
     */
    protected function sanitizePublicRestEndpoints($input)
    {
        return PublicEndpoints::sanitizeSelection($input);
    }

    /**
     * Return valid endpoint definitions supplied by installed plugins.
     *
     * @return array<string, array{label: string, route: string, methods: array, description: string}>
     */
    protected function getPublicRestEndpoints()
    {
        return PublicEndpoints::getForSettings();
    }
}
