<?php

/**
 * Exercise the last login tracking without WordPress.
 * Run: php tests/last-login.php
 */

namespace {
    if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
        exit(1);
    }
    define('ABSPATH', '/');

    $GLOBALS['hooks'] = [];
    $GLOBALS['actions'] = [];
    $GLOBALS['usermeta'] = [];
    $GLOBALS['currentUser'] = 0;
    $GLOBALS['failures'] = 0;

    $GLOBALS['networkAdmin'] = true;

    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        $GLOBALS['hooks'][$hook][] = $callback;
        return true;
    }
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return add_filter($hook, $callback, $priority, $args);
    }
    function is_multisite() {
        return true;
    }
    function is_network_admin() {
        return $GLOBALS['networkAdmin'];
    }
    function __($text, $domain = 'default') {
        return $text;
    }
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text, ENT_QUOTES);
    }
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
    function get_option($name) {
        return ['date_format' => 'd.m.Y', 'time_format' => 'H:i'][$name] ?? '';
    }
    function wp_date($format, $timestamp) {
        return gmdate($format, $timestamp);
    }
    function human_time_diff($from, $to) {
        return '2 days';
    }

    class FakeWpdb {
        public $users = 'wp_users';
        public $usermeta = 'wp_usermeta';

        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", $query), $args);
        }
    }
    $GLOBALS['wpdb'] = new FakeWpdb();

    class FakeUserQuery {
        public $query_vars;
        public $query_from = 'FROM wp_users';
        public $query_orderby = 'ORDER BY user_login ASC';

        public function __construct(array $vars) {
            $this->query_vars = $vars;
        }

        public function get($key) {
            return $this->query_vars[$key] ?? null;
        }
    }
    function do_action($hook, ...$args) {
        $GLOBALS['actions'][] = [$hook, $args];
        foreach ($GLOBALS['hooks'][$hook] ?? [] as $callback) {
            call_user_func_array($callback, $args);
        }
    }
    function update_user_meta($userId, $key, $value) {
        $GLOBALS['usermeta'][$userId][$key] = $value;
        return true;
    }
    function get_user_meta($userId, $key, $single = false) {
        return $GLOBALS['usermeta'][$userId][$key] ?? '';
    }
    function absint($value) {
        return abs((int) $value);
    }
    function get_current_user_id() {
        return $GLOBALS['currentUser'];
    }

    function check(string $label, bool $ok): void {
        echo ($ok ? 'ok   ' : 'FAIL ') . $label . PHP_EOL;
        if (!$ok) {
            $GLOBALS['failures']++;
        }
    }

    require __DIR__ . '/../includes/Users/LastLogin.php';
    require __DIR__ . '/../includes/Helper.php';
}

namespace {
    use RRZE\Settings\Users\LastLogin;
    use RRZE\Settings\Helper;

    (new LastLogin())->loaded();
    check('hook registered on wp_login', !empty($GLOBALS['hooks']['wp_login']));

    check('unknown user returns 0', LastLogin::get(42) === 0);

    $user = new \stdClass();
    $user->ID = 42;
    $before = time();
    do_action('wp_login', 'jdoe', $user);
    $after = time();

    $stored = $GLOBALS['usermeta'][42][LastLogin::META_KEY] ?? null;
    check('stored as integer', is_int($stored));
    check('stored timestamp is now', $stored >= $before && $stored <= $after);
    check('get() returns stored value', LastLogin::get(42) === $stored);

    $fired = array_values(array_filter($GLOBALS['actions'], 'isLastLoginAction'));
    check('rrze_settings_user_last_login fired once', count($fired) === 1);
    check('action passes user id and timestamp', ($fired[0][1] ?? null) === [42, $stored]);

    $GLOBALS['actions'] = [];
    do_action('wp_login', 'ghost', null);
    check('missing user object ignored', count(array_filter($GLOBALS['actions'], 'isLastLoginAction')) === 0);

    LastLogin::update(7, 1700000000);
    check('explicit timestamp kept', LastLogin::get(7) === 1700000000);

    $GLOBALS['usermeta'][7][LastLogin::LEGACY_ACTIVITY_META_KEY] = ['timestamp' => 1800000000];
    $GLOBALS['usermeta'][7]['wfls_last_login'] = 1900000000;
    check('RRZE timestamp has priority over legacy sources', LastLogin::get(7) === 1700000000);

    $GLOBALS['usermeta'][8][LastLogin::LEGACY_ACTIVITY_META_KEY] = ['timestamp' => 1700000100];
    check('legacy audit timestamp is used as fallback', LastLogin::get(8) === 1700000100);

