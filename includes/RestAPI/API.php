<?php

namespace RRZE\Settings\RestAPI;

use RRZE\Settings\Library\Network\IP;
use RRZE\Settings\Library\Network\RemoteAddress;

defined('ABSPATH') || exit;

/**
 * API class
 *
 * @package RRZE\Settings\RestAPI
 */
class API
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * @var string
     */
    protected $requestUri;

    /**
     * @var string
     */
    protected $blogPath;

    /**
     * Constructor
     *
     * @param object $siteOptions
     * @return void
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;

        $this->requestUri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
        $this->blogPath = parse_url(untrailingslashit(get_home_url()), PHP_URL_PATH);
    }

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        add_filter('rest_pre_dispatch', [$this, 'restAccess'], 99, 3);
        add_filter('rrze_rest_api_approved_public_endpoints', [$this, 'approvedPublicEndpoints']);
        add_filter('rrze_rest_api_public_request_allowed', [$this, 'publicRequestAllowed'], 10, 3);
    }

    /**
     * Supply the network-approved choices for a site's public REST exceptions.
     */
    public function approvedPublicEndpoints($endpoints)
    {
        return PublicEndpoints::getApproved($this->siteOptions->rest);
    }

    /**
     * Revalidate a site's selection against the current network policy.
     */
    public function publicRequestAllowed($allowed, $selected, $request)
    {
        return PublicEndpoints::approvedRequestMatches($this->siteOptions->rest, $selected, $request);
    }

    /**
     * REST API access
     *
     * @param object $response
     * @param object $server
     * @param object $request
     * @return object
     */
    public function restAccess($response, $server, $request)
    {
        // Check authentication during REST dispatch, after WordPress has identified
        // application-password users as well as cookie-authenticated users.
        if (is_user_logged_in()) {
            return $response;
        }

        if (!$this->isRestAllowed($request)) {
            $response = new \WP_REST_Response(
                __('REST API support is restricted.', 'rrze-settings'),
                403
            );
        }

        return $response;
    }

    /**
     * Check if REST API is allowed
     *
     * @param object $request
     * @return boolean
     */
    protected function isRestAllowed($request)
    {
        // A registered route requires explicit approval even when a broad IP or
        // namespace exception applies, or the general REST restriction is off.
        if (PublicEndpoints::isRegisteredRoute($request->get_route())) {
            return $this->isPublicEndpointAllowed($request);
        }

        if (empty($this->siteOptions->rest->disabled)) {
            return true;
        }

        if ($this->isRestnetwork()) {
            return true;
        }

        $route = $request->get_route();
        if ($this->isRestwhite($route)) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the network administrator approved a registered endpoint.
     *
     * @param \WP_REST_Request $request The current REST request.
     * @return bool Whether the request matches an approved endpoint.
     */
    protected function isPublicEndpointAllowed($request)
    {
        $selected = $this->siteOptions->rest->restpublic ?? [];

        return PublicEndpoints::requestMatches($selected, $request);
    }

    /**
     * Check if REST API is allowed for the current request
     *
     * @return boolean
     */
    protected function isRestnetwork()
    {
        if (
            empty($this->siteOptions->rest->restnetwork) ||
            !is_array($this->siteOptions->rest->restnetwork)
        ) {
            return false;
        }

        $remoteAddr = $this->getRemoteIpAddress();
        if (!$remoteAddr) {
            return false;
        }

        $ipRanges = $this->getRestnetworkOption();
        $ip = IP::fromStringIP($remoteAddr);
        if ($ip->isInRanges($ipRanges)) {
            return true;
        }

        return false;
    }

    /**
     * Check if REST API is allowed for the current request
     *
     * @param string $route
     * @return boolean
     */
    protected function isRestwhite($route)
    {
        if (
            empty($this->siteOptions->rest->restwhite) ||
            !is_array($this->siteOptions->rest->restwhite)
        ) {
            return false;
        }

        if (!$route || !is_string($route)) {
            return false;
        }

        $route = '/' . trim($route, '/');

        foreach ($this->siteOptions->rest->restwhite as $exception) {
            if (!is_string($exception)) {
                continue;
            }

            $exception = '/' . trim(trim($exception), '/');
            if ($exception === '/') {
                continue;
            }

            if ($route === $exception || str_starts_with($route, $exception . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the remote IP address
     *
     * @return boolean
     */
    protected function getRemoteIpAddress()
    {
        return (new RemoteAddress())->getIpAddress();
    }

    /**
     * Disable REST API link in HTTP headers
     *
     * @return void
     */
    protected function disableRestApiHeaders()
    {
        // Disable REST API links in HTTP headers
        // Link: <https://example.com/wp-json/>; rel="https://api.w.org/"
        remove_action(
            'template_redirect',
            'rest_output_link_header',
            11
        );

        // Disable REST API link in HTTP headers
        // Link: <https://example.com/wp-json/wp/v2/>; rel="https://api.w.org/"
        remove_action(
            'xmlrpc_rsd_apis',
            'rest_output_rsd'
        );
    }

    /**
     * Disable REST API link in HTML <head>
     *
     * @return void
     */
    protected function disableRestApiHtml()
    {
        // Disable REST API links in HTML <head>
        // <link rel='https://api.w.org/' href='https://example.com/wp-json/' />
        remove_action(
            'wp_head',
            'rest_output_link_wp_head'
        );

        // Disable REST API links in HTML <head>
        // <link rel='https://api.w.org/' href='https://example.com/wp-json/wp/v2/' />
        remove_action(
            'xmlrpc_rsd_apis',
            'rest_output_rsd'
        );
    }

    /**
     * Get the REST API options
     *
     * @return array
     */
    protected function getRestnetworkOption()
    {
        $option = $this->siteOptions->rest->restnetwork;
        if (empty($option) || !is_array($option)) {
            return [];
        }
        $ipRanges = [];
        foreach ($option as $value) {
            $valueAry = explode('#', $value);
            $valueAry = array_filter(array_map('trim', $valueAry));
            if (!empty($valueAry[0])) {
                $ipRanges[] = $valueAry[0];
            }
        }

        return $ipRanges;
    }
}
