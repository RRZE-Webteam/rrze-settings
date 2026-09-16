<?php

/**
 * Crawler ID regression tests (no database or HTTP requests).
 * Run: php rrze-settings/tests/crawler-ids.php [WordPress root]
 * Uses actual WordPress sanitizers and crawler classes; stubs storage/context APIs.
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', rtrim($argv[1] ?? dirname(__DIR__, 4), '/') . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/compat.php';
require ABSPATH . 'wp-includes/utf8.php';
require ABSPATH . 'wp-includes/formatting.php';
require ABSPATH . 'wp-includes/kses.php';
add_filter('sanitize_title', 'sanitize_title_with_dashes', 10, 3);

function is_utf8_charset($charset = null) { return true; }
function get_option($name, $default = false) { return $name === 'blog_charset' ? 'UTF-8' : $default; }
function get_locale() { return 'de_DE'; }
function mbstring_binary_safe_encoding($reset = false) {}
function reset_mbstring_encoding() {}
function wp_allowed_protocols() { return ['http', 'https', 'mailto']; }
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, (array) $args); }
function is_network_admin() { return true; }
function __($text, $domain = '') { return $text; }

spl_autoload_register(static function ($class) {
    $prefix = 'RRZE\\Settings\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__) . '/includes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

use RRZE\Settings\Options;
use RRZE\Settings\Crawlers\Defaults;
use RRZE\Settings\Crawlers\Settings;

class CrawlerIdFixture extends Settings
{
    public function errors(): array { return $this->errors; }
}
function settingsFor($options): CrawlerIdFixture {
    return new CrawlerIdFixture(Options::OPTION_NAME, $options, $options, Options::getDefaultOptions());
}
function expect($condition, $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}

$checks = 0;
$title = '東京 Crawler';
$key = 'e69db1e4baac-crawler';
$input = ['key' => '', 'title' => $title, 'ip_addresses' => '192.0.2.1'];
$settings = settingsFor(Options::parseOptions([]));
$saved = $settings->optionsValidate(['crawler' => $input]);
expect($settings->errors() === [], 'Unicode crawler could not be added');
expect(isset($saved->crawlers->entries[$key]), 'Generated ID was not normalized before saving');
$reloaded = Options::parseOptions((array) $saved);
expect($reloaded->crawlers->entries === $saved->crawlers->entries, 'Crawler keys or data changed on reload');

// Repeating an add must reject the duplicate and preserve the existing IP list.
$before = serialize($reloaded);
$settings = settingsFor($reloaded);
$duplicate = $settings->optionsValidate(['crawler' => array_replace($input, ['ip_addresses' => '198.51.100.1'])]);
expect($settings->errors() === ['A crawler with this Id already exists.'], 'Unicode duplicate bypassed validation');
expect(serialize($duplicate) === $before, 'Duplicate add overwrote the existing crawler');

// An explicit ID must collide with the same generated ID.
$settings = settingsFor($reloaded);
$settings->optionsValidate(['crawler' => ['key' => strtoupper($key), 'title' => 'Other crawler']]);
expect($settings->errors() === ['A crawler with this Id already exists.'], 'Explicit ID bypassed canonical duplicate check');

// Renaming another crawler to a generated duplicate must preserve both entries.
$reloaded->crawlers->entries['other-crawler'] = Defaults::normalizeCrawler('other-crawler', ['title' => 'Other crawler']);
$before = serialize($reloaded);
$settings = settingsFor($reloaded);
$renamed = $settings->optionsValidate(['crawler' => array_replace($input, ['original_key' => 'other-crawler'])]);
expect($settings->errors() === ['A crawler with this Id already exists.'], 'Rename accepted a duplicate generated ID');
expect(serialize($renamed) === $before, 'Failed rename changed the original or target crawler');

// Editing the same crawler still succeeds when its ID is regenerated from the title.
$settings = settingsFor($reloaded);
$edited = $settings->optionsValidate(['crawler' => array_replace($input, ['original_key' => $key, 'notes' => 'Updated'])]);
expect($settings->errors() === [], 'Editing the original crawler was rejected as a duplicate');
expect($edited->crawlers->entries[$key]['notes'] === 'Updated', 'Editing created a different key');

$settings = settingsFor(Options::parseOptions([]));
$ascii = $settings->optionsValidate(['crawler' => ['title' => 'Example Crawler']]);
expect(isset($ascii->crawlers->entries['example-crawler']), 'ASCII title slug changed');
$settings = settingsFor(Options::parseOptions([]));
$settings->optionsValidate(['crawler' => ['title' => '!!!']]);
expect($settings->errors() === ['Enter a name or Id for the crawler.'], 'Empty generated ID was accepted');

echo "Passed $checks crawler ID checks (isolated; no database or HTTP).\n";
