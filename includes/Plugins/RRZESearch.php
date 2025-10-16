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
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
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