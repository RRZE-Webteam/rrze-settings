<?php

namespace RRZE\Settings\Library\Network;

defined('ABSPATH') || exit;

// https://github.com/piwik/component-network

/**
 * IP address.
 *
 * This class is immutable, i.e. once created it can't be changed. Methods that modify it
 * will always return a new instance.
 */
abstract class IP
{
    /**
     * Binary representation of the IP.
     *
     * @var string
     */
    protected $ip;

    /**
     * @see fromBinaryIP
     * @see fromStringIP
     *
     * @param string $ip Binary representation of the IP.
     */
    protected function __construct($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Factory method to create an IP instance from an IP in binary format.
     *
     * @see fromStringIP
     *
     * @param string $ip IP address in a binary format.
     * @return IP
     */
    public static function fromBinaryIP($ip)
    {
        if ($ip === NULL || $ip === '') {
            return new IPv4("\x00\x00\x00\x00");
        }

        if (self::isIPv4($ip)) {
            return new IPv4($ip);
        }

        return new IPv6($ip);
    }

    /**
     * Factory method to create an IP instance from an IP represented as string.
     *
     * @see fromBinaryIP
     *
     * @param string $ip IP address in a string format (X.X.X.X).
     * @return IP
     */
    public static function fromStringIP($ip)
    {
        return self::fromBinaryIP(IPUtils::stringToBinaryIP($ip));
    }

    /**
     * Returns the IP address in a binary format.
     *
     * @return string
     */
    public function toBinary()
    {
        return $this->ip;
    }

    /**
     * Returns the IP address in a string format (X.X.X.X).
     *
     * @return string
     */
    public function toString()
    {
        return IPUtils::binaryToStringIP($this->ip);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Tries to return the hostname associated to the IP.
     *
     * @return string|NULL The hostname or NULL if the hostname can't be resolved.
     */
    public function getHostname()
    {
        $stringIp = $this->toString();

        $host = strtolower(@gethostbyaddr($stringIp));

        if ($host === '' || $host === $stringIp) {
            return NULL;
        }

        return $host;
    }

    /**
     * Determines if the IP address is in a specified IP address range.
     *
     * An IPv4-mapped address should be range checked with an IPv4-mapped address range.
     *
     * @param array|string $ipRange IP address range (string or array containing min and max IP addresses)
     * @return bool
     */
    public function isInRange($ipRange)
    {
        $ipLen = strlen($this->ip);
        if (empty($this->ip) || empty($ipRange) || ($ipLen != 4 && $ipLen != 16)) {
            return FALSE;
        }

        if (is_array($ipRange)) {
            // already split into low/high IP addresses
            $ipRange[0] = IPUtils::stringToBinaryIP($ipRange[0]);
            $ipRange[1] = IPUtils::stringToBinaryIP($ipRange[1]);
        } else {
            // expect CIDR format but handle some variations
            $ipRange = IPUtils::getIPRangeBounds($ipRange);
        }
        if ($ipRange === NULL) {
            return FALSE;
        }

        $low = $ipRange[0];
        $high = $ipRange[1];
        if (strlen($low) != $ipLen) {
            return FALSE;
        }

        // binary-safe string comparison
        if ($this->ip >= $low && $this->ip <= $high) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Determines if the IP address is in a specified IP address range.
     *
     * An IPv4-mapped address should be range checked with IPv4-mapped address ranges.
     *
     * @param array $ipRanges List of IP address ranges (strings or arrays containing min and max IP addresses).
     * @return bool True if in any of the specified IP address ranges; FALSE otherwise.
     */
    public function isInRanges(array $ipRanges)
    {
        $ipLen = strlen($this->ip);
        if (empty($this->ip) || empty($ipRanges) || ($ipLen != 4 && $ipLen != 16)) {
            return FALSE;
        }

        foreach ($ipRanges as $ipRange) {
            if ($this->isInRange($ipRange)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Returns the IP address as an IPv4 string when possible.
     *
     * Some IPv6 can be transformed to IPv4 addresses, for example
     * IPv4-mapped IPv6 addresses: `::ffff:192.168.0.1` will return `192.168.0.1`.
     *
     * @return string|NULL IPv4 string address e.g. `'192.0.2.128'` or NULL if this is not an IPv4 address.
     */
    public abstract function toIPv4String();

    /**
     * Anonymize X bytes of the IP address by setting them to a NULL byte.
     *
     * This method returns a new IP instance, it does not modify the current object.
     *
     * @param int $byteCount Number of bytes to set to "\0".
     *
     * @return IP Returns a new modified instance.
     */
    public abstract function anonymize($byteCount);

    /**
     * Returns TRUE if this is an IPv4, IPv4-compat, or IPv4-mapped address, FALSE otherwise.
     *
     * @param string $binaryIp
     * @return bool
     */
    private static function isIPv4($binaryIp)
    {
        // in case mbstring overloads strlen function
        $strlen = function_exists('mb_orig_strlen') ? 'mb_orig_strlen' : 'strlen';

        return $strlen($binaryIp) == 4;
    }
}