    $GLOBALS['usermeta'][9]['wfls_last_login'] = 1700000200;
    $GLOBALS['usermeta'][9]['wfls-last-login'] = 1700000300;
    check('latest Wordfence timestamp is used as fallback', LastLogin::get(9) === 1700000300);

    $GLOBALS['usermeta'][10]['wfls_lastlogin'] = '2023-11-14 22:13:20 UTC';
    check('date-based Wordfence timestamp is used as fallback', LastLogin::get(10) === 1700000000);

    LastLogin::update(0);
    check('user id 0 ignored', !isset($GLOBALS['usermeta'][0]));

    $GLOBALS['currentUser'] = 7;
    check('Helper::getUserLastLogin() current user', Helper::getUserLastLogin() === 1700000000);
    check('Helper::getUserLastLogin() explicit user', Helper::getUserLastLogin(42) === $stored);

    // Network admin user list: column.
    $lastLogin = new LastLogin();
    check('column hooks registered', !empty($GLOBALS['hooks']['wpmu_users_columns']) && !empty($GLOBALS['hooks']['pre_user_query']));

    $columns = $lastLogin->addColumn(['cb' => '', 'username' => 'Username', 'registered' => 'Registered', 'wfls_last_login' => 'Last Login', 'blogs' => 'Sites']);
    check('column inserted after registered', array_keys($columns) === ['cb', 'username', 'registered', LastLogin::COLUMN, 'blogs']);
    check('Wordfence last-login column removed', !isset($columns['wfls_last_login']));
    check('column label', $columns[LastLogin::COLUMN] === 'Last login');

    $columns = $lastLogin->addColumn(['cb' => '', 'username' => 'Username']);
    check('column appended without registered', array_key_last($columns) === LastLogin::COLUMN);

    $sortable = $lastLogin->addSortableColumn(['username' => 'login']);
    check('sortable, descending first', $sortable[LastLogin::COLUMN] === [LastLogin::COLUMN, true]);

    // Rendering.
    check('foreign column untouched', $lastLogin->renderColumn('x', 'email', 7) === 'x');
    $html = $lastLogin->renderColumn('', LastLogin::COLUMN, 7);
    check('renders date only', str_contains($html, '>14.11.2023<'));
    check('does not render relative date or additional visible content', !str_contains($html, '2 days ago') && !str_contains($html, '<br>'));
    check('renders machine-readable time', str_contains($html, 'datetime="2023-11-14T22:13:20+00:00"'));
    $html = $lastLogin->renderColumn('', LastLogin::COLUMN, 99);
    check('unknown: dash plus screen reader text', str_contains($html, '&mdash;') && str_contains($html, 'No login recorded'));

    // Sorting.
    $query = new FakeUserQuery(['orderby' => LastLogin::COLUMN, 'order' => 'desc']);
    $lastLogin->sortQuery($query);
    check('sort: RRZE timestamp has priority', str_contains($query->query_orderby, "rrze_last_login.meta_key = 'rrze_last_login'"));
    check('sort: includes legacy audit fallback', str_contains($query->query_orderby, "rrze_legacy_activity.meta_key = 'rrze_users_activity'"));
    check('sort: includes Wordfence fallback', str_contains($query->query_orderby, "rrze_wordfence.meta_key IN ('wfls_last_login', 'wfls-last-login', 'wfls_lastlogin')"));
    check('sort: numeric desc order', str_ends_with($query->query_orderby, 'DESC, wp_users.user_login ASC'));

    $query = new FakeUserQuery(['orderby' => LastLogin::COLUMN, 'order' => 'asc; DROP TABLE x']);
    $lastLogin->sortQuery($query);
    check('sort: invalid order falls back to ASC', str_ends_with($query->query_orderby, 'ASC, wp_users.user_login ASC'));

    $query = new FakeUserQuery(['orderby' => 'login', 'order' => 'asc']);
    $lastLogin->sortQuery($query);
    check('sort: other orderby untouched', $query->query_from === 'FROM wp_users' && $query->query_orderby === 'ORDER BY user_login ASC');

    $GLOBALS['networkAdmin'] = false;
    $query = new FakeUserQuery(['orderby' => LastLogin::COLUMN, 'order' => 'asc']);
    $lastLogin->sortQuery($query);
    check('sort: only in network admin', $query->query_from === 'FROM wp_users');

    function isLastLoginAction($entry) {
        return $entry[0] === 'rrze_settings_user_last_login';
    }

    echo PHP_EOL . ($GLOBALS['failures'] ? $GLOBALS['failures'] . ' failure(s)' : 'all passed') . PHP_EOL;
    exit($GLOBALS['failures'] ? 1 : 0);
}
