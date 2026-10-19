<?php

/**
 * Exercise the real ordering, discovery and POST validation without a database.
 * Run: php tests/admin-menu-order.php [WordPress root]
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', rtrim($argv[1] ?? dirname(__DIR__, 4), '/') . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/compat.php';
if (is_file(ABSPATH . 'wp-includes/utf8.php')) {
    require ABSPATH . 'wp-includes/utf8.php';
}
foreach (['Order', 'Catalog', 'Navigation', 'Settings'] as $class) {
    require dirname(__DIR__) . '/includes/AdminMenu/' . $class . '.php';
}

use RRZE\Settings\AdminMenu\Catalog;
use RRZE\Settings\AdminMenu\Navigation;
use RRZE\Settings\AdminMenu\Order;
use RRZE\Settings\AdminMenu\Settings;

$checks = $writes = 0;
$networkId = 1;
$networkAdmin = $userAdmin = $canManageNetwork = false;
$storage = [];

/** @return bool Keep text sanitization independent of the options database. */
function is_utf8_charset() { return true; }
/** @param string $text Message. @param string $domain Domain. @return string Plain message. */
function __($text, $domain = '') { return $text; }
/** @param string $text Message. @param string $domain Domain. @return string Plain message. */
function esc_html__($text, $domain = '') { return $text; }
/** @return bool Website/network context fixture. */
function is_network_admin() { return $GLOBALS['networkAdmin']; }
/** @return bool Personal admin context fixture. */
function is_user_admin() { return $GLOBALS['userAdmin']; }
/** @return bool Test installation supports networks. */
function is_multisite() { return true; }
/** @param string $cap Capability. @return bool Current fixture capability. */
function current_user_can($cap) { return $cap === 'manage_network_options' && $GLOBALS['canManageNetwork']; }
/** @param string $name Option name. @param mixed $default Fallback. @return mixed Network-scoped value. */
function get_site_option($name, $default = false) { return $GLOBALS['storage'][$GLOBALS['networkId']][$name] ?? $default; }
/** @param string $name Option name. @param mixed $value New value. @return bool Whether changed. */
function update_site_option($name, $value)
{
    $changed = get_site_option($name, null) !== $value;
    $GLOBALS['storage'][$GLOBALS['networkId']][$name] = $value;
    $GLOBALS['writes'] += $changed ? 1 : 0;
    return $changed;
}
/** @param string $action Nonce action. @return void Reject an invalid token. */
function check_admin_referer($action)
{
    if ($action !== Settings::PAGE || ($_POST['_wpnonce'] ?? '') !== 'valid-fixture') {
        throw new DomainException('Invalid nonce');
    }
}
/** @param string $message Error message. @return never Stop a rejected request. */
function wp_die($message) { throw new DomainException($message); }
/** @param string $path Network path. @return string Local fixture URL. */
function network_admin_url($path) { return 'https://example.test/wp-admin/network/' . $path; }
/** @param array $args Query arguments. @param string $url URL. @return string Encoded URL. */
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
/** Marker for successful redirects, preventing the handler's final exit. */
class NavigationRedirect extends RuntimeException {}
/** @param string $url Target URL. @return never Capture successful handler completion. */
function wp_safe_redirect($url) { throw new NavigationRedirect($url); }

/** @param bool $condition Invariant. @param string $message Failure context. @return void */
function verifyNavigation(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
    $GLOBALS['checks']++;
}

/** @param array $input Submitted form. @return void Run the real POST handler to its redirect. */
function submitNavigation(array $input): void
{
    $_POST = $input;
    try {
        (new Settings())->save();
    } catch (NavigationRedirect $redirect) {
        verifyNavigation(str_contains($redirect->getMessage(), 'updated=1'), 'Successful save redirects back to its own screen.');
        return;
    }
    throw new RuntimeException('Save did not redirect.');
}

$defaults = Catalog::defaults();
$evidence = json_decode(file_get_contents(dirname(__DIR__) . '/docs/admin-settings-menu-inventory.json'), true);
verifyNavigation(count($defaults) === 51, 'All 51 reviewed catalogue entries are bundled.');
foreach ($evidence['entries'] as $entry) {
    verifyNavigation($defaults[strtolower($entry['plugin'])]['slugs'] === $entry['menu_slugs'], 'Bundled aliases match the reviewed inventory: ' . $entry['plugin']);
}
verifyNavigation(!isset($defaults['rrze-sso']), 'Network-only SSO is not in the website defaults.');
verifyNavigation(Navigation::preferences()['enabled'], 'Catalogue applies by default without writing preferences.');
verifyNavigation($writes === 0, 'Reading defaults never writes storage.');

