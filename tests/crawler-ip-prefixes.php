<?php

/**
 * Crawler IP prefix regression tests (no database or HTTP requests).
 * Run: php rrze-settings/tests/crawler-ip-prefixes.php
 * Uses the actual crawler normalizer, form validator and IP utilities.
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
spl_autoload_register(static function ($class) {
    $prefix = 'RRZE\\Settings\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__) . '/includes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

use RRZE\Settings\Crawlers\Defaults;
use RRZE\Settings\Crawlers\Settings;

$cases = [
    // Empty groups must not be removed to make malformed prefixes valid.
    '131..188.' => [],
    '.131.188.' => [],
    '131.188..' => [],
    '.' => [],
    '256.' => [],
    '131.188.1.2.' => [],
    '2001:::db8:' => [],
    ':2001:db8:' => [],
    '2001:db8::abcd:' => [],
    '::abcd:' => [],
    ':' => [],
    '2001:gggg:' => [],
    '12345:db8:' => [],
    '1:2:3:4:5:6:7:8:' => [],
    // Supported uncompressed prefixes, including explicit zero groups.
    '131.' => ['131.0.0.0/8'],
    '131.188.' => ['131.188.0.0/16'],
    '131.188.0.' => ['131.188.0.0/24'],
    '0.' => ['0.0.0.0/8'],
    '2001:' => ['2001::/16'],
    '2001:db8:' => ['2001:db8::/32'],
    '2001:db8:0:' => ['2001:db8:0::/48'],
    '1:2:3:4:5:6:7:' => ['1:2:3:4:5:6:7::/112'],
    // Complete addresses, CIDRs and wildcards keep their existing meaning.
    '192.0.2.1' => ['192.0.2.1/32'],
    '192.0.2.0/24' => ['192.0.2.0/24'],
    '192.0.2.*' => ['192.0.2.0/24'],
    '::' => ['::/128'],
    '::1' => ['::1/128'],
    '2001:db8::' => ['2001:db8::/128'],
    '2001:db8::abcd' => ['2001:db8::abcd/128'],
    '2001:db8::/32' => ['2001:db8::/32'],
    '2001:db8::*' => ['2001:db8::0/112'],
];

$checks = 0;
foreach ($cases as $input => $expected) {
    $actual = Defaults::sanitizeIpAddresses([$input]);
    if ($actual !== $expected) {
        throw new RuntimeException("$input: expected " . json_encode($expected) . ', got ' . json_encode($actual));
    }
    $checks++;
}

// Invalid input must reach the form's error list instead of becoming a saved range.
$settings = (new ReflectionClass(Settings::class))->newInstanceWithoutConstructor();
$validate = new ReflectionMethod(Settings::class, 'validateIpAddresses');
$result = $validate->invoke($settings, "131..188.\n2001:db8::abcd:\n131.188.\n2001:db8::/32");
$expected = [
    'valid' => ['131.188.0.0/16', '2001:db8::/32'],
    'invalid' => ['131..188.', '2001:db8::abcd:'],
];
if ($result !== $expected) {
    throw new RuntimeException('Form validation did not separate valid and invalid prefixes: ' . json_encode($result));
}
$checks++;

echo "Passed $checks crawler IP prefix checks (no database or HTTP).\n";
