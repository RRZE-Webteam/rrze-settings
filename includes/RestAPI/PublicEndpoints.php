<?php

namespace RRZE\Settings\RestAPI;

defined('ABSPATH') || exit;

/**
 * Registry for public REST endpoints declared by installed plugins.
 */
final class PublicEndpoints
{
    private const DISCOVERED_OPTION = 'rrze_rest_api_public_endpoints';
    private const NETWORK_ROUTE_PREFIX = 'network-route-';

    /**
     * Remember endpoint metadata discovered on any site in the network.
     *
     * Site-specific plugins are not loaded in Network Admin. Persisting their
     * declarations makes them available in the network settings after they have
     * been observed on a site where the plugin is active.
     *
     * @return void
     */
    public static function discover()
    {
        $live = self::getLive();
        if (empty($live)) {
            return;
        }

        $known = self::normalize(get_site_option(self::DISCOVERED_OPTION, []));
        $discovered = $known;
        foreach ($live as $id => $endpoint) {
            if (
                !isset($known[$id]) ||
                $known[$id]['route'] !== $endpoint['route'] ||
                $known[$id]['methods'] !== $endpoint['methods']
            ) {
                $discovered[$id] = $endpoint;
            }
        }
        if ($discovered !== $known) {
            update_site_option(self::DISCOVERED_OPTION, $discovered);
        }
    }

    /**
     * Return endpoints available to the network settings screen.
     *
     * @return array<string, array{label: string, route: string, methods: array, description: string}>
     */
    public static function getForSettings()
    {
        $known = self::normalize(get_site_option(self::DISCOVERED_OPTION, []));

        return array_replace($known, self::getLive());
    }

    /**
     * Return endpoints declared by plugins active for the current site.
     *
     * @return array<string, array{label: string, route: string, methods: array, description: string}>
     */
    public static function getLive()
    {
        /**
         * Filters public REST endpoints offered for explicit administrator approval.
         *
         * Registration does not grant access. Each definition must use a stable,
         * sanitize_key-compatible ID and provide a literal route plus HTTP methods.
         * The ID prefix "network-route-" is reserved for network allowlist entries.
         *
         * @param array $endpoints {
         *     Endpoint definitions keyed by stable endpoint ID.
         *
         *     @type string   $label       Human-readable endpoint name.
         *     @type string   $route       Exact REST route beginning with a slash.
         *     @type string[] $methods     Allowed HTTP methods.
         *     @type string   $description Optional explanation of exposed data.
         * }
         */
        $endpoints = apply_filters('rrze_rest_api_public_endpoints', []);

        return self::normalize($endpoints);
    }

    /**
     * Validate selected IDs against endpoints shown in network settings.
     *
     * @param mixed $input Submitted endpoint IDs.
     * @return string[] Valid endpoint IDs.
     */
    public static function sanitizeSelection($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $selected = array_map('sanitize_key', $input);

        return array_values(array_unique(array_intersect(
            $selected,
            array_keys(self::getForSettings())
        )));
    }

    /**
     * Check a request against selected endpoints active on the current site.
     *
     * @param mixed            $selected Selected endpoint IDs.
     * @param \WP_REST_Request $request  Current REST request.
     * @return bool Whether the request exactly matches an approved endpoint.
     */
    public static function requestMatches($selected, $request)
    {
        return self::matches(self::getLive(), $selected, $request);
    }