$custom = Catalog::discoveryId('new-entry');
$catalog = $defaults + [$custom => ['label' => 'New', 'slugs' => ['new-entry']]];
$ids = Order::ids($defaults, $catalog, ['rrze-cache', 'fau-cris', 'rrze-cache', 'invalid', ['bad']]);
verifyNavigation(array_slice($ids, 0, 2) === ['rrze-cache', 'fau-cris'], 'Saved priorities precede new catalogue defaults.');
verifyNavigation(count($ids) === 51 && !in_array($custom, $ids, true), 'Unaccepted discoveries are not assigned a rank.');
$ids = Order::ids($defaults, $catalog, [$custom, 'rrze-cache']);
verifyNavigation($ids[0] === $custom, 'Accepted discovery can be moved ahead of bundled entries.');
$futureDefaults = ['new-default' => ['label' => 'New default', 'slugs' => ['new-entry']]] + $defaults;
$migrated = Order::ids($futureDefaults, $futureDefaults + $catalog, ['rrze-cache', $custom, 'fau-cris']);
verifyNavigation(array_slice($migrated, 0, 3) === ['rrze-cache', 'new-default', 'fau-cris'], 'A newly bundled discovery retains its previously saved position.');

$original = [
    3 => ['Unknown Z', 'custom_z', 'z', 'Z title', 'z-class'],
    7 => ['Formular DE', 'edit_posts', 'rrze-formular-einstellungen', 'Form title'],
    14 => ['Cache', 'manage_options', 'rrze-cache', 'Cache title', 'existing-class'],
    15 => ['Connectors', 'manage_options', 'options-connectors.php'],
    21 => ['General', 'manage_options', 'options-general.php'],
    38 => ['Reading', 'manage_options', 'options-reading.php'],
    40 => ['Unknown A', 'custom_a', 'a'],
    90 => ['Cache duplicate', 'custom_cap', 'rrze-cache', 'Duplicate title'],
    101 => ['Answers EN', 'manage_options', 'rrze-answers-settings'],
];
$ids = Order::ids($defaults, $defaults, ['rrze-answers', 'rrze-formular', 'rrze-cache']);
$ordered = Order::apply($original, $defaults, $ids);
verifyNavigation(array_column($ordered, 2) === ['options-general.php', 'options-connectors.php', 'options-reading.php', 'rrze-answers-settings', 'rrze-formular-einstellungen', 'rrze-cache', 'rrze-cache', 'z', 'a'], 'Core, translated aliases, duplicate registrations and unknowns have the intended stable order.');
foreach ($original as $item) {
    verifyNavigation(in_array($item, $ordered, true), 'Every original item retains its full metadata.');
}
verifyNavigation(count($ordered) === count($original), 'Catalogue must never create missing plugin links.');
verifyNavigation(Order::apply($ordered, $defaults, $ids) === $ordered, 'Ordering is idempotent.');
verifyNavigation(Order::apply([], $defaults, $ids) === [], 'An empty menu stays empty.');
verifyNavigation(Order::fromPositions(['a', 'b', 'c'], ['a' => '3', 'b' => '1', 'c' => '2', 'evil' => '0']) === ['b', 'c', 'a'], 'Positions reorder only permitted identifiers.');
verifyNavigation(Order::fromPositions(['a', 'b', 'c'], ['a' => [], 'b' => '-99', 'c' => 'NaN']) === ['a', 'b', 'c'], 'Malformed positions cannot corrupt the order.');
verifyNavigation(Order::fromPositions(['a', 'b', 'c'], ['a' => '2', 'b' => '2', 'c' => '2']) === ['a', 'b', 'c'], 'Equal positions are stable without JavaScript.');

