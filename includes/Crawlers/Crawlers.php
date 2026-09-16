<?php

namespace RRZE\Settings\Crawlers;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use RRZE\Settings\Options;

/**
 * Crawlers class.
 *
 * @package RRZE\Settings\Crawlers
 */
class Crawlers extends Main
{
    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded()
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        add_filter('rrze_settings_crawlers', [$this, 'filterCrawlers']);
        add_filter('rrze_settings_crawler', [$this, 'filterCrawler'], 10, 2);
        add_filter('rrze_settings_crawler_ip_addresses', [$this, 'filterCrawlerIpAddresses'], 10, 2);
        add_action('init', [$this, 'maybeMigrateCrawlerDirectory'], 1);
    }

    /**
     * Persist the crawler directory for existing installations.
     *
     * @return void
     */
    public function maybeMigrateCrawlerDirectory(): void
    {
        if (!is_multisite()) {
            return;
        }

        $rawOptions = get_site_option(Options::OPTION_NAME);
        if (empty($rawOptions) || !is_array($rawOptions) && !is_object($rawOptions)) {
            return;
        }

        $rawOptions = (array) $rawOptions;
        if (!empty($rawOptions['crawlers'])) {
            return;
        }

        $options = Options::parseOptions($rawOptions);
        $rawOptions['crawlers'] = $options->crawlers;
        $rawOptions['plugins'] = $options->plugins;

        update_site_option(Options::OPTION_NAME, (object) $rawOptions);
    }

    /**
     * Provide all configured crawlers.
     *
     * @param mixed $crawlers
     * @return array
     */
    public function filterCrawlers($crawlers): array
    {
        $crawlers = is_array($crawlers) ? $crawlers : [];

        return array_replace($crawlers, Defaults::getEntries($this->siteOptions));
    }

    /**
     * Provide one configured crawler.
     *
     * @param mixed $crawler
     * @param string $crawlerKey
     * @return array
     */
    public function filterCrawler($crawler, string $crawlerKey): array
    {
        $crawler = is_array($crawler) ? $crawler : [];
        $configuredCrawler = Defaults::getCrawler($this->siteOptions, $crawlerKey);

        if (empty($configuredCrawler)) {
            return $crawler;
        }

        return array_replace($crawler, $configuredCrawler);
    }

    /**
     * Provide IP addresses for one crawler, or all crawler IP addresses when no key is given.
     *
     * @param mixed $ipAddresses
     * @param string $crawlerKey
     * @return array
     */
    public function filterCrawlerIpAddresses($ipAddresses, string $crawlerKey = ''): array
    {
        $ipAddresses = is_array($ipAddresses) ? $ipAddresses : [];
        $configuredIpAddresses = Defaults::getIpAddresses($this->siteOptions, $crawlerKey);
        $ipAddresses = array_merge($ipAddresses, $configuredIpAddresses);
        $ipAddresses = array_filter(array_map('trim', $ipAddresses));

        return array_values(array_unique($ipAddresses));
    }
}
