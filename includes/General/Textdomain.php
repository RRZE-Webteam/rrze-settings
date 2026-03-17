<?php

declare(strict_types=1);

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * Textdomain fallback loader for PHP and JS translations.
 *
 * Supports:
 * - .mo
 * - .l10n.php
 * - Gutenberg script translation JSON files:
 *   domain-locale-hash.json
 *
 * Fallback order:
 * 1) Simplified locale variant (de_DE_formal -> de_DE)
 * 2) Other same-language locales from installed/allowed pool
 * 3) Language-only locale (de)
 * 4) Same-language last-resort locale (de_DE)
 * 5) Global English fallback (en_GB / en_US)
 */
class Textdomain
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * Cache fallback chains per locale.
     *
     * @var array<string, array<int, string>>
     */
    private array $fallbackChainCache = [];

    /**
     * Cache resolved candidate file per requested file+locale.
     *
     * @var array<string, string>
     */
    private array $resolvedFileCache = [];

    /**
     * Cache locale pool per request.
     *
     * @var array<int, string>|null
     */
    private ?array $installedLocalesPool = null;

    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Register filters.
     *
     * Call this on/after plugins_loaded.
     *
     * @return void
     */
    public function loaded(): void
    {
        if (empty($this->siteOptions->general->textdomain_fallback)) {
            return;
        }

        // Legacy PHP translations (.mo).
        add_filter('load_textdomain_mofile', [$this, 'filterMoFile'], 10, 2);

        // Modern PHP translations (.mo and .l10n.php).
        add_filter('load_translation_file', [$this, 'filterTranslationFile'], 10, 3);

        // Script translations (Gutenberg / JS JSON).
        add_filter('load_script_translation_file', [$this, 'filterScriptTranslationFile'], 10, 3);
    }

    /**
     * Legacy filter for MO files only.
     *
     * @param string $mofile
     * @param string $domain
     * @return string
     */
    public function filterMoFile($mofile, $domain): string
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
    public function filterTranslationFile($file, $domain, $locale): string
    {
        return $this->applyLocaleFallback((string) $file, (string) $locale, (string) $domain);
    }

    /**
     * Filter for script translation JSON files.
     *
     * Signature:
     * load_script_translation_file( string|false $file, string $handle, string $domain )
     *
     * Locale is not passed directly here, so we resolve it from the current request.
     *
     * @param string|false $file
     * @param string       $handle
     * @param string       $domain
     * @return string|false
     */
    public function filterScriptTranslationFile($file, $handle, $domain)
    {
        if ($file === false || $file === '') {
            return $file;
        }

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

        return $this->applyLocaleFallback((string) $file, (string) $locale, (string) $domain);
    }

    /**
     * Apply locale fallback chain if the requested file is not readable.
     *
     * @param string $file
     * @param string $locale
     * @param string $domain
     * @return string
     */
    private function applyLocaleFallback(string $file, string $locale, string $domain): string
    {
        unset($domain); // reserved for future domain-specific logic

        if ($file === '' || $locale === '') {
            return $file;
        }

        if (is_readable($file)) {
            return $file;
        }

        $cacheKey = $file . '|' . $locale;

        if (isset($this->resolvedFileCache[$cacheKey])) {
            return $this->resolvedFileCache[$cacheKey];
        }

        $dir  = dirname($file);
        $base = basename($file);

        foreach ($this->getFallbackLocales($locale) as $fallbackLocale) {
            $candidateBase = $this->replaceLocaleInFilename($base, $locale, $fallbackLocale);

            if ($candidateBase === $base) {
                continue;
            }

            $candidate = $dir . DIRECTORY_SEPARATOR . $candidateBase;

            if (is_readable($candidate)) {
                $this->resolvedFileCache[$cacheKey] = $candidate;
                return $candidate;
            }
        }

        $this->resolvedFileCache[$cacheKey] = $file;

        return $file;
    }

    /**
     * Return ordered fallback locales for a requested locale.
     *
     * @param string $locale
     * @return string[]
     */
    private function getFallbackLocales(string $locale): array
    {
        if ($locale === '') {
            return [];
        }

        if (isset($this->fallbackChainCache[$locale])) {
            return $this->fallbackChainCache[$locale];
        }

        $pool      = $this->getInstalledLocalesPool();
        $lang      = $this->getLanguageFromLocale($locale);
        $fallbacks = [];

        // 1) Simplified locale, e.g. de_DE_formal -> de_DE
        $simplifiedLocale = $this->getSimplifiedLocale($locale);
        if ($simplifiedLocale !== '' && $simplifiedLocale !== $locale) {
            $fallbacks[] = $simplifiedLocale;
        }

        // 2) Other same-language locales from pool
        foreach ($pool as $poolLocale) {
            if ($poolLocale === '' || $poolLocale === $locale || $poolLocale === $simplifiedLocale) {
                continue;
            }

            if ($lang !== '' && strpos($poolLocale, $lang . '_') === 0) {
                $fallbacks[] = $poolLocale;
            }
        }

        // 3) Language-only fallback
        if ($lang !== '' && $lang !== $locale) {
            $fallbacks[] = $lang;
        }

        // 4) Same-language last-resort map
        foreach ($this->getLastResortFallbacks($locale) as $lastResortLocale) {
            if ($lastResortLocale !== $locale) {
                $fallbacks[] = $lastResortLocale;
            }
        }

        // 5) Global English fallback
        foreach ($this->getGlobalFallbackLocales() as $globalFallbackLocale) {
            if ($globalFallbackLocale !== $locale) {
                $fallbacks[] = $globalFallbackLocale;
            }
        }

        $fallbacks = $this->deduplicateLocales($fallbacks, $locale);
        $this->fallbackChainCache[$locale] = $fallbacks;

        return $fallbacks;
    }

    /**
     * Pool of installed/allowed locales.
     *
     * @return string[]
     */
    private function getInstalledLocalesPool(): array
    {
        if (is_array($this->installedLocalesPool)) {
            return $this->installedLocalesPool;
        }

        $fromSettings = $this->getLocalesFromSettings();
        $fromWP       = function_exists('get_available_languages') ? (array) get_available_languages() : [];
        $siteLocale   = function_exists('determine_locale') ? determine_locale() : get_locale();

        $pool = array_merge($fromSettings, $fromWP, [$siteLocale]);

        $this->installedLocalesPool = $this->deduplicateLocales($pool);

        return $this->installedLocalesPool;
    }

    /**
     * Read locales from plugin settings.
     *
     * Supports:
     * - array: ['de_DE', 'en_GB']
     * - string: 'de_DE,en_GB,es_ES'
     *
     * @return string[]
     */
    private function getLocalesFromSettings(): array
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
     * Supported patterns:
     * - domain-de_DE.mo
     * - domain-de_DE.l10n.php
     * - domain_de_DE.mo
     * - domain-de_DE-<hash>.json
     * - domain-de_DE.json
     *
     * @param string $base
     * @param string $locale
     * @param string $fallbackLocale
     * @return string
     */
    private function replaceLocaleInFilename(string $base, string $locale, string $fallbackLocale): string
    {
        if ($base === '' || $locale === '' || $fallbackLocale === '' || $locale === $fallbackLocale) {
            return $base;
        }

        // Most common PHP translation naming.
        $needle = '-' . $locale . '.';
        if (strpos($base, $needle) !== false) {
            return str_replace($needle, '-' . $fallbackLocale . '.', $base);
        }

        // Alternative underscore naming.
        $needle2 = '_' . $locale . '.';
        if (strpos($base, $needle2) !== false) {
            return str_replace($needle2, '_' . $fallbackLocale . '.', $base);
        }

        // Gutenberg/JS JSON with hash: domain-locale-hash.json
        $needle3 = '-' . $locale . '-';
        if (strpos($base, $needle3) !== false) {
            return str_replace($needle3, '-' . $fallbackLocale . '-', $base);
        }

        // JSON without hash: domain-locale.json
        $needle4 = '-' . $locale . '.json';
        if (strpos($base, $needle4) !== false) {
            return str_replace($needle4, '-' . $fallbackLocale . '.json', $base);
        }

        // Very last fallback.
        return str_replace($locale, $fallbackLocale, $base);
    }

    /**
     * Extract language code from locale.
     *
     * @param string $locale
     * @return string
     */
    private function getLanguageFromLocale(string $locale): string
    {
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
     * Simplify locale by removing the last segment if there are more than 2.
     *
     * Examples:
     * - de_DE_formal -> de_DE
     * - ca_ES_valencia -> ca_ES
     * - de_DE -> de_DE
     * - de -> de
     *
     * @param string $locale
     * @return string
     */
    private function getSimplifiedLocale(string $locale): string
    {
        if ($locale === '') {
            return '';
        }

        $parts = explode('_', $locale);

        if (count($parts) <= 2) {
            return $locale;
        }

        array_pop($parts);

        return implode('_', $parts);
    }

    /**
     * Same-language last-resort fallback suggestions.
     *
     * @param string $locale
     * @return string[]
     */
    private function getLastResortFallbacks(string $locale): array
    {
        if (strpos($locale, 'de_') === 0 || $locale === 'de') {
            return ['de_DE'];
        }

        if (strpos($locale, 'es_') === 0 || $locale === 'es') {
            return ['es_ES'];
        }

        if (strpos($locale, 'en_') === 0 || $locale === 'en') {
            return $this->getPreferredEnglishLocales();
        }

        if (strpos($locale, 'fr_') === 0 || $locale === 'fr') {
            return ['fr_FR'];
        }

        if (strpos($locale, 'it_') === 0 || $locale === 'it') {
            return ['it_IT'];
        }

        if (strpos($locale, 'pt_') === 0 || $locale === 'pt') {
            return ['pt_PT', 'pt_BR'];
        }

        return [];
    }

    /**
     * Global fallback locales used as final fallback.
     *
     * @return string[]
     */
    private function getGlobalFallbackLocales(): array
    {
        return $this->getPreferredEnglishLocales();
    }

    /**
     * Return preferred English locale order.
     *
     * Supported setting examples:
     * - en_GB
     * - en_US
     *
     * Default: en_GB first, then en_US
     *
     * @return string[]
     */
    private function getPreferredEnglishLocales(): array
    {
        $preferred = $this->siteOptions->general->english_fallback_locale ?? 'en_GB';
        $preferred = is_string($preferred) ? trim($preferred) : 'en_GB';

        if ($preferred === 'en_US') {
            return ['en_US', 'en_GB'];
        }

        return ['en_GB', 'en_US'];
    }

    /**
     * Deduplicate locales preserving order.
     *
     * @param array $locales
     * @param string $excludeLocale
     * @return string[]
     */
    private function deduplicateLocales(array $locales, string $excludeLocale = ''): array
    {
        $out = [];

        foreach ($locales as $locale) {
            $locale = is_string($locale) ? trim($locale) : '';

            if ($locale === '' || $locale === $excludeLocale) {
                continue;
            }

            if (!in_array($locale, $out, true)) {
                $out[] = $locale;
            }
        }

        return $out;
    }
}