$runtime = new Navigation();
$other = [['Elsewhere', 'read', 'elsewhere']];
$submenu = ['options-general.php' => $original, 'other' => $other];
$runtime->sortMenu();
verifyNavigation($submenu['other'] === $other, 'Other admin menus are untouched.');
verifyNavigation($writes === 0, 'Regular website users cannot write discovery data.');
update_site_option(Navigation::OPTION, ['enabled' => false, 'order' => ['rrze-cache']]);
$submenu['options-general.php'] = $original;
$runtime->sortMenu();
verifyNavigation($submenu['options-general.php'] === $original, 'Disabling preserves the original keys and plugin registration order.');
$networkAdmin = true;
update_site_option(Navigation::OPTION, ['enabled' => true, 'order' => []]);
$runtime->sortMenu();
verifyNavigation($submenu['options-general.php'] === $original, 'Network administration is excluded from runtime sorting.');
$networkAdmin = false;
$userAdmin = true;
$runtime->sortMenu();
verifyNavigation($submenu['options-general.php'] === $original, 'Personal administration is excluded.');
$userAdmin = false;
$canManageNetwork = true;
$discoveryMenu = [
    ['<strong>New menu</strong>', 'manage_options', 'new-entry'],
    ['Known alias', 'manage_options', 'rrze-answers-einstellungen'],
    ['External', 'read', 'https://example.org/?token=secret'],
    ['Token URL', 'read', 'admin.php?page=x&_wpnonce=secret'],
    ['Core', 'manage_options', 'options-general.php'],
];
Catalog::discover($discoveryMenu);
verifyNavigation(count(Catalog::discoveries()) === 1, 'Only an unknown, persistent local slug is discovered.');
verifyNavigation(Catalog::discoveries()[$custom]['label'] === 'New menu', 'Discovered labels are stored without markup.');
$before = $writes;
Catalog::discover($discoveryMenu);
verifyNavigation($writes === $before, 'Repeated visits do not write unchanged discoveries.');
$networkId = 2;
verifyNavigation(Catalog::discoveries() === [] && Navigation::preferences()['order'] === [], 'Another network has independent defaults and discoveries.');
$manyEntries = [];
for ($i = 0; $i < Catalog::MAX_DISCOVERIES + 5; $i++) {
    $manyEntries[] = ['Extra ' . $i, 'read', 'extra-' . $i];
}
Catalog::discover($manyEntries);
verifyNavigation(count(Catalog::discoveries()) === Catalog::MAX_DISCOVERIES, 'Discovery storage remains bounded.');
update_site_option(Catalog::DISCOVERIES_OPTION, ['invalid', ['slug' => [], 'label' => 'Bad'], ['slug' => 'valid', 'label' => '<b>Valid</b>']]);
verifyNavigation(count(Catalog::discoveries()) === 1, 'Malformed stored discovery records are ignored.');
update_site_option(Navigation::OPTION, 'malformed');
verifyNavigation(Navigation::preferences() === ['enabled' => true, 'order' => []], 'Malformed saved preferences fall back to the catalogue.');
$networkId = 1;

$networkAdmin = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
$before = $writes;
foreach (['capability', 'nonce', 'method', 'context'] as $rejection) {
    $canManageNetwork = $rejection !== 'capability';
    $networkAdmin = $rejection !== 'context';
    $_SERVER['REQUEST_METHOD'] = $rejection === 'method' ? 'GET' : 'POST';
    $_POST = ['_wpnonce' => $rejection === 'nonce' ? 'invalid' : 'valid-fixture'];
    try {
        (new Settings())->save();
        throw new RuntimeException('Rejected request unexpectedly continued.');
    } catch (DomainException $error) {
        verifyNavigation($writes === $before, 'Rejected ' . $rejection . ' must not save.');
    }
}
$networkAdmin = $canManageNetwork = true;
$_SERVER['REQUEST_METHOD'] = 'POST';
submitNavigation(['_wpnonce' => 'valid-fixture', 'enabled' => '1', 'positions' => ['rrze-cache' => '1', 'akismet' => '999'], 'include' => [$custom, 'evil', []]]);
$saved = Navigation::preferences();
verifyNavigation($saved['enabled'] && $saved['order'][0] === 'rrze-cache', 'Authorized form saves its order and activation.');
verifyNavigation(end($saved['order']) === $custom && !in_array('evil', $saved['order'], true), 'Only known selected discoveries are appended.');
submitNavigation(['_wpnonce' => 'valid-fixture', 'positions' => []]);
verifyNavigation(!Navigation::preferences()['enabled'] && Navigation::preferences()['order'] === $saved['order'], 'Disabling retains the saved priorities.');
submitNavigation(['_wpnonce' => 'valid-fixture', 'intent' => 'reset']);
$reset = Navigation::preferences();
verifyNavigation(!$reset['enabled'] && array_slice($reset['order'], 0, 51) === array_keys($defaults) && end($reset['order']) === $custom, 'Reset restores bundled order, retains accepted additions and preserves activation.');

printf("Passed %d admin-menu checks (isolated network storage, no database writes).\n", $checks);
