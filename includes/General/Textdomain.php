<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * Textdomain fallback loader (supports .mo and .l10n.php)
 *
 * - Works with legacy MO filter (load_textdomain_mofile)
 * - Works with modern WP translation loader (load_translation_file) for .l10n.php + .mo
 * - Fallback locales are derived from:
 *   1) Plugin settings (allowed/installed languages list)
 *   2) WP installed languages (get_available_languages)
 *   3) A small last-resort map (de_*, es_*, en_*) if no pool exists
 */
class Textdomain
{
    /**
     * @var object
     */
    protected $siteOptions;

    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Register filters.
     *
     * Call this on/after plugins_loaded.
     */
    public function loaded()
    {
        if (empty($this->siteOptions->general->textdomain_fallback)) {
            return;
        }

        // Legacy: affects only .mo.
        add_filter('load_textdomain_mofile', [$this, 'filterMoFile'], 10, 2);

        // Modern: affects .mo and .l10n.php.
        add_filter('load_translation_file', [$this, 'filterTranslationFile'], 10, 3);
    }

    /**
     * Legacy filter for MO files only.
     *
     * @param string $mofile
     * @param string $domain
     * @return string
     */
    public function filterMoFile($mofile, $domain)
    {
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        return $this->applyLocaleFallback((string) $mofile, (string) $locale, (string) $domain);
    }

