<?php

// phpcs:ignoreFile -- Isolated CLI regression checks without database access.

/**
 * Verify Heartbeat validation and the runtime behavior described by the UI.
 * Run: php tests/heartbeat-settings.php
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
require dirname(__DIR__) . '/includes/Config.php';
require dirname(__DIR__) . '/includes/Main.php';
require dirname(__DIR__) . '/includes/Settings.php';
require dirname(__DIR__) . '/includes/Heartbeat/Settings.php';
require dirname(__DIR__) . '/includes/Heartbeat/Heartbeat.php';

$heartbeatErrors = [];
$heartbeatAdmin = true;
$heartbeatLoggedIn = true;
$heartbeatCustomizer = false;
$heartbeatRoles = ['editor'];
$heartbeatRemovedScripts = [];
$heartbeatInlineScripts = [];
$checks = 0;

/**
 * Fail on warnings such as undefined option keys.
 * @param int $severity Error level.
 * @param string $message Error description.
 * @return never
 */
function failHeartbeatWarning(int $severity, string $message): never
{
    throw new RuntimeException($message, $severity);
}
set_error_handler('failHeartbeatWarning');

/** @return bool Network administration context for option validation. */
function is_network_admin() { return true; }

/** @return bool Whether this request represents an administration page. */
function is_admin() { return $GLOBALS['heartbeatAdmin']; }

/** @return bool Whether this request has an authenticated user. */
function is_user_logged_in() { return $GLOBALS['heartbeatLoggedIn']; }

/** @return bool Whether this request is the Customizer preview. */
function is_customize_preview() { return $GLOBALS['heartbeatCustomizer']; }

/** @return object User fixture with ordered website roles. */
function wp_get_current_user() { return (object) ['roles' => $GLOBALS['heartbeatRoles']]; }

/**
 * Merge option values without loading storage.
 * @param array|object $args Explicit values.
 * @param array $defaults Fallback values.
 * @return array Merged values.
 */
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, (array) $args); }

/**
 * Unslash the string input used by these fixtures.
 * @param string $value Slashed input.
 * @return string Unslashed input.
 */
function wp_unslash($value) { return stripslashes($value); }

/**
 * Keep error messages readable without loading translations.
 * @param string $text Source message.
 * @param string $domain Translation domain.
 * @return string Source message.
 */
function __($text, $domain = 'default') { return $text; }

/**
 * Capture validation notices rather than storing them.
 * @param string $setting Settings identifier.
 * @param string $code Error identifier.
 * @param string $message Error message.
 * @return void
 */
function add_settings_error($setting, $code, $message)
{
    $GLOBALS['heartbeatErrors'][] = compact('setting', 'code', 'message');
}

/**
 * Retrieve captured notices for the tested page.
 * @param string $setting Settings identifier.
 * @return array Captured notices.
 */
function get_settings_errors($setting) { return $GLOBALS['heartbeatErrors']; }

/**
 * Expose the captured error to test the page's notice callback.
 * @param string $setting Settings identifier.
 * @return void
 */
function settings_errors($setting) { echo $GLOBALS['heartbeatErrors'][0]['message']; }

/**
 * Record script removal without changing a browser or server state.
 * @param string $handle Script handle.
 * @return void
 */
function wp_deregister_script($handle) { $GLOBALS['heartbeatRemovedScripts'][] = $handle; }

/**
 * Capture the actual calculated JavaScript interval.
 * @param string $handle Script handle.
 * @param string $code Inline script.
 * @param string $position Script placement.
 * @return void
 */
function wp_add_inline_script($handle, $code, $position) { $GLOBALS['heartbeatInlineScripts'][] = $code; }

/**
 * Create fresh two-level options while retaining nested role maps as arrays.
 * @return object Plugin defaults in their runtime shape.
 */
function heartbeatOptions(): object
{
    $sections = [];
    foreach (\RRZE\Settings\Config::get('options') as $key => $value) {
        $sections[$key] = is_array($value) ? (object) $value : $value;
    }
    return (object) $sections;
}

/**
 * Assert an observable outcome independently of PHP assertion settings.
 * @param bool $condition Outcome to verify.
 * @param string $message Failure description.
 * @return void
 */
function checkHeartbeat(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}

/** Initialize the actual runtime without accessing its storage constructor. */
class HeartbeatRuntimeFixture extends \RRZE\Settings\Heartbeat\Heartbeat
{
    /**
     * Inject isolated network options.
     * @param object $network Network options.
     */
    public function __construct(object $network)
    {
        $this->siteOptions = $network;
    }
}

$network = heartbeatOptions();
$network->heartbeat->editor_interval = 30;
$network->heartbeat->admin_interval = 90;
$settings = new \RRZE\Settings\Heartbeat\Settings('rrze_settings', heartbeatOptions(), $network, heartbeatOptions());
$saved = $settings->optionsValidate([]);
checkHeartbeat($saved->heartbeat->editor_interval === 30 && $saved->heartbeat->admin_interval === 90, 'Omitted intervals must retain their saved values without warnings.');
$saved = $settings->optionsValidate(['editor_interval' => '2', 'admin_interval' => '15']);
checkHeartbeat($saved->heartbeat->editor_interval === 15 && $saved->heartbeat->admin_interval === 60, 'Saved interval minimums must match the runtime and UI.');