    /**
     * Identify registered routes independently of their approval or HTTP method.
     */
    public static function isRegisteredRoute($route)
    {
        $route = '/' . trim((string) $route, '/');
        foreach (self::getLive() as $endpoint) {
            // WordPress also matches registered REST route paths case-insensitively.
            if (strcasecmp($route, $endpoint['route']) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Offer only network-approved, read-only exceptions to private sites.
     *
     * Plugin declarations must be live on this site. Network namespace/route
     * entries use stable IDs tied to the path, never to their position in the list.
     *
     * @param object $options Network REST options.
     * @return array Endpoint definitions keyed by selection ID.
     */
    public static function getApproved($options)
    {
        $selected = is_array($options->restpublic ?? null) ? $options->restpublic : [];
        $approved = [];
        foreach (self::getLive() as $id => $endpoint) {
            if (!in_array($id, $selected, true)) {
                continue;
            }
            $endpoint['methods'] = array_values(array_intersect($endpoint['methods'], ['GET', 'HEAD']));
            if ($endpoint['methods']) {
                $approved[$id] = $endpoint;
            }
        }

        $routes = is_array($options->restwhite ?? null) ? $options->restwhite : [];
        foreach ($routes as $route) {
            if (!is_string($route)) {
                continue;
            }
            $route = '/' . trim(trim($route), '/');
            if ($route === '/') {
                continue;
            }
            $approved[self::NETWORK_ROUTE_PREFIX . hash('sha256', $route)] = [
                /* translators: %s: REST namespace or route. */
                'label' => sprintf(__('%s (including subroutes)', 'rrze-settings'), $route),
                'route' => $route,
                'methods' => ['GET', 'HEAD'],
                'description' => __('Network allowlist entry. Permits public read access to this route and its subroutes. Registered public endpoints require their own approval.', 'rrze-settings'),
                'match' => 'prefix',
            ];
        }

        return $approved;
    }

    /**
     * Require both the network's current approval and the private site's selection.
     */
    public static function approvedRequestMatches($options, $selected, $request)
    {
        $endpoints = self::getApproved($options);
        if (self::isRegisteredRoute($request->get_route())) {
            // Broad local namespace exceptions must not bypass an endpoint's
            // individual network or site approval (including disallowed methods).
            $endpoints = array_filter($endpoints, static fn($endpoint) => ($endpoint['match'] ?? '') !== 'prefix');
        }

        return self::matches($endpoints, $selected, $request);
    }

    /**
     * Match selected definitions; namespace exceptions respect path boundaries.
     */
    private static function matches($endpoints, $selected, $request)
    {
        if (empty($selected) || !is_array($selected)) {
            return false;
        }

        $route = '/' . trim((string) $request->get_route(), '/');
        $method = strtoupper((string) $request->get_method());

        foreach ($selected as $id) {
            $endpoint = $endpoints[sanitize_key($id)] ?? null;
            if (!is_array($endpoint) || !in_array($method, $endpoint['methods'], true)) {
                continue;
            }

            if (($endpoint['match'] ?? '') === 'prefix') {
                if ($route === $endpoint['route'] || str_starts_with($route, $endpoint['route'] . '/')) {
                    return true;
                }
            } elseif (strcasecmp($route, $endpoint['route']) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize and validate endpoint definitions.
     *
     * @param mixed $endpoints Endpoint definitions.
     * @return array<string, array{label: string, route: string, methods: array, description: string}>
     */
    private static function normalize($endpoints)
    {
        if (!is_array($endpoints)) {
            return [];
        }

        $valid = [];
        foreach ($endpoints as $id => $endpoint) {
            $id = sanitize_key($id);
            if (
                $id === '' ||
                str_starts_with($id, self::NETWORK_ROUTE_PREFIX) ||
                !is_array($endpoint) ||
                empty($endpoint['route']) ||
                !is_scalar($endpoint['route']) ||
                empty($endpoint['methods']) ||
                !is_array($endpoint['methods'])
            ) {
                continue;
            }

            $methods = [];
            foreach ($endpoint['methods'] as $method) {
                if (is_scalar($method)) {
                    $method = strtoupper(sanitize_text_field((string) $method));
                    if ($method !== '') {
                        $methods[] = $method;
                    }
                }
            }
            $methods = array_values(array_unique($methods));
            if (empty($methods)) {
                continue;
            }

            $label = isset($endpoint['label']) && is_scalar($endpoint['label'])
                ? sanitize_text_field((string) $endpoint['label'])
                : $id;
            $description = isset($endpoint['description']) && is_scalar($endpoint['description'])
                ? sanitize_text_field((string) $endpoint['description'])
                : '';

            $valid[$id] = [
                'label' => $label,
                'route' => '/' . trim(sanitize_text_field((string) $endpoint['route']), '/'),
                'methods' => $methods,
                'description' => $description,
            ];
        }

        return $valid;
    }
}
