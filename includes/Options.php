<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

use RRZE\Settings\Crawlers\Defaults as CrawlerDefaults;

/**
 * Options class
 * 
 * @package RRZE\Settings
 */
class Options
{
    /**
     * @var string
     */
    const OPTION_NAME = 'rrze_settings';

    /**
     * Default options
     * 
     * @return array
     */
    protected static function defaultOptions(): array
    {
        return Config::get('options');
    }

    /**
     * Returns the default options
     * 
     * @return object
     */
    public static function getDefaultOptions(): object
    {
        $options = self::defaultOptions();
        return self::parseOptions($options);
    }

    /**
     * Returns the options
     * 
     * @param  boolean $network Is it a network option?
     * @return object Parsed options
     */
    public static function getOptions(bool $network = false): object
    {
        if ($network) {
            $options = (array) get_site_option(self::OPTION_NAME);
        } else {
            $options = (array) get_option(self::OPTION_NAME);
        }

        return self::parseOptions($options);
    }

    /**
     * Returns the site options
     * 
     * @return object
     */
    public static function getSiteOptions(): object
    {
        return self::getOptions(true);
    }

    /**
     * Returns the name of the option
     * 
     * @return string
     */
    public static function getOptionName(): string
    {
        return self::OPTION_NAME;
    }

    /**
     * Returns parsed options
     * 
     * @param  array $options Options to parse
     * @return object Parsed options
     */
    public static function parseOptions(array $options): object
    {
        $defaults = self::defaultOptions();
        $options = wp_parse_args($options, $defaults);
        $options = (object) array_intersect_key($options, $defaults);
        foreach ($defaults as $key => $value) {
            if (is_array($value)) {
                $options->$key = wp_parse_args($options->$key, $value);
                $options->$key = (object) array_intersect_key($options->$key, $value);
            }
        }
        if (isset($options->plugins->siteimprove) && is_array($defaults['plugins']['siteimprove'])) {
            $options->plugins->siteimprove = (object) wp_parse_args(
                (array) $options->plugins->siteimprove,
                $defaults['plugins']['siteimprove']
            );
        }

        $options->crawlers = CrawlerDefaults::normalizeDirectory(
            (array) ($options->crawlers ?? []),
            $options->plugins->siteimprove_crawler_ip_addresses ?? []
        );
        $siteimproveIpAddresses = CrawlerDefaults::getIpAddresses($options, 'siteimprove');
        if (!empty($siteimproveIpAddresses)) {
            $options->plugins->siteimprove_crawler_ip_addresses = $siteimproveIpAddresses;
        }

        return $options;
    }
}
