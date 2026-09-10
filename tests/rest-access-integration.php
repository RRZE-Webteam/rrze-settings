<?php

/**
 * Extend the isolated policy suite with actual Access, Permissions and IP classes.
 * Run: php rrze-settings/tests/rest-access-integration.php [WordPress root]
 * WordPress storage and identity APIs are stubbed; no HTTP or database access.
 */
require __DIR__ . '/public-rest-policy.php';
spl_autoload_register(static function ($class) {
    foreach (['RRZE\\Settings\\' => 'rrze-settings', 'RRZE\\PrivateSite\\' => 'rrze-private-site'] as $prefix => $plugin) {
        if (str_starts_with($class, $prefix)) {
            require dirname(__DIR__, 2) . '/' . $plugin . '/includes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        }
    }
});
foreach (['PASSWORD', 'ALLOW_IPADDR', 'SSO', 'SITEIMPROVE'] as $name) {
    define('RRZE\\PrivateSite\\PRIVATE_SITE_' . $name . '_OPTION', 'private_site_' . strtolower($name));
}
function is_super_admin() { return false; }
function is_feed() { return false; }
function permissions() { return new RRZE\PrivateSite\Permissions(); }
function wp_set_current_user($id) { $GLOBALS['loggedIn'] = (bool) $id; }
function get_current_blog_id() { return 1; }
function get_current_user_id() { return 1; }
function get_blogs_of_user($id) { return [1 => (object) ['userblog_id' => 1]]; }

$initialChecks = $checks;
remove_all_filters('rest_pre_dispatch');
remove_all_filters('rrze_rest_api_approved_public_endpoints');
remove_all_filters('rrze_rest_api_public_request_allowed');
$options = (object) ['rest' => (object) [
    'disabled' => 1, 'restwhite' => ['/wp/v2/pages'],
    'restpublic' => ['rrze-faubox-download'], 'restnetwork' => [],
]];
$network = new RRZE\Settings\RestAPI\API($options);
$network->loaded();
$private = (new ReflectionClass(RRZE\PrivateSite\Main::class))->newInstanceWithoutConstructor();
add_filter('rest_pre_dispatch', [$private, 'restAccess'], 1, 3);
$stored['private_site_public_rest_endpoints'] = [];
$loggedIn = false;
unset($_SERVER['HTTP_X_FORWARDED_FOR']);

foreach ([['192.0.2.0/24', '192.0.2.23'], ['192.0.2.23', '192.0.2.23'], ['2001:db8::/65', '2001:db8::23']] as [$range, $client]) {
    $stored['private_site_allow_ipaddr'] = [$range];
    $_SERVER['REMOTE_ADDR'] = $client;
    expect(dispatch(request('/wp/v2/pages')) === null, 'Authorized private-site IP cannot reach allowed network route');
    expect(dispatch(request('/wp/v2/posts'))?->get_status() === 403, 'Private-site IP bypassed network restriction');
    expect(dispatch(request()) === null, 'General site access incorrectly requires public endpoint checkbox');
}
$_SERVER['REMOTE_ADDR'] = '198.51.100.23';
$stored['private_site_allow_ipaddr'] = [];
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Empty local IP list permits access');
$options->rest->restnetwork = ['198.51.100.0/24'];
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Network IP alone bypasses private site');
$options->rest->disabled = 0;
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Open network API bypasses private site');
$options->rest->disabled = 1;
$options->rest->restnetwork = [];

// Regression: a single visitor IP used to become a trusted /0 proxy, letting
// an outside client impersonate it through X-Forwarded-For.
$stored['private_site_allow_ipaddr'] = ['192.0.2.23'];
$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.23';
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Forged header bypasses Private Site');
expect(permissions()->checkRemoteIpAddressInRanges(['192.0.2.23'], true) === false, 'Legacy checkProxies flag trusts visitor range');
expect(permissions()->getRemoteIpAddress(['192.0.2.23']) === '198.51.100.23', 'Legacy allowedIpAddresses argument trusts visitor range');
remove_filter('rest_pre_dispatch', [$private, 'restAccess'], 1);
$options->rest->restwhite = [];
$options->rest->restnetwork = ['127.0.0.1'];
$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Forged header bypasses network IP restriction');
add_filter('rest_pre_dispatch', [$private, 'restAccess'], 1, 3);

// Both plugins resolve the same real visitor through a configured proxy chain.
$proxyFilter = static fn() => ['203.0.113.0/24'];
add_filter('rrze_trusted_proxies', $proxyFilter);
$_SERVER['REMOTE_ADDR'] = '203.0.113.2';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.23, 203.0.113.1';
$options->rest->restnetwork = ['192.0.2.0/24'];
expect(dispatch(request('/wp/v2/pages')) === null, 'Trusted proxy client cannot access permitted route');
$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.23, 198.51.100.23, 203.0.113.1';
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Spoofed prefix bypasses access through trusted proxy');
$stored['private_site_allow_ipaddr'] = ['203.0.113.2'];
$options->rest->restnetwork = ['203.0.113.2'];
unset($_SERVER['HTTP_X_FORWARDED_FOR']);
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Missing client identity falls back to authorized transport IP');
remove_filter('rrze_trusted_proxies', $proxyFilter);

// Cookie-only download links have no REST nonce. Respect WordPress's decision
// to treat them as anonymous; only the explicitly approved public route passes.
require ABSPATH . 'wp-includes/rest-api.php';
$stored['private_site_allow_ipaddr'] = [];
$options->rest->restnetwork = [];
$loggedIn = true;
$wp_rest_auth_cookie = true;
$_REQUEST = [];
unset($_SERVER['HTTP_X_WP_NONCE']);
expect(dispatch(request('/wp/v2/posts')) === null, 'Late authenticated member access blocked');
rest_cookie_check_errors(null);
expect(permissions()->isUserMember() === false, 'Nonce-less cookie request retained member privileges');
expect(dispatch(request())?->get_status() === 403, 'Anonymous download bypasses local approval');
$stored['private_site_public_rest_endpoints'] = ['rrze-faubox-download'];
expect(dispatch(request()) === null, 'Public signed-download route remains blocked without nonce');
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'Public download exception exposes site content');
$options->rest->restpublic = [];
expect(dispatch(request())?->get_status() === 403, 'Revoked network approval does not stop download exception');

// An AC decision for an HTML page must not become a blanket REST exception.
// AC itself is neither loaded nor modified by this test.
$acFlag = new ReflectionProperty(RRZE\PrivateSite\Main::class, 'rrzeACPluginAccessAllowed');
$acFlag->setValue($private, true);
$stored['private_site_public_rest_endpoints'] = [];
$options->rest->restwhite = ['/wp/v2/pages'];
expect(dispatch(request('/wp/v2/pages'))?->get_status() === 403, 'AC HTML decision bypasses private REST policy');

echo 'Passed ' . ($checks - $initialChecks) . " additional access-chain checks (isolated; no HTTP or database).\n";
