<?php

namespace RRZE\Settings\Crawlers;

defined('ABSPATH') || exit;

use RRZE\Settings\Config;
use RRZE\Settings\Library\Network\IPUtils;

/**
 * Crawler directory defaults and normalization.
 *
 * @package RRZE\Settings\Crawlers
 */
class Defaults
{
    /**
     * Get predefined crawler entries.
     *
     * @return array
     */
    public static function getDefaults(): array
    {
        return Config::get('crawlers');
    }

    /**
     * Check whether a crawler key belongs to the default configuration.
     *
     * @param string $key
     * @return bool
     */
    public static function isDefaultCrawler(string $key): bool
    {
        $key = sanitize_key($key);
        $defaults = self::getDefaults();

        return isset($defaults[$key]);
    }

    /**
     * Normalize the crawler option directory.
     *
     * @param array $directory
     * @param mixed $legacySiteimproveIpAddresses
     * @return object
     */
    public static function normalizeDirectory(array $directory, $legacySiteimproveIpAddresses = []): object
    {
        $entries = [];
        $rawEntries = isset($directory['entries']) && (is_array($directory['entries']) || is_object($directory['entries']))
            ? (array) $directory['entries']
            : [];

        foreach (self::getDefaults() as $key => $crawler) {
            $entries[$key] = self::normalizeCrawler($key, $crawler);
        }

        // Legacy settings override defaults; saved crawler entries take precedence below.
        if (!empty($legacySiteimproveIpAddresses)) {
            $entries['siteimprove']['ip_addresses'] = self::sanitizeIpAddresses($legacySiteimproveIpAddresses);
        }

        foreach ($rawEntries as $key => $crawler) {
            $key = sanitize_key((string) $key);
            if ($key === '') {
                continue;
            }

            $entries[$key] = self::normalizeCrawler($key, $crawler);
        }

        uasort($entries, [self::class, 'sortByTitle']);

        return (object) [
            'entries' => $entries,
        ];
    }

    /**
     * Get normalized crawler entries.
     *
     * @param object $siteOptions
     * @return array
     */
    public static function getEntries($siteOptions): array
    {
        if (empty($siteOptions->crawlers->entries) || (!is_array($siteOptions->crawlers->entries) && !is_object($siteOptions->crawlers->entries))) {
            return [];
        }

        return (array) $siteOptions->crawlers->entries;
    }

    /**
     * Get a crawler by key.
     *
     * @param object $siteOptions
     * @param string $key
     * @return array
     */
    public static function getCrawler($siteOptions, string $key): array
    {
        $key = sanitize_key($key);
        $entries = self::getEntries($siteOptions);

        return $entries[$key] ?? [];
    }

    /**
     * Get crawler IP addresses.
     *
     * @param object $siteOptions
     * @param string $key
     * @return array
     */
    public static function getIpAddresses($siteOptions, string $key = ''): array
    {
        $entries = self::getEntries($siteOptions);
        $ipAddresses = [];

        if ($key !== '') {
            $crawler = self::getCrawler($siteOptions, $key);
            return !empty($crawler['ip_addresses']) && is_array($crawler['ip_addresses']) ? $crawler['ip_addresses'] : [];
        }

        foreach ($entries as $crawler) {
            if (empty($crawler['ip_addresses']) || !is_array($crawler['ip_addresses'])) {
                continue;
            }

            $ipAddresses = array_merge($ipAddresses, $crawler['ip_addresses']);
        }

        return array_values(array_unique($ipAddresses));
    }

