<?php

/**
 * Isolated cross-plugin policy regression tests (no database or HTTP requests).
 * Run: php rrze-settings/tests/public-rest-policy.php [WordPress root]
 * Requires rrze-private-site next to rrze-settings. Uses actual WordPress hooks,
 * request/response classes and plugin callbacks; stubs storage, login and UI APIs.
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', rtrim($argv[1] ?? dirname(__DIR__, 4), '/') . '/');
define('RRZE\PrivateSite\PRIVATE_SITE_PUBLIC_REST_ENDPOINTS_OPTION', 'private_site_public_rest_endpoints');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-http-response.php';
require ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
require ABSPATH . 'wp-includes/rest-api/class-wp-rest-request.php';
require dirname(__DIR__) . '/includes/Settings.php';
require dirname(__DIR__) . '/includes/Main.php';
require dirname(__DIR__) . '/includes/RestAPI/PublicEndpoints.php';
require dirname(__DIR__) . '/includes/RestAPI/API.php';
require dirname(__DIR__) . '/includes/RestAPI/RestAPI.php';
require dirname(__DIR__) . '/includes/RestAPI/Settings.php';
require dirname(__DIR__, 2) . '/rrze-private-site/includes/Main.php';
require dirname(__DIR__, 2) . '/rrze-private-site/includes/Settings.php';

use RRZE\Settings\RestAPI\PublicEndpoints;

$stored = [];
$loggedIn = false;
$fields = [];
$checks = 0;
function get_option($key, $default = false) { return $GLOBALS['stored'][$key] ?? $default; }
function get_site_option($key, $default = false) { return get_option($key, $default); }
function update_site_option($key, $value) { $GLOBALS['stored'][$key] = $value; }
function is_user_logged_in() { return $GLOBALS['loggedIn']; }
function is_network_admin() { return true; }
function absint($value) { return abs((int) $value); }
function get_home_url() { return 'https://example.test'; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function sanitize_key($key) { return is_scalar($key) ? preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)) : ''; }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function __($text, $domain = '') { return $text; }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return esc_html($text); }
function esc_html_e($text, $domain = '') { echo esc_html($text); }
function checked($checked, $current = true) { if ($checked == $current) { echo 'checked="checked"'; } }
function wp_parse_args($args, $defaults) { return array_merge($defaults, $args); }
function add_settings_section(...$args) {}
function add_settings_field($id, ...$args) { $GLOBALS['fields'][] = $id; }

class NetworkPolicyFixture extends RRZE\Settings\RestAPI\API
{
    public bool $ipAllowed = false;
    public function __construct($options) { $this->siteOptions = $options; }
    protected function isRestnetwork() { return $this->ipAllowed; }
}
class PrivateSiteFixture extends RRZE\PrivateSite\Main
{
    public bool $member = false;
    public function __construct() {}
    protected function tryAccess($rest = false) { return $this->member; }
}
class RestBootstrapFixture extends RRZE\Settings\RestAPI\RestAPI
{
    public function __construct($options)
    {
        $this->siteOptions = $options;
        $this->optionName = 'rrze_settings';
        $this->options = new stdClass();
        $this->defaultOptions = new stdClass();
    }
}
function expect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}
function request($route = '/rrze-faubox/v1/download', $method = 'GET')
{
    return new WP_REST_Request($method, $route);
}
function dispatch($request, $response = null)
{
    return apply_filters('rest_pre_dispatch', $response, null, $request);
}

$declarations = [
    'rrze-faubox-download' => [
        'label' => 'FAUbox signed downloads',
        'route' => '/rrze-faubox/v1/download',
        'methods' => ['GET', 'HEAD'],
        'description' => 'Requires a valid signature.',
    ],
];
add_filter('rrze_rest_api_public_endpoints', static function () use (&$declarations) { return $declarations; });
$options = (object) ['rest' => (object) ['disabled' => 1, 'restwhite' => [], 'restpublic' => []]];
$network = new NetworkPolicyFixture($options);
$network->loaded();
$private = new PrivateSiteFixture();
add_filter('rest_pre_dispatch', [$private, 'restAccess'], 1, 3);

// Neither a disabled global restriction nor broad IP/namespace exceptions can
// override either of the explicit approvals for a registered endpoint.
foreach ([0, 1] as $restricted) {
    foreach ([false, true] as $ipAllowed) {
        foreach ([false, true] as $networkApproved) {
            foreach ([false, true] as $siteApproved) {
                $options->rest->disabled = $restricted;
                $network->ipAllowed = $ipAllowed;
                $options->rest->restwhite = ['/rrze-faubox'];
                $options->rest->restpublic = $networkApproved ? ['rrze-faubox-download'] : [];
                $stored['private_site_public_rest_endpoints'] = $siteApproved ? ['rrze-faubox-download'] : [];
                foreach (['GET', 'HEAD', 'POST', 'OPTIONS'] as $method) {
                    $response = dispatch(request('/rrze-faubox/v1/download', $method));
                    $allowed = $networkApproved && $siteApproved && in_array($method, ['GET', 'HEAD'], true);
                    expect($allowed ? $response === null : $response?->get_status() === 403,
                        "Approval matrix failed: $restricted/$ipAllowed/$networkApproved/$siteApproved/$method");
                }
            }
        }
    }
}

$network->ipAllowed = false;
$options->rest->disabled = 1;
$options->rest->restwhite = ['/wp/v2/pages', '/rrze-faubox'];
$options->rest->restpublic = ['rrze-faubox-download'];
$approved = apply_filters('rrze_rest_api_approved_public_endpoints', []);
$pagesId = 'network-route-' . hash('sha256', '/wp/v2/pages');
$fauboxPrefixId = 'network-route-' . hash('sha256', '/rrze-faubox');
expect(isset($approved[$pagesId], $approved['rrze-faubox-download']), 'Approved choices missing');
$stored['private_site_public_rest_endpoints'] = [$pagesId, $fauboxPrefixId];
expect(dispatch(request('/wp/v2/pages')) === null, 'Selected page collection blocked');
expect(dispatch(request('/wp/v2/pages/42', 'HEAD')) === null, 'Selected page subroute blocked');
expect(dispatch(request('/wp/v2/pages-other'))?->get_status() === 403, 'Prefix boundary bypass');
expect(dispatch(request('/wp/v2/posts'))?->get_status() === 403, 'Unselected route allowed');
expect(dispatch(request('/wp/v2/pages', 'POST'))?->get_status() === 403, 'Namespace permitted write');
expect(dispatch(request())?->get_status() === 403, 'Namespace bypassed local endpoint approval');
expect(dispatch(request('/rrze-faubox/v1/Download'))?->get_status() === 403, 'Case variant bypassed local approval');
$options->rest->restpublic = [];
expect(dispatch(request('/rrze-faubox/v1/Download'))?->get_status() === 403, 'Case variant bypassed network approval');
expect(!isset(apply_filters('rrze_rest_api_approved_public_endpoints', [])['rrze-faubox-download']), 'Unapproved endpoint still offered');
$options->rest->restwhite = [];
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Removed network route remained accessible');

// Ordinary authenticated/membership access and other plugins' denial responses
// retain their existing behavior. A public exception only returns null to let
// WordPress continue to route matching and endpoint permission callbacks.
$options->rest->restpublic = ['rrze-faubox-download'];
$stored['private_site_public_rest_endpoints'] = ['rrze-faubox-download'];
expect(dispatch(request()) === null, 'Explicit approvals failed');
expect(dispatch(request('/rrze-faubox/v1/download/extra'))?->get_status() === 403, 'Exact declaration permitted subroute');
$denied = new WP_REST_Response('Another plugin denied access', 401);
expect(dispatch(request(), $denied) === $denied, 'Approval replaced prior denial');
$stored['private_site_public_rest_endpoints'] = [];
$private->member = true;
$loggedIn = true;
expect(dispatch(request('/wp/v2/posts')) === null, 'Member access regressed');
$private->member = false;
expect(dispatch(request('/wp/v2/posts'))?->get_status() === 403, 'Logged-in nonmember bypassed private site');
$loggedIn = false;
remove_filter('rest_pre_dispatch', [$private, 'restAccess'], 1);
$options->rest->disabled = 0;
$options->rest->restpublic = [];
expect(dispatch(request('/wp/v2/posts')) === null, 'Public site / open API regressed');
expect(dispatch(request())?->get_status() === 403, 'Public site bypassed endpoint network veto');
$options->rest->disabled = 1;
$options->rest->restwhite = ['/wp/v2/pages'];
expect(dispatch(request('/wp/v2/pages')) === null, 'Legacy namespace exception regressed');
expect(dispatch(request('/wp/v2/pages-extra'))?->get_status() === 403, 'Legacy namespace boundary failed');
$network->ipAllowed = true;
expect(dispatch(request('/wp/v2/posts')) === null, 'Legacy IP exception regressed');
expect(dispatch(request())?->get_status() === 403, 'IP bypassed registered endpoint veto');
add_filter('rest_pre_dispatch', [$private, 'restAccess'], 1, 3);

// Discovered declarations are offered in network admin, but cached declarations
// alone may never establish a live plugin endpoint exception on another site.
PublicEndpoints::discover();
$declarations = [];
expect(isset(PublicEndpoints::getForSettings()['rrze-faubox-download']), 'Discovery lost network metadata');
expect(!PublicEndpoints::requestMatches(['rrze-faubox-download'], request()), 'Cached declaration granted access');
expect(!isset(PublicEndpoints::getApproved($options->rest)['rrze-faubox-download']), 'Inactive provider offered locally');
$declarations = $stored['rrze_rest_api_public_endpoints'];

// Settings remain visible and saveable with the global REST restriction off.
$options->rest->disabled = 0;
$options->rest->restpublic = ['rrze-faubox-download'];
$settings = new RRZE\Settings\RestAPI\Settings('rrze_settings', new stdClass(), $options, new stdClass());
$settings->networkAdminPage();
expect(in_array('restpublic', $fields, true) && in_array('restwhite', $fields, true), 'Approval controls hidden when API open');
$saved = $settings->optionsValidate(['restpublic' => ['rrze-faubox-download', 'forged', []]]);
expect($saved->rest->restpublic === ['rrze-faubox-download'], 'Network selection validation failed');
$saved = $settings->optionsValidate([]);
expect($saved->rest->restpublic === [], 'Unchecking all network endpoints failed');
$options->rest->restpublic = ['rrze-faubox-download'];
$options->rest->restwhite = ['/wp/v2/pages'];
$localSettings = (new ReflectionClass(RRZE\PrivateSite\Settings::class))->newInstanceWithoutConstructor();
expect($localSettings->validatePublicRestEndpoints(['rrze-faubox-download', $pagesId, 'forged', []]) === ['rrze-faubox-download', $pagesId], 'Local checkbox validation failed');
expect($localSettings->validatePublicRestEndpoints(null) === [], 'Unchecking all local endpoints failed');
$options->rest->restpublic = [];
expect($localSettings->validatePublicRestEndpoints(['rrze-faubox-download']) === [], 'Local form accepted unapproved endpoint');
ob_start();
$localSettings->publicRestEndpointsField();
$html = ob_get_clean();
expect(str_contains($html, $pagesId) && !str_contains($html, 'value="rrze-faubox-download"'), 'Local UI offered wrong choices');

remove_filter('rrze_rest_api_approved_public_endpoints', [$network, 'approvedPublicEndpoints']);
remove_filter('rrze_rest_api_public_request_allowed', [$network, 'publicRequestAllowed']);
$stored['private_site_public_rest_endpoints'] = [$pagesId];
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Missing network policy provider failed open');
expect($localSettings->validatePublicRestEndpoints([$pagesId]) === [], 'Missing network policy provider offered choices');

remove_all_filters('rest_pre_dispatch');
$options->rest->disabled = 0;
(new RestBootstrapFixture($options))->loaded();
expect(has_filter('rest_pre_dispatch') && has_filter('rrze_rest_api_public_request_allowed'), 'Bootstrap skipped network policy when REST restriction off');
expect(dispatch(request())?->get_status() === 403, 'Bootstrapped network veto missing');
expect(isset(apply_filters('rrze_rest_api_approved_public_endpoints', [])[$pagesId]), 'Bootstrapped checkbox catalog missing');

echo "Passed $checks policy checks (isolated; no database, HTTP or download streaming).\n";
