<?php

// phpcs:ignoreFile -- Isolated CLI regression tests with no database writes.

/**
 * Exercise editor option validation, WordPress hooks and preservation of block providers.
 * Run: php tests/editor-settings.php [WordPress root]
 */
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
define('ABSPATH', rtrim($argv[1] ?? dirname(__DIR__, 4), '/') . '/');
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-block-type.php';
require ABSPATH . 'wp-includes/class-wp-block-type-registry.php';
require dirname(__DIR__) . '/includes/Config.php';
require dirname(__DIR__) . '/includes/Main.php';
require dirname(__DIR__) . '/includes/Settings.php';
require dirname(__DIR__) . '/includes/Writing/Settings.php';
require dirname(__DIR__) . '/includes/Writing/Writing.php';
require dirname(__DIR__) . '/includes/Writing/BlockEditor/Editor.php';
require dirname(__DIR__) . '/includes/Writing/BlockEditor/Blocks.php';

$networkContext = true;
$registeredEditorFields = [];
$checks = 0;

/**
 * Select the isolated network or website validation path.
 * @return bool Whether this fixture represents network administration.
 */
function is_network_admin() { return $GLOBALS['networkContext']; }

/**
 * Represent an authorized administrator without loading account storage.
 * @return bool Always true for these fixtures.
 */
function is_super_admin() { return true; }

/**
 * Merge the array-only arguments used by the exercised classes.
 * @param array $args Explicit values.
 * @param array $defaults Fallback values.
 * @return array Merged values.
 */
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, (array) $args); }

/**
 * Convert the lock interval as WordPress does.
 * @param mixed $value Input value.
 * @return int Non-negative integer.
 */
function absint($value) { return abs((int) $value); }

/**
 * Keep source labels available without loading translations.
 * @param string $text Source text.
 * @param string $domain Translation domain.
 * @return string Source text.
 */
function __($text, $domain = 'default') { return $text; }

/**
 * Accept section registration without rendering the administration page.
 * @param mixed ...$args Section registration arguments.
 * @return void
 */
function add_settings_section(...$args) {}

/**
 * Record field registration for both editor policies.
 * @param string $id Field identifier.
 * @param mixed ...$args Remaining field registration arguments.
 * @return void
 */
function add_settings_field($id, ...$args) { $GLOBALS['registeredEditorFields'][] = $id; }

/**
 * Create independent option fixtures from the actual plugin defaults.
 * @return object Options grouped into section objects.
 */
function editorOptions(): object
{
    return json_decode(json_encode(\RRZE\Settings\Config::get('options')));
}

/**
 * Assert an observable result without relying on PHP assertion configuration.
 * @param bool $condition Result to verify.
 * @param string $message Failure description.
 * @return void
 */
function checkEditor(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}

/** Supply options to the actual runtime without its storage-reading constructor. */
class EditorWritingFixture extends \RRZE\Settings\Writing\Writing
{
    /**
     * Initialize in-memory network and website options.
     * @param object $local Website options.
     * @param object $network Network options.
     */
    public function __construct(object $local, object $network)
    {
        $this->optionName = 'rrze_settings';
        $this->options = $local;
        $this->siteOptions = $network;
        $this->defaultOptions = editorOptions();
    }
}

$local = editorOptions();
$network = editorOptions();
$local->writing->post_lock = 360;
$network->writing->post_lock = 240;
$network->writing->autosave_interval = 120;
$network->writing->enable_block_editor_new_sites = 1;
$settings = new \RRZE\Settings\Writing\Settings('rrze_settings', $local, $network, editorOptions());
$saved = $settings->optionsValidate([]);
checkEditor($saved->writing->enable_block_editor_new_sites === 0, 'An unchecked new-websites control must switch off.');
checkEditor($saved->writing->post_lock === 240, 'Network save without a lock field must preserve its scalar interval.');
checkEditor($saved->writing->autosave_interval === 120, 'Missing autosave input must preserve the saved interval.');
$saved = $settings->optionsValidate(['enable_block_editor_new_sites' => '1', 'autosave_interval' => '15']);
checkEditor($saved->writing->enable_block_editor_new_sites === 1, 'New websites must be enabled explicitly.');
checkEditor($saved->writing->autosave_interval === 60, 'Autosave must enforce the documented minimum.');
$saved = $settings->optionsValidate(['autosave_interval' => '180']);
checkEditor($saved->writing->autosave_interval === 180, 'Autosave must store a numeric interval.');
$networkContext = false;
$saved = $settings->optionsValidate(['post_lock' => '10']);
checkEditor($saved->writing->post_lock === 150, 'Website saves must match the displayed lock minimum.');
$local->writing->post_lock = 360;
$saved = $settings->optionsValidate([]);
checkEditor($saved->writing->post_lock === 360, 'Absent lock input must preserve the website interval.');
$networkContext = true;

foreach ([0, 1] as $policy) {
    $network->writing->enable_block_editor = $policy;
    $registeredEditorFields = [];
    $settings->networkAdminPage();
    checkEditor(count($registeredEditorFields) === 20, 'Both editor policies must expose the complete form.');
    foreach (['allowed_post_types', 'themes_exceptions', 'websites_exceptions'] as $field) {
        checkEditor(in_array($field, $registeredEditorFields, true), 'An active exemption must remain visible.');
    }
}

$network = editorOptions();
$network->writing->enable_post_lock = 1;
$runtime = new EditorWritingFixture($local, $network);
$runtime->loaded();
checkEditor(apply_filters('wp_check_post_lock_window', 150) === 360, 'The actual WordPress hook must apply the website lock interval.');
remove_filter('wp_check_post_lock_window', [$runtime, 'filterPostLock']);
$network->writing->enable_post_lock = 0;
$runtime->loaded();
checkEditor(apply_filters('wp_check_post_lock_window', 150) === 150, 'Disabling customization must retain the WordPress lock window.');
$network->writing->sync_autosave = 1;
$network->heartbeat->editor_interval = 60;
checkEditor($runtime->filterBlockEditorSettings([], (object) [])['autosaveInterval'] === 120, 'Autosave synchronization must double the configured Heartbeat interval.');
$network->heartbeat->editor_interval = 15;
checkEditor($runtime->filterBlockEditorSettings([], (object) [])['autosaveInterval'] === 60, 'Synchronized autosave must retain its minimum.');
$network->writing->sync_autosave = 0;
$network->writing->autosave_interval = 90;
checkEditor($runtime->filterBlockEditorSettings([], (object) [])['autosaveInterval'] === 90, 'Unsynchronized autosave must use the explicit interval.');

/**
 * Render a dynamic fixture using existing inner content.
 * @param array $attributes Stored attributes.
 * @param string $content Stored inner content.
 * @return string Rendered content.
 */
function renderExistingEditorBlock($attributes, $content) { return '<div>' . $content . '</div>'; }

$registry = \WP_Block_Type_Registry::get_instance();
$registry->register('example/retained', ['render_callback' => 'renderExistingEditorBlock']);
$registry->register('example/available');
$network->writing->allowed_block_types = '';
$network->writing->disabled_block_types = ['example/retained'];
$blocks = new \RRZE\Settings\Writing\BlockEditor\Blocks($network);
checkEditor($blocks->getFilteredBlocks() === ['example/available'], 'Excluded blocks must be absent from new insertion choices.');
checkEditor($registry->is_registered('example/retained'), 'Excluding a block must retain its registered provider.');
checkEditor($registry->get_registered('example/retained')->render([], 'Existing content') === '<div>Existing content</div>', 'Existing content must still render through the excluded block provider.');

echo "Passed $checks editor settings checks without database writes.\n";