    /**
     * Normalize a single crawler entry.
     *
     * @param string $key
     * @param mixed $crawler
     * @return array
     */
    public static function normalizeCrawler(string $key, $crawler): array
    {
        $crawler = (array) $crawler;
        $title = isset($crawler['title']) ? sanitize_text_field((string) $crawler['title']) : '';
        $userAgent = isset($crawler['user_agent']) ? sanitize_text_field((string) $crawler['user_agent']) : '';
        $contactEmail = isset($crawler['contact_email']) ? sanitize_email((string) $crawler['contact_email']) : '';
        $contactUrl = isset($crawler['contact_url']) ? esc_url_raw((string) $crawler['contact_url']) : '';
        $notes = isset($crawler['notes']) ? sanitize_textarea_field((string) $crawler['notes']) : '';

        if ($title === '') {
            $title = $key;
        }

        if ($contactEmail !== '' && !is_email($contactEmail)) {
            $contactEmail = '';
        }

        return [
            'title' => $title,
            'user_agent' => $userAgent,
            'ip_addresses' => self::sanitizeIpAddresses($crawler['ip_addresses'] ?? []),
            'contact_email' => $contactEmail,
            'contact_url' => $contactUrl,
            'notes' => $notes,
        ];
    }

    /**
     * Sanitize IP address ranges.
     *
     * @param mixed $ipAddresses
     * @return array
     */
    public static function sanitizeIpAddresses($ipAddresses): array
    {
        if (is_string($ipAddresses)) {
            $ipAddresses = preg_split('/\R/', $ipAddresses);
        }

        if (!is_array($ipAddresses)) {
            return [];
        }

        $sanitized = [];
        foreach ($ipAddresses as $ipAddress) {
            $ipAddress = trim((string) $ipAddress);
            if ($ipAddress === '') {
                continue;
            }

            $ipAddress = self::expandPartialIpAddress($ipAddress);
            $ipRange = IPUtils::sanitizeIpRange($ipAddress);
            if ($ipRange === null) {
                continue;
            }

            $sanitized[] = $ipRange;
        }

        sort($sanitized, SORT_NATURAL);

        return array_values(array_unique($sanitized));
    }

    /**
     * Expand partial IPv4 and IPv6 prefixes to CIDR notation.
     *
     * @param string $ipAddress
     * @return string
     */
    protected static function expandPartialIpAddress(string $ipAddress): string
    {
        if (str_ends_with($ipAddress, '.')) {
            return self::expandPartialIpv4Address($ipAddress);
        }

        if (str_ends_with($ipAddress, ':') && !str_ends_with($ipAddress, '::')) {
            return self::expandPartialIpv6Address($ipAddress);
        }

        return $ipAddress;
    }

    /**
     * Expand a partial IPv4 prefix like 131.188. to 131.188.0.0/16.
     *
     * @param string $ipAddress
     * @return string
     */
    protected static function expandPartialIpv4Address(string $ipAddress): string
    {
        $octets = array_values(array_filter(explode('.', $ipAddress), 'strlen'));
        $octetCount = count($octets);

        if ($octetCount < 1 || $octetCount > 3) {
            return $ipAddress;
        }

        foreach ($octets as $octet) {
            if (!ctype_digit($octet) || (int) $octet > 255) {
                return $ipAddress;
            }
        }

        $expandedOctets = array_pad($octets, 4, '0');

        return implode('.', $expandedOctets) . '/' . ($octetCount * 8);
    }

    /**
     * Expand a partial IPv6 prefix like 2001:db8: to 2001:db8::/32.
     *
     * @param string $ipAddress
     * @return string
     */
    protected static function expandPartialIpv6Address(string $ipAddress): string
    {
        $groups = array_values(array_filter(explode(':', $ipAddress), 'strlen'));
        $groupCount = count($groups);

        if ($groupCount < 1 || $groupCount > 7) {
            return $ipAddress;
        }

        foreach ($groups as $group) {
            if (!preg_match('/^[0-9a-fA-F]{1,4}$/', $group)) {
                return $ipAddress;
            }
        }

        return implode(':', $groups) . '::/' . ($groupCount * 16);
    }

    /**
     * Sort crawlers by title.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected static function sortByTitle(array $a, array $b): int
    {
        return strnatcasecmp($a['title'] ?? '', $b['title'] ?? '');
    }
}
