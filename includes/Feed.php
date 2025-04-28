<?php

namespace RRZE\Settings;

defined('ABSPATH') || exit;

use SimpleXMLElement;
use Exception;
use WP_Error;

/**
 * RSS|Atom Parser Class
 * Originally written by {@link https://github.com/dg David Grudl} and
 * adapted by {@link https://github.com/RRZE-Webteam RRZE Webteam}
 * 
 * @package RRZE\Settings
 */
class Feed
{
    /**
     * Cache expire time in seconds
     * 
     * @var int
     */
    public static $cacheExpire = 0;

    /**
     * Transient name
     * 
     * @var string
     */
    public static $transient = '';

    /**
     * SimpleXMLElement
     * 
     * @var object
     */
    protected $xml;

    /**
     * Loads RSS or Atom feed
     * 
     * @param  string $url
     * @return object Feed|WP_Error
     */
    public static function load($url)
    {
        try {
            $xml = self::loadXml($url);
        } catch (Exception $e) {
            return new WP_Error('loadXml', $e->getMessage());
        }

        if ($xml->channel) {
            try {
                return self::fromRSS($xml);
            } catch (Exception $e) {
                return new WP_Error('fromRSS', $e->getMessage());
            }
        } else {
            try {
                return self::fromAtom($xml);
            } catch (Exception $e) {
                return new WP_Error('fromAtom', $e->getMessage());
            }
        }
    }

    /**
     * Loads RSS feed
     * 
     * @param  string $url The URL of the RSS feed
     * @return object      Feed|WP_Error
     */
    public static function loadRSS($url)
    {
        try {
            $xml = self::loadXml($url);
        } catch (Exception $e) {
            return new WP_Error('loadXML', $e->getMessage());
        }
        return self::fromRSS($xml);
    }

    /**
     * Loads Atom feed
     * 
     * @param  string $url [description]
     * @return object      Feed|WP_Error
     */
    public static function loadAtom($url)
    {
        try {
            $xml = self::loadXml($url);
        } catch (Exception $e) {
            return new WP_Error('loadXML', $e->getMessage());
        }
        return self::fromAtom($xml);
    }

    /**
     * From RSS
     * 
     * @param  object $xml SimpleXMLElement
     * @return object      Feed|WP_Error
     */
    private static function fromRSS(SimpleXMLElement $xml)
    {
        if (!$xml->channel) {
            return new WP_Error('fromRSS', __('Invalid feed.', 'rrze-rss'));
        }

        self::adjustNamespaces($xml);

        foreach ($xml->channel->item as $item) {
            // converts namespaces to dotted tags
            self::adjustNamespaces($item);

            // generate timestamp tag
            if (isset($item->{'dc:date'})) {
                $item->timestamp = strtotime($item->{'dc:date'});
            } elseif (isset($item->pubDate)) {
                $item->timestamp = strtotime($item->pubDate);
            }
        }
        $feed = new self;
        $feed->xml = $xml->channel;
        return $feed;
    }

    /**
     * From Atom
     * 
     * @param  object $xml SimpleXMLElement
     * @return object      Feed|WP_Error
     */
    private static function fromAtom(SimpleXMLElement $xml)
    {
        if (
            !in_array('http://www.w3.org/2005/Atom', $xml->getDocNamespaces(), true)
            && !in_array('http://purl.org/atom/ns#', $xml->getDocNamespaces(), true)
        ) {
            return new WP_Error('fromAtom', __('Invalid feed.', 'rrze-rss'));
        }

        // generate 'timestamp' tag
        foreach ($xml->entry as $entry) {
            $entry->timestamp = strtotime($entry->updated);
        }
        $feed = new self;
        $feed->xml = $xml;
        return $feed;
    }

    /**
     * Returns property value. Do not call directly
     * 
     * @param  string $name Class property
     * @return mixed
     */
    public function __get($name)
    {
        return $this->xml->{$name};
    }

    /**
     * Sets value of a property. Do not call directly
     * 
     * @param  string  $name  Class property
     * @param  mixed   $value Class property
     */
    public function __set($name, $value)
    {
        throw new Exception("Cannot assign a value to a read-only property '$name'.");
    }

    /**
     * Converts a SimpleXMLElement into an array
     * 
     * @param  object|null $xml SimpleXMLElement|null
     * @return mixed
     */
    public function toArray(SimpleXMLElement $xml)
    {
        if ($xml === null) {
            $xml = $this->xml;
        }

        if (!$xml->children()) {
            return (string) $xml;
        }

        $arr = [];
        foreach ($xml->children() as $tag => $child) {
            if (count($xml->$tag) === 1) {
                $arr[$tag] = $this->toArray($child);
            } else {
                $arr[$tag][] = $this->toArray($child);
            }
        }

        return $arr;
    }

    /**
     * Load XML from cache or HTTP
     * 
     * @param  string $url
     * @return object SimpleXMLElement
     */
    private static function loadXml($url)
    {
        if (!self::$cacheExpire) {
            delete_transient(self::$transient);
        }

        if (false === ($data = get_transient(self::$transient))) {
            if ($data = trim(self::httpRequest($url))) {
                if (self::$cacheExpire) {
                    set_transient(self::$transient, $data, self::$cacheExpire);
                }
            } else {
                throw new Exception(__('Cannot load feed.', 'rrze-rss'));
            }
        }

        return new SimpleXMLElement($data, LIBXML_NOWARNING | LIBXML_NOERROR);
    }

    /**
     * Process HTTP request
     * 
     * @param  string $url
     * @return string|false
     */
    private static function httpRequest($url)
    {
        if (extension_loaded('curl')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            $userAgent = 'RRZE-SETTINGS/' . get_bloginfo('version') . '; ' . get_bloginfo('url');
            curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            if (!ini_get('open_basedir')) {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            }
            $result = curl_exec($curl);
            return curl_errno($curl) === 0 && curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200 ? $result : false;
        } else {
            return file_get_contents($url);
        }
    }

    /**
     * Generates better accessible namespaced tags
     * 
     * @param  object $el SimpleXMLElement
     */
    private static function adjustNamespaces($el)
    {
        foreach ($el->getNamespaces(true) as $prefix => $ns) {
            $children = $el->children($ns);
            foreach ($children as $tag => $content) {
                $el->{$prefix . ':' . $tag} = $content;
            }
        }
    }
}
