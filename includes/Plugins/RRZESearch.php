<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Options;
use RRZE\Settings\Helper;
use RRZE\Settings\Library\Encryption\Encryption;

/**
 * RRZE Search class to add Search machines via Networkwide settings
 *
 * @package RRZE\Settings\Plugins
 */
class RRZESearch
{
    /**
     * Plugin slug
     *
     * @var string
     */
    const PLUGIN = 'rrze-search/rrze-search.php';

    /**
     * Site options
     *
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options
     */
    public function __construct()
    {
        add_action('rrze_search_increment', [$this, 'handleIncrement'], 10, 1);

        // Cron Handlers
        add_action('rrze_search_reset_hour',  [$this, 'cronResetHour']);
        add_action('rrze_search_reset_week',  [$this, 'cronResetWeek']);
        add_action('rrze_search_reset_month', [$this, 'cronResetMonth']);
        add_action('rrze_search_reset_year',  [$this, 'cronResetYear']);

        add_action('init', [$this, 'maybeScheduleCronEvents']);
    }

    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        if (!$this->pluginExists(self::PLUGIN) || !$this->isPluginActive(self::PLUGIN)) {
            return;
        }
    }

    /**
     * Nur auf der Haupt-Site Cron-Events planen, falls noch nicht vorhanden.
     */
    public function maybeScheduleCronEvents(): void
    {
        if ( ! is_multisite() || ! is_main_site() ) return;

        try {
            $this->scheduleIfMissing('rrze_search_reset_hour',  $this->nextBoundary('hour'));
            $this->scheduleIfMissing('rrze_search_reset_week',  $this->nextBoundary('week'));
            $this->scheduleIfMissing('rrze_search_reset_month', $this->nextBoundary('month'));
            $this->scheduleIfMissing('rrze_search_reset_year',  $this->nextBoundary('year'));
        } catch (\Throwable $e) {
            $now = time();
            $this->scheduleIfMissing('rrze_search_reset_hour',  $now + HOUR_IN_SECONDS);
            $this->scheduleIfMissing('rrze_search_reset_week',  $now + WEEK_IN_SECONDS);
            $this->scheduleIfMissing('rrze_search_reset_month', $now + MONTH_IN_SECONDS);
            $this->scheduleIfMissing('rrze_search_reset_year',  $now + YEAR_IN_SECONDS);
        }
    }

    private function scheduleIfMissing(string $hook, int $timestamp): void
    {
        if ( ! wp_next_scheduled($hook) ) {
            wp_schedule_single_event($timestamp, $hook);
        }
    }

    /**
     * Berechnet den nächsten UTC-Grenzzeitpunkt für hour/week/month/year.
     * @throws \DateMalformedStringException
     */
    private function nextBoundary(string $unit): int
    {
        $tz  = new \DateTimeZone('UTC');
        $now = new \DateTimeImmutable('now', $tz);

        switch ($unit) {
            case 'week':
                $next = $now->modify('next monday')->setTime(0, 0, 0);
                break;

            case 'month':
                $y = (int)$now->format('Y');
                $m = (int)$now->format('m');
                if ($m === 12) { $y += 1; $m = 1; } else { $m += 1; }
                $next = $now->setDate($y, $m, 1)->setTime(0, 0, 0);
                break;

            case 'year':
                $y = (int)$now->format('Y') + 1;
                $next = $now->setDate($y, 1, 1)->setTime(0, 0, 0);
                break;

            default:
                $next = $now->setTime((int)$now->format('H'), 0, 0)->modify('+1 hour');
                break;
        }

        return $next->getTimestamp(); // UTC Unix Timestamp
    }


    /**
     * If the Setting rrze_search_network_limits_and_statistics is not available, initialize it with
     * $limit_per_hour, $limit_per_day, $limit_per_week, $limit_per_month, $limit_per_year and the counters $total_hour, $total_day, $total_week
     * $total_month and $total_year.
     *
     * @param $limit_per_hour - API Limit per Hour
     * @param $limit_per_day - API Limit per Day
     * @param $limit_per_week - API Limit per Week
     * @param $limit_per_month - API Limit per Month
     * @param $limit_per_year - API Limit per Year
     * @return void
     */
    public static function initializeNetworkSettings($limit_per_hour, $limit_per_day, $limit_per_week, $limit_per_month, $limit_per_year): void
    {
        if ( ! is_multisite() )
        {
            return;
        }

        $option_name = 'rrze_search_network_limits_and_stats';
        $existing = get_site_option($option_name, null);
        if ($existing !== null) {
            return;
        }

        $limits = [
            'hour'  => max(0, (int) $limit_per_hour),
            'day'   => max(0, (int) $limit_per_day),
            'week'  => max(0, (int) $limit_per_week),
            'month' => max(0, (int) $limit_per_month),
            'year'  => max(0, (int) $limit_per_year),
        ];

        $totals = [
            'hour'  => 0,
            'day'   => 0,
            'week'  => 0,
            'month' => 0,
            'year'  => 0,
        ];

        $payload = [
            'limits' => $limits,
            'totals' => $totals,
        ];

        add_site_option($option_name, $payload);
    }

    /**
     * A Search Request was used. Inkrements the right stats Option values by 1.
     *
     * Receives the current UTC timestamp and adds it to the relevant Stats inside the options initialized before.
     */
    public function incrementRRZESearchStats(): void
    {
        if ( ! is_multisite() ) {
            return;
        }

        $option_name  = 'rrze_search_network_limits_and_stats';
        $lock_key     = $option_name . '_lock';
        $lock_acquired = false;
        $max_attempts  = 6;     // ~1.2s gesamt
        $sleep_us      = 200000; // 200ms
        $lock_ttl      = 3;     // Seconds

        for ($i = 0; $i < $max_attempts; $i++) {
            if ( function_exists('wp_cache_add') && wp_cache_add($lock_key, 1, '', $lock_ttl) ) {
                $lock_acquired = true;
                break;
            }
            usleep($sleep_us);
        }

        if ( ! $lock_acquired ) {
            return;
        }

        try {
            $data = get_site_option($option_name, null);

            if ( ! is_array($data) ) {
                $this->initializeNetworkSettings(0, 0, 0, 0, 0);

                $data = get_site_option($option_name, null);
                if ( ! is_array($data) ) {
                    return;
                }
            }

            if (empty($data['totals']) || !is_array($data['totals'])) {
                $data['totals'] = [];
            }
            foreach (['hour','day','week','month','year'] as $k) {
                if (!isset($data['totals'][$k]) || !is_int($data['totals'][$k])) {
                    $data['totals'][$k] = 0;
                }
            }

            $data['totals']['hour']  += 1;
            $data['totals']['day']   += 1;
            $data['totals']['week']  += 1;
            $data['totals']['month'] += 1;
            $data['totals']['year']  += 1;

            update_site_option($option_name, $data);
        } finally {
            if ( function_exists('wp_cache_delete') ) {
                wp_cache_delete($lock_key, '');
            }
        }
    }

    /**
     * Available Hook-Handler: Fired by do_action('rrze_search_increment', $context).
     *
     * @param array $context Frei wählbare Metadaten (z. B. ['source' => 'my-plugin', 'site_id' => get_current_blog_id()])
     * @return void
     */
    public function handleIncrement(array $context = []): void
    {
        $should_increment = apply_filters('rrze_search_should_increment', true, $context);

        if ( ! $should_increment ) {
            return;
        }

        $this->incrementRRZESearchStats();

        do_action('rrze_search_after_increment', $context);
    }

    public function cronResetHour(): void
    {
        if ( ! $this->isMainSiteCron() ) {
            return;
        }
        $this->resetTotals(['hour']);
        wp_schedule_single_event($this->nextBoundary('hour'), 'rrze_search_reset_hour');
    }

    public function cronResetWeek(): void
    {
        if ( ! $this->isMainSiteCron() ) {
            return;
        }
        $this->resetTotals(['week']);
        wp_schedule_single_event($this->nextBoundary('week'), 'rrze_search_reset_week');
    }

    public function cronResetMonth(): void
    {
        if ( ! $this->isMainSiteCron() ) {
            return;
        }
        $this->resetTotals(['month']);
        wp_schedule_single_event($this->nextBoundary('month'), 'rrze_search_reset_month');
    }

    public function cronResetYear(): void
    {
        if ( ! $this->isMainSiteCron() ) {
            return;
        }
        $this->resetTotals(['year']);
        wp_schedule_single_event($this->nextBoundary('year'), 'rrze_search_reset_year');
    }

    /**
     * Resettet die angegebenen Keys in totals auf 0 (mit Soft-Lock).
     */
    private function resetTotals(array $keys): void
    {
        if ( ! is_multisite() ) {
            return;
        }

        $option_name   = 'rrze_search_network_limits_and_stats';
        $lock_key      = $option_name . '_lock';
        $lock_acquired = false;
        $max_attempts  = 6;
        $sleep_us      = 200000; // 200ms
        $lock_ttl      = 3;

        for ($i = 0; $i < $max_attempts; $i++) {
            if ( function_exists('wp_cache_add') && wp_cache_add($lock_key, 1, '', $lock_ttl) ) {
                $lock_acquired = true;
                break;
            }
            usleep($sleep_us);
        }

        if ( ! $lock_acquired ) {
            return;
        }

        try {
            $data = get_site_option($option_name, null);
            if ( ! is_array($data) ) {
                return;
            }

            if (empty($data['totals']) || !is_array($data['totals'])) {
                $data['totals'] = [];
            }

            foreach ($keys as $k) {
                if (!isset($data['totals'][$k]) || !is_int($data['totals'][$k])) {
                    $data['totals'][$k] = 0;
                } else {
                    $data['totals'][$k] = 0;
                }
            }

            update_site_option($option_name, $data);
        } finally {
            if ( function_exists('wp_cache_delete') ) {
                wp_cache_delete($lock_key, '');
            }
        }
    }

    private function isMainSiteCron(): bool
    {
        return ( is_multisite() && is_main_site() );
    }

    /**
     * Define network-wide search engines as constants.
     *
     * @return void
     */
    public static function setRRZESearchSearchEngines()
    {
        // If everything is already defined, abort early.
        if (defined('RRZE_SEARCH_ENGINES') && defined('RRZE_SEARCH_ENGINE_PAIRS') && defined('RRZE_SEARCH_ENGINE_KEYS')) {
            return;
        }

        $raw = self::getRawOption();
        if ($raw === null || $raw === '' || $raw === []) {
            return;
        }

        $lines = self::linesFromRaw($raw);
        $lines = self::sanitizeLines($lines);
        $engines = self::parseAllEngines($lines);

        if (empty($engines)) {
            return;
        }

        self::defineEnginesConstants($engines);
    }

    /* =========================
    *  Normalization helpers
    * ========================= */

    /**
     * Fetch raw option value from site options.
     *
     * @return mixed null|string|array<int,string>
     */
    protected static function getRawOption()
    {
        $siteOptions = Options::getSiteOptions();
        return $siteOptions->plugins->rrze_search_engine_keys ?? null;
    }

    /**
     * Convert raw option (encrypted string, plaintext string, or array) to lines.
     *
     * @param mixed $raw
     * @return array<int,string>
     */
    protected static function linesFromRaw($raw)
    {
        if (is_string($raw)) {
            $maybe = Encryption::decrypt($raw);
            if ($maybe !== false && is_string($maybe)) {
                $raw = $maybe;
            }
            return preg_split('/\r\n|\r|\n/', (string)$raw) ?: [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        return [];
    }

    /**
     * Trim and drop empty lines (defensive; settings already sanitize).
     *
     * @param array<int,string> $lines
     * @return array<int,string>
     */
    protected static function sanitizeLines(array $lines)
    {
        $lines = array_map(static function ($l) {
            return trim((string)$l);
        }, $lines);

        return array_values(array_filter($lines, static function ($l) {
            return $l !== '';
        }));
    }

    /* =========================
     *  Parsing helpers
     * ========================= */

    /**
     * Parse all engines from a list of lines (3/4-line blocks).
     *
     * @param array<int,string> $lines
     * @return array<int,array{name:string,cx:string,api:string,desc:string}>
     */
    protected static function parseAllEngines(array $lines)
    {
        $engines = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            [$engine, $nextIndex] = self::parseEngineBlockAt($lines, $i);
            if ($engine !== null) {
                $engines[] = $engine;
                $i = $nextIndex;
            } else {
                // Move by one to avoid endless loop and try to resync.
                $i++;
            }
        }

        return $engines;
    }

    /**
     * Parse a single engine block starting at index $i.
     *
     * Returns [engine|null, nextIndex].
     *
     * @param array<int,string> $lines
     * @param int $i
     * @return array{0:?array{name:string,cx:string,api:string,desc:string},1:int}
     */
    protected static function parseEngineBlockAt(array $lines, $i)
    {
        $count = count($lines);
        // Need at least 3 lines for a valid block
        if ($i + 2 >= $count) {
            return [null, $i + 1];
        }

        $name = trim($lines[$i] ?? '');
        $cx = trim($lines[$i + 1] ?? '');
        $api = trim($lines[$i + 2] ?? '');

        if (!self::isValidRequiredTriplet($name, $cx, $api)) {
            return [null, $i + 1];
        }

        // Optional 4th line (description) heuristic
        $desc = '';
        if ($i + 3 < $count) {
            $maybeDesc = trim($lines[$i + 3]);
            if ($maybeDesc !== '') {
                $remainingAfter4 = $count - ($i + 4);
                if ($remainingAfter4 === 0 || $remainingAfter4 >= 3) {
                    $desc = $maybeDesc;
                    return [self::buildEngine($name, $cx, $api, $desc), $i + 4];
                }
            }
        }

        return [self::buildEngine($name, $cx, $api, $desc), $i + 3];
    }

    /**
     * Validate required fields of an engine triplet.
     *
     * @param string $name
     * @param string $cx
     * @param string $api
     * @return bool
     */
    protected static function isValidRequiredTriplet($name, $cx, $api)
    {
        return ($name !== '' && $cx !== '' && $api !== '');
    }

    /**
     * Build a normalized engine array.
     *
     * @param string $name
     * @param string $cx
     * @param string $api
     * @param string $desc
     * @return array{name:string,cx:string,api:string,desc:string}
     */
    protected static function buildEngine($name, $cx, $api, $desc = '')
    {
        return [
            'name'  => $name,
            'cx'    => $cx,
            'api'   => $api,
            'desc'  => $desc ?? '',
        ];
    }

    /* =========================
     *  Constants helpers
     * ========================= */

    /**
     * Define all related constants if not yet defined.
     *
     * @param array<int,array{name:string,cx:string,api:string,desc:string}> $engines
     * @return void
     */
    protected static function defineEnginesConstants(array $engines)
    {
        if (!defined('RRZE_SEARCH_ENGINES')) {
            define('RRZE_SEARCH_ENGINES', $engines);
        }

        if (!defined('RRZE_SEARCH_ENGINE_PAIRS')) {
            define('RRZE_SEARCH_ENGINE_PAIRS', self::toPairs($engines));
        }

        if (!defined('RRZE_SEARCH_ENGINE_KEYS')) {
            define('RRZE_SEARCH_ENGINE_KEYS', self::toLegacyKeys($engines));
        }
    }

    /**
     * Map engines to legacy "pairs" structure.
     *
     * @param array<int,array{name:string,cx:string,api:string,desc:string}> $engines
     * @return array<int,array{api:string,cx:string,name:string,desc:string}>
     */
    protected static function toPairs(array $engines)
    {
        return array_map(static function ($e) {
            return [
                'api'   => $e['api'],
                'cx'    => $e['cx'],
                'name'  => $e['name'],
                'desc'  => $e['desc'] ?? '',
            ];
        }, $engines);
    }

    /**
     * Map engines to very old "API|CX" flat key format.
     *
     * @param array<int,array{name:string,cx:string,api:string,desc:string}> $engines
     * @return array<int,string>
     */
    protected static function toLegacyKeys(array $engines)
    {
        return array_map(static function ($e) {
            return $e['api'] . '|' . $e['cx'];
        }, $engines);
    }

    /* =========================
     *  Env helpers
     * ========================= */


    /**
     * Check if plugin is available
     *
     * @param string $plugin Plugin
     * @return boolean True if plugin is available
     */
    protected function pluginExists($plugin)
    {
        return Helper::pluginExists($plugin);
    }

    /**
     * Check if plugin is active
     *
     * @param string $plugin Plugin
     * @return boolean True if plugin is active
     */
    protected function isPluginActive($plugin)
    {
        return Helper::isPluginActive($plugin);
    }
}