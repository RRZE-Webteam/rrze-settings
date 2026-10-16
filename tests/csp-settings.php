<?php

// phpcs:ignoreFile -- Isolated CLI checks using real WordPress text sanitization.

/**
 * Verify CSP source-list normalization and the header behavior explained by the UI.
 * Run: php tests/csp-settings.php [WordPress root]
 * No database access, HTTP requests or policy file writes.
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', rtrim($argv[1] ?? dirname(__DIR__, 4), '/') . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/formatting.php';
if (file_exists(ABSPATH . 'wp-includes/utf8.php')) {
    require ABSPATH . 'wp-includes/utf8.php';
}
require dirname(__DIR__) . '/includes/Config.php';
require dirname(__DIR__) . '/includes/Main.php';
require dirname(__DIR__) . '/includes/Settings.php';
require dirname(__DIR__) . '/includes/CSP/Settings.php';
require dirname(__DIR__) . '/includes/CSP/CSP.php';

$cspChecks = 0;

/** @return bool Treat fixture strings as UTF-8 without consulting site storage. */
function is_utf8_charset() { return true; }

/** @return bool Validate options in the network administration context. */
function is_network_admin() { return true; }

/**
 * Merge options without loading WordPress storage.
 * @param array|object $args Submitted values.
 * @param array $defaults Stored values.
 * @return array Merged values.
 */
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, (array) $args); }

/**
 * Create independent options with the real CSP defaults.
 * @return object Network options fixture.
 */
function cspOptions(): object
{
    return (object) ['csp' => (object) \RRZE\Settings\Config::get('options')['csp']];
}

/**
 * Assert a behavior independently of PHP assertion configuration.
 * @param bool $condition Result to verify.
 * @param string $message Failure explanation.
 * @return void
 */
function checkCsp(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['cspChecks']++;
}

/** Inject settings without running the storage-backed constructor. */
class CspRuntimeFixture extends \RRZE\Settings\CSP\CSP
{
    /**
     * Initialize the actual header builder with isolated options.
     * @param object $options Network options.
     */
    public function __construct(object $options)
    {
        $this->siteOptions = $options;
        $this->defaultOptions = cspOptions();
    }
}

$network = cspOptions();
$settings = new \RRZE\Settings\CSP\Settings('rrze_settings', cspOptions(), $network, cspOptions());
foreach (["\n", "\r\n", "\r", ' ', "\t"] as $separator) {
    $saved = $settings->optionsValidate(['enabled' => '1', 'img_src' => "'none'" . $separator . 'https://images.example.org']);
    checkCsp($saved->csp->img_src === "'none'", 'The none keyword must remain exclusive with separator ' . json_encode($separator));
    $header = (new CspRuntimeFixture($saved))->headers([])['Content-Security-Policy'];
    checkCsp(str_contains($header, "img-src 'none';") && !str_contains($header, 'images.example.org'), 'The emitted header must not retain a source alongside none.');
}
$saved = $settings->optionsValidate(['img_src' => addslashes("'self'\r\n'none'\r\nhttps://images.example.org")]);
checkCsp($saved->csp->img_src === "'none'", 'Slashed WordPress form input must also normalize none.');
$saved = $settings->optionsValidate(['script_src' => " 'self'\n\n https://scripts.example.org\t'unsafe-inline' "]);
checkCsp($saved->csp->script_src === "'self' https://scripts.example.org 'unsafe-inline'", 'Ordinary source lists must retain sources and keyword quoting.');
$saved = $settings->optionsValidate(['img_src' => "  \r\n\t "]);
checkCsp($saved->csp->img_src === '', 'Clearing a directive must not restore a default value.');
$saved = $settings->optionsValidate(['enabled' => 1]);
checkCsp($saved->csp->img_src === '', 'Omitted fields must retain their saved values.');
checkCsp($saved->csp->script_src === "'self' https://scripts.example.org 'unsafe-inline'", 'Saving activation alone must preserve the source lists.');

$runtime = new CspRuntimeFixture($saved);
$runtime->setEmbedHandlers([['allowed_domains' => ['embed.example.org']]]);
$header = $runtime->headers([])['Content-Security-Policy'];
checkCsp(!str_contains($header, 'img-src'), 'An empty directive must be omitted for browser fallback.');
checkCsp(str_contains($header, "default-src 'self' embed.example.org;"), 'Collected embed hosts must supplement a non-empty default rule.');
checkCsp(str_contains($header, "frame-src 'self';"), 'A specific frame rule must not merge the default source list.');
checkCsp(!isset($runtime->headers([])['Content-Security-Policy-Report-Only']), 'Activation must emit an enforcing policy, not a report-only policy.');

$saved->csp->default_src = "'none'";
$header = $runtime->headers([])['Content-Security-Policy'];
checkCsp(str_contains($header, "default-src 'none';") && !str_contains($header, 'embed.example.org'), 'A none default must suppress automatic embed additions.');
$saved->csp->default_src = '';
$header = $runtime->headers([])['Content-Security-Policy'];
checkCsp(!str_contains($header, 'default-src') && !str_contains($header, 'embed.example.org'), 'An empty default rule must not output an embed-only directive.');
$saved->csp->enabled = 0;
checkCsp($runtime->headers(['X-Fixture' => 'kept']) === ['X-Fixture' => 'kept'], 'Disabled CSP must not add a header.');
checkCsp($runtime->headers(['Content-Security-Policy' => 'other policy'])['Content-Security-Policy'] === 'other policy', 'Disabling this module must not remove an existing policy from another source.');

printf("Passed %d CSP checks (isolated; no database, HTTP or file writes).\n", $cspChecks);