    /**
     * Modern filter for translation files (.mo or .l10n.php).
     *
     * @param string $file
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public function filterTranslationFile($file, $domain, $locale)
    {
        return $this->applyLocaleFallback((string) $file, (string) $locale, (string) $domain);
    }

    /**
     * Apply locale fallback chain if the requested file does not exist/readable.
     *
     * @param string $file   Translation file path (.mo or .l10n.php).
     * @param string $locale Requested locale.
     * @param string $domain Textdomain (for context / future use).
     * @return string
     */
    private function applyLocaleFallback($file, $locale, $domain)
    {
        if ($file === '' || $locale === '') {
            return $file;
        }

        if (is_readable($file)) {
            return $file;
        }

        $dir  = dirname($file);
        $base = basename($file);

        // Build fallback list based on installed/allowed languages.
        $fallbackLocales = $this->getFallbackLocalesFromInstalledPool($locale);

        // Try each fallback locale and return the first existing file.
        foreach ($fallbackLocales as $fallbackLocale) {
            $candidateBase = $this->replaceLocaleInFilename($base, $locale, $fallbackLocale);

            // If we couldn't produce a different filename, skip.
            if ($candidateBase === $base) {
                continue;
            }

            $candidate = $dir . DIRECTORY_SEPARATOR . $candidateBase;

            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return $file;
    }

    /**
     * Build an ordered fallback chain using:
     *  1) plugin settings languages (if present)
     *  2) WP installed languages (get_available_languages)
     *  3) last-resort mapping if pool is empty
     *
     * For a requested locale like "en_UK":
     *  - prefer other installed locales with same language "en_*" (in pool order)
     *  - then try language-only "en" (rare but harmless)
     *
     * @param string $locale
     * @return string[]
     */
    private function getFallbackLocalesFromInstalledPool($locale)
    {
        $locale = (string) $locale;
        $pool   = $this->getInstalledLocalesPool();

        $lang = $this->getLanguageFromLocale($locale);

        $fallbacks = [];

        // 1) same-language regional fallbacks from pool, excluding original locale
        foreach ($pool as $l) {
            if (!is_string($l) || $l === '' || $l === $locale) {
                continue;
            }
            if (strpos($l, $lang . '_') === 0) {
                $fallbacks[] = $l;
            }
        }

        // 2) language-only
        if ($lang !== '' && $lang !== $locale) {
            $fallbacks[] = $lang;
        }

        // 3) If pool empty (or no same-language options), use a small last-resort map
        if (empty($fallbacks)) {
            foreach ($this->getLastResortFallbacks($locale) as $lr) {
                if ($lr !== $locale) {
                    $fallbacks[] = $lr;
                }
            }
            if ($lang !== '' && $lang !== $locale) {
                $fallbacks[] = $lang;
            }
        }

        // Deduplicate while preserving order.
        $out = [];
        foreach ($fallbacks as $f) {
            if (!is_string($f) || $f === '' || $f === $locale) {
                continue;
            }
            if (!in_array($f, $out, true)) {
                $out[] = $f;
            }
        }

        return $out;
    }

    /**
     * Pool of "installed/allowed" locales.
     *
     * Adjust the settings key if your plugin stores it differently.
     *
     * @return string[]
     */
    private function getInstalledLocalesPool()
    {
        $fromSettings = $this->getLocalesFromSettings();
        $fromWP       = function_exists('get_available_languages') ? (array) get_available_languages() : [];

        // Also consider site locale (often not in get_available_languages for default en_US edge cases)
        $siteLocale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $extras     = [$siteLocale];

        $pool = array_merge($fromSettings, $fromWP, $extras);

        // Normalize + dedupe
        $out = [];
        foreach ($pool as $l) {
            $l = is_string($l) ? trim($l) : '';
            if ($l === '') {
                continue;
            }
            if (!in_array($l, $out, true)) {
                $out[] = $l;
            }
        }

        return $out;
    }

    /**
     * Read locales from plugin settings.
     *
     * Supports:
     * - array: ['de_DE', 'en_GB', ...]
     * - string: 'de_DE,en_GB,es_ES'
     *
     * Change this method to match your real options structure if needed.
     *
     * @return string[]
     */
    private function getLocalesFromSettings()
    {
        $val = $this->siteOptions->general->languages ?? null;

        if (is_array($val)) {
            return array_values(array_filter(array_map('trim', $val)));
        }

        if (is_string($val) && $val !== '') {
            $parts = preg_split('/[\s,;]+/', $val);
            if (is_array($parts)) {
                return array_values(array_filter(array_map('trim', $parts)));
            }
        }

        return [];
    }

    /**
     * Replace locale in filename safely.
     *
     * Typical filenames:
     *  - domain-de_DE.mo
     *  - domain-de_DE.l10n.php
     *  - some/path/domain-de_DE-1234.l10n.php (rare)
     *
     * We try:
     *  1) Replace "-<locale>." with "-<fallbackLocale>."
     *  2) Else fallback to simple str_replace (only within base)
     *
     * @param string $base
     * @param string $locale
     * @param string $fallbackLocale
     * @return string
     */
    private function replaceLocaleInFilename($base, $locale, $fallbackLocale)
    {
        $base = (string) $base;
        $locale = (string) $locale;
        $fallbackLocale = (string) $fallbackLocale;

        if ($base === '' || $locale === '' || $fallbackLocale === '' || $locale === $fallbackLocale) {
            return $base;
        }

        // Prefer replacing -LOCALE. (most common WordPress naming)
        $needle = '-' . $locale . '.';
        if (strpos($base, $needle) !== false) {
            return str_replace($needle, '-' . $fallbackLocale . '.', $base);
        }

        // Some edge cases include _LOCALE (less common), try it too.
        $needle2 = '_' . $locale . '.';
        if (strpos($base, $needle2) !== false) {
            return str_replace($needle2, '_' . $fallbackLocale . '.', $base);
        }

        // Fallback: replace any occurrence (still only within filename).
        return str_replace($locale, $fallbackLocale, $base);
    }

    /**
     * Extract language code from locale.
     *
     * @param string $locale
     * @return string
     */
    private function getLanguageFromLocale($locale)
    {
        $locale = (string) $locale;
        if ($locale === '') {
            return '';
        }

        if (strpos($locale, '_') !== false) {
            $lang = strtok($locale, '_');
            return is_string($lang) ? $lang : '';
        }

        return $locale;
    }

    /**
     * Last-resort fallback suggestions if no installed pool exists.
     *
     * @param string $locale
     * @return string[]
     */
    private function getLastResortFallbacks($locale)
    {
        if (strpos($locale, 'de_') === 0) {
            return ['de_DE'];
        }
        if (strpos($locale, 'es_') === 0) {
            return ['es_ES'];
        }
        if (strpos($locale, 'en_') === 0) {
            // Many installs prefer en_GB; then en_US.
            return ['en_GB', 'en_US'];
        }

        return [];
    }
}