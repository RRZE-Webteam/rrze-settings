<?php

// phpcs:ignoreFile -- Isolated menu fixtures without WordPress storage.

/**
 * Verify that grouping preserves extension links, capabilities and menu metadata.
 * Run: php tests/admin-navigation.php
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', __DIR__ . '/');
require dirname(__DIR__) . '/includes/AdminNavigation.php';

/**
 * Keep labels readable without loading WordPress translations.
 *
 * @param string $text Original label.
 * @param string $domain Text domain.
 * @return string Untranslated label.
 */
function __($text, $domain = 'default') { return $text; }

/**
 * Assert a navigation invariant independently of PHP assertion configuration.
 *
 * @param bool $condition Result to verify.
 * @param string $message Failure description.
 * @return void
 */
function checkNavigation(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['navigationChecks']++;
}

$navigationChecks = 0;
$original = [
    20 => ['External tool', 'custom_extension_capability', 'external.php?mode=full', 'External title', 'extension-highlight'],
    40 => ['Mail', 'manage_options', 'rrze-settings-mail', 'Mail title'],
    70 => ['General', 'manage_options', 'rrze-settings', 'General title', 'existing-class'],
    80 => ['Legal', 'custom_legal_capability', 'legal', 'Legal title', 'legal-class'],
    90 => ['API', 'custom_api_capability', 'rrze-settings-rest-api', 'API title'],
];
$otherMenu = [['Unrelated', 'read', 'unrelated-page']];
$submenu = ['rrze-settings' => $original, 'other-plugin' => $otherMenu];
$navigation = new \RRZE\Settings\AdminNavigation();
$navigation->groupSubmenu();
$grouped = $submenu['rrze-settings'];
checkNavigation(count($grouped) === count($original), 'Grouping must not add placeholder links or remove registered pages.');
checkNavigation($grouped[0][2] === 'rrze-settings', 'The parent menu must still lead to General.');
checkNavigation($submenu['other-plugin'] === $otherMenu, 'Other plugin menus must remain untouched.');

foreach ($original as $item) {
    $matches = array_values(array_filter($grouped, static fn($candidate) => $candidate[2] === $item[2]));
    checkNavigation(count($matches) === 1, 'Every original link must remain available exactly once.');
    checkNavigation(array_slice($matches[0], 0, 4) === array_slice($item, 0, 4), 'Labels, capabilities, URLs and titles must be preserved.');
    checkNavigation(empty($item[4]) || str_contains($matches[0][4], $item[4]), 'Existing extension classes must survive grouping.');
}

checkNavigation(end($grouped)[2] === 'external.php?mode=full', 'Unknown extension links must remain accessible after the known groups.');
$navigation->groupSubmenu();
checkNavigation($submenu['rrze-settings'] === $grouped, 'Repeated grouping must not duplicate classes or change the order.');
$submenu = ['other-plugin' => $otherMenu];
$navigation->groupSubmenu();
checkNavigation($submenu === ['other-plugin' => $otherMenu], 'An absent settings menu must not be created by grouping.');

printf("Passed %d navigation checks (no database or browser state changes).\n", $navigationChecks);
