<?php

namespace RRZE\Settings\Plugins;

defined('ABSPATH') || exit;

use RRZE\Settings\Library\Network\IPUtils;

/**
 * Siteimprove class
 * 
 * @link https://github.com/RRZE-Webteam/rrze-siteimprove
 * @package RRZE\Settings\Plugins
 */
class Siteimprove
{
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
        // Siteimprove crawler IP addresses filter
        add_filter('rrze_ac_siteimprove_crawler_ip_addresses', [$this, 'siteimproveCrawlerIpAddresses']);
        add_filter('rrze_private_site_siteimprove_crawler_ip_addresses', [$this, 'siteimproveCrawlerIpAddresses']);
    }

    /**
     * Siteimprove crawler IP addresses
     * 
     * @param mixed $ipAddresses
     * @return array
     */
    public function siteimproveCrawlerIpAddresses($ipAddresses): array
    {
        $ipRange = [];
        $ipAddresses = (array) $ipAddresses;
        $option = $this->siteOptions->plugins->siteimprove_crawler_ip_addresses;
        if (empty($option) || !is_array($option)) {
            return $ipAddresses;
        }
        $ipAddresses = array_merge($ipAddresses, $this->getIpRange($option));
        $ipAddresses = array_filter(array_map('trim', $ipAddresses));
        $ipAddresses = array_unique(array_values($ipAddresses));
        if (!empty($ipAddresses)) {
            $ipRange = self::getIpRange($ipAddresses);
        }
        return $ipRange;
    }

    /**
     * Get IP range
     * 
     * @param array $ipAddress
     * @return array
     */
    protected static function getIpRange(array $ipAddress): array
    {
        $ipRange = [];
        if (!empty($ipAddress)) {
            foreach ($ipAddress as $value) {
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                $sanitized_value = IPUtils::sanitizeIpRange($value);
                if (!is_null($sanitized_value)) {
                    $ipRange[] = $sanitized_value;
                }
            }
        }
        return $ipRange;
    }
}
