<?php

// phpcs:ignoreFile -- Isolated rendering checks, with no database or HTTP access.

/**
 * Verify concealed credentials preserve their form payload and escape hostile text.
 * Run: php tests/plugin-secret-fields.php
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/kses.php';
if (file_exists(ABSPATH . 'wp-includes/utf8.php')) {
    require ABSPATH . 'wp-includes/utf8.php';
}
require dirname(__DIR__) . '/includes/Main.php';
require dirname(__DIR__) . '/includes/Settings.php';
require dirname(__DIR__) . '/includes/Plugins/Settings.php';

/** @return bool Treat fixture strings as UTF-8 without accessing site storage. */
function is_utf8_charset() { return true; }

/**
 * Supply the only option needed by the real escaping functions.
 * @param string $name Requested option.
 * @return string UTF-8 fixture encoding.
 */
function get_option($name) { return 'UTF-8'; }

/**
 * Keep fixture character encodings independent of WordPress bootstrap.
 * @param string $charset Requested charset.
 * @return string Normalized UTF-8 charset.
 */
function _canonical_charset($charset) { return 'UTF-8'; }

/**
 * Return untranslated labels for deterministic rendering checks.
 * @param string $text Source text.
 * @param string $domain Text domain.
 * @return string Untranslated text.
 */
function __($text, $domain = 'default') { return $text; }

/**
 * Escape untranslated field descriptions.
 * @param string $text Source text.
 * @param string $domain Text domain.
 * @return string Escaped text.
 */
function esc_html__($text, $domain = 'default') { return esc_html($text); }

/** @return string[] Protocols needed by the existing Directions help link. */
function wp_allowed_protocols() { return ['http', 'https']; }

/**
 * Assert observable rendering behavior independently of PHP assertion settings.
 * @param bool $condition Result to check.
 * @param string $message Failure explanation.
 * @return void
 */
function checkSecret(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['secretChecks']++;
}

/**
 * Render an actual field into a DOM without exposing fixture content in output.
 * @param object $settings Actual settings controller with isolated option values.
 * @param string $method Public renderer to invoke.
 * @return DOMXPath Queryable rendered document.
 */
function renderSecretFixture(object $settings, string $method): DOMXPath
{
    ob_start();
    $settings->$method();
    $html = ob_get_clean();
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    return new DOMXPath($dom);
}

$secretChecks = 0;
$fields = [
    'dipAPIField' => 'dip_apiKey',
    'dipEduApiKeyField' => 'dip_edu_api_key',
    'faudirApiKeyField' => 'faudir_public_apiKey',
    'biteApiKeyField' => 'bite_api_key',
    'rrzeDirectionsOpenRouteServiceApiKeyField' => 'rrze_directions_openrouteservice_api_key',
    'webtPasswordField' => 'rrze_webt_password',
];
$fixture = 'test-"\'><svg onload="alert(1)">&-ä';
$options = (object) ['plugins' => (object) array_fill_keys(array_values($fields), $fixture)];
$settings = new \RRZE\Settings\Plugins\Settings('rrze_settings', $options, $options, $options);
foreach ($fields as $method => $key) {
    $xpath = renderSecretFixture($settings, $method);
    $input = $xpath->query('//input')->item(0);
    $button = $xpath->query('//button')->item(0);
    checkSecret($input->getAttribute('type') === 'password', "$key must be masked in the initial server markup.");
    checkSecret($input->getAttribute('name') === "rrze_settings[$key]", "$key must keep its original submitted name.");
    checkSecret($input->getAttribute('value') === $fixture, "$key must retain every character of its form value.");
    checkSecret(!$input->hasAttribute('disabled'), "$key must still submit while masked.");
    checkSecret($xpath->query('//svg')->length === 0, "$key must escape markup-like values.");
    checkSecret($button->getAttribute('type') === 'button' && !$button->hasAttribute('name'), 'Visibility controls must not submit settings.');
    checkSecret($button->getAttribute('aria-controls') === $input->getAttribute('id'), 'Each visibility control must identify its own field.');
    checkSecret($button->hasAttribute('hidden'), 'A nonfunctional eye button must remain hidden without JavaScript.');
    checkSecret(!str_contains($button->getAttribute('aria-label'), $fixture), 'Labels must not include credentials.');
}

$lines = ['Engine', 'example-cx', $fixture, '</textarea><script>alert(1)</script>'];
$options->plugins->rrze_search_engine_keys = $lines;
$xpath = renderSecretFixture($settings, 'rrzeSearchEngineKeysField');
$details = $xpath->query('//details')->item(0);
$textarea = $xpath->query('//textarea')->item(0);
checkSecret(!$details->hasAttribute('open'), 'Multiline credentials must start inside a closed native disclosure.');
checkSecret($textarea->parentNode === $details, 'Multiline credentials must be concealed even before scripts or styles load.');
checkSecret($textarea->textContent === implode(PHP_EOL, $lines), 'Masking must preserve multiline content and line boundaries.');
checkSecret($textarea->getAttribute('name') === 'rrze_settings[rrze_search_engine_keys]' && !$textarea->hasAttribute('disabled'), 'Closed disclosures must submit the original textarea.');
checkSecret($xpath->query('//script|//svg')->length === 0, 'Multiline content must not escape its textarea.');
checkSecret(!str_contains($xpath->query('//summary')->item(0)->textContent, $fixture), 'Closed previews must not disclose the credential.');

echo "Passed $secretChecks secret-field checks without database writes.\n";
