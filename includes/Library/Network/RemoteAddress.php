<?php

namespace RRZE\Settings\Library\Network;

use RRZE\Settings\Library\Network\IP;

defined('ABSPATH') || exit;

/**
 * RemoteAddress class
 * @package RRZE\Settings\Library\Network
 */
class RemoteAddress
{
    /**
     * Get the remote IP address
     * @return string
     */
    public function getIpAddress()
    {
        $ipStr = $this->getIpAddressFromProxy();
        if ($ipStr) {
            return $ipStr;
        }

        // Remote IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * Get the remote IP address from the proxy
     * @return string|false
     */
    protected function getIpAddressFromProxy()
    {
        if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return false;
        }

        $ips_ary = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));

        if (empty($this->getProxies($ips_ary))) {
            return false;
        }

        // The right-most IP address is always the IP address that connects to
        // the last proxy, which means it is the most reliable source of information.
        // @see https://en.wikipedia.org/wiki/X-Forwarded-For
        $ipStr = array_pop($ips_ary);
        return $ipStr;
    }

    /**
     * Get the proxies from the IP addresses
     * @param array $ips_ary Array of IP addresses
     * @return array
     */
    protected function getProxies($ips_ary = [])
    {
        $proxies = [];

        foreach ($ips_ary as $ipStr) {
            $ip = IP::fromStringIP($ipStr);
            $host = $ip->getHostname();
            if ($host === null) {
                continue;
            }
            $proxies[] = $ipStr;
        }

        return $proxies;
    }
}