$oldRules = $network->heartbeat->role_overrides;
foreach (['{"editor":', '[{"editor":30}]', 'null', 'true', '{"editor":30}', '{"editor":{"editor":"soon"}}', '{"editor":{"default":[]}}', '{"editor":{"default":30.5}}', '{"editor":{"defaut":90}}'] as $badInput) {
    $heartbeatErrors = [];
    $saved = $settings->optionsValidate(['role_overrides' => $badInput, 'admin_interval' => '120']);
    checkHeartbeat($saved->heartbeat->role_overrides === $oldRules && count($heartbeatErrors) === 1, 'Invalid role input must preserve all existing rules and report an error: ' . $badInput);
    checkHeartbeat($saved->heartbeat->admin_interval === 120, 'Other valid settings must still be saved when role JSON is invalid.');
}
ob_start();
$settings->settingsUpdateNotice();
$notice = ob_get_clean();
checkHeartbeat(str_contains($notice, 'previous role exceptions were kept'), 'The network notice must explain that previous role rules were retained.');

foreach (['', '{}', '[]'] as $emptyInput) {
    $heartbeatErrors = [];
    $saved = $settings->optionsValidate(['role_overrides' => $emptyInput]);
    checkHeartbeat($saved->heartbeat->role_overrides === [] && $heartbeatErrors === [], 'Explicit empty input must clear role exceptions.');
}
$saved = $settings->optionsValidate(['role_overrides' => addslashes('{"editor":{"editor":2,"default":15},"custom_role":{"editor":45}}')]);
checkHeartbeat($saved->heartbeat->role_overrides === ['editor' => ['editor' => 15, 'default' => 60], 'custom_role' => ['editor' => 45]], 'JSON must be unslashed, preserve custom roles and normalize context-specific minimums.');
$saved = $settings->optionsValidate(['role_overrides' => ['editor' => ['editor' => 30, 'default' => 90]]]);
checkHeartbeat($saved->heartbeat->role_overrides['editor'] === ['editor' => 30, 'default' => 90], 'Existing array input must remain supported.');
$saved = $settings->optionsValidate(['admin_allowlist_hooks' => "index.php\r\nupload.php\nindex.php\n\n"]);
checkHeartbeat($saved->heartbeat->admin_allowlist_hooks === ['index.php', 'upload.php'], 'Hook lists must accept line breaks and remove duplicates.');

$network = heartbeatOptions();
$runtime = new HeartbeatRuntimeFixture($network);
$pagenow = 'post.php';
checkHeartbeat($runtime->filterHeartbeatSettings([])['interval'] === 30, 'The default editor-role rule must override the general editor interval.');
$heartbeatRoles = ['author', 'editor'];
checkHeartbeat($runtime->filterHeartbeatSettings([])['interval'] === 45, 'The first matching user role must win, not the smallest interval.');
$heartbeatRoles = ['editor'];
$network->heartbeat->role_overrides = ['editor' => ['default' => 90]];
checkHeartbeat($runtime->filterHeartbeatSettings([])['interval'] === 90, 'A role default must also apply in the editor when that role has no editor value.');
$network->heartbeat->role_overrides = [];
$network->heartbeat->editor_interval = 45;
checkHeartbeat($runtime->filterHeartbeatSettings([])['interval'] === 45, 'Without role rules, the general editor interval must apply.');
$pagenow = 'index.php';
checkHeartbeat($runtime->filterHeartbeatSettings([])['interval'] === 60, 'Other administration pages must use the administration default.');
$heartbeatAdmin = false;
checkHeartbeat($runtime->filterHeartbeatSettings(['interval' => 20])['interval'] === 20, 'Administration defaults must not change the public-site interval.');

$network->heartbeat->disable_frontend = true;
$runtime->maybeDisableFrontendHeartbeat();
checkHeartbeat($heartbeatRemovedScripts === [], 'Logged-in public visitors must remain exempt.');
$heartbeatLoggedIn = false;
$heartbeatCustomizer = true;
$runtime->maybeDisableFrontendHeartbeat();
checkHeartbeat($heartbeatRemovedScripts === [], 'Customizer previews must remain exempt.');
$heartbeatCustomizer = false;
$runtime->maybeDisableFrontendHeartbeat();
checkHeartbeat($heartbeatRemovedScripts === ['heartbeat'], 'Logged-out public pages must lose Heartbeat when enabled.');

$heartbeatAdmin = true;
$heartbeatLoggedIn = true;
$network->heartbeat->disable_admin_non_editor = true;
$heartbeatRemovedScripts = [];
$pagenow = 'post.php';
$runtime->maybeDisableAdminHeartbeat('post.php');
checkHeartbeat($heartbeatRemovedScripts === [], 'Content editor screens must retain Heartbeat.');
$pagenow = 'index.php';
$runtime->maybeDisableAdminHeartbeat('index.php');
checkHeartbeat($heartbeatRemovedScripts === [], 'The default dashboard allowlist entry must retain Heartbeat.');
$pagenow = 'site-editor.php';
$runtime->maybeDisableAdminHeartbeat('site-editor.php');
checkHeartbeat($heartbeatRemovedScripts === ['heartbeat'], 'Document the current Site Editor limitation without silently changing runtime policy.');
$heartbeatRemovedScripts = [];
$network->heartbeat->admin_allowlist_hooks[] = 'site-editor.php';
$runtime->maybeDisableAdminHeartbeat('site-editor.php');
checkHeartbeat($heartbeatRemovedScripts === [], 'Explicit Site Editor allowlisting must retain Heartbeat.');

$network->heartbeat->force_js_slow = true;
$network->heartbeat->role_overrides = ['editor' => ['default' => 90]];
$runtime->maybeForceInlineAfter();
checkHeartbeat(str_contains($heartbeatInlineScripts[0], 'interval(90)'), 'The browser override must use the effective role interval, not a fixed 60 seconds.');
$network->heartbeat->force_js_slow = false;
$runtime->maybeForceInlineAfter();
checkHeartbeat(count($heartbeatInlineScripts) === 1, 'Disabling the browser override must omit the additional script.');

echo "Passed $checks Heartbeat checks without database writes.\n";
