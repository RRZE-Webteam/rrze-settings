<?php

namespace RRZE\Settings\CSP;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use RRZE\Settings\Library\Files\File;

/**
 * Class CSP
 *
 * This class handles the Content Security Policy (CSP) for the WordPress site.
 *
 * @package RRZE\Settings\CSP
 */
class CSP extends Main
{
    /**
     * oEmbed providers
     * 
     * @var array
     */
    protected $oembedProviders = [];

    /**
     * Plugin loaded action
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

        add_filter('rrze_settings_csp_options_validate', [$this, 'maybeWritePolicy']);

        add_filter('oembed_providers', [$this, 'setOembedProviders'], 99);
        add_filter('fau_oembed_providers', [$this, 'setOembedProviders']);
        add_filter('fau_oembed_handlers', [$this, 'setEmbedHandlers']);

        add_filter('wp_headers', [$this, 'headers']);
    }

    /**
     * Maybe write the CSP policy to a file
     *
     * @param object $options Options object
     * @return object Modified options object
     */
    public function maybeWritePolicy($options)
    {
        $filename = WP_PLUGIN_DIR .
            DIRECTORY_SEPARATOR .
            'rrze-settings' .
            DIRECTORY_SEPARATOR .
            'content-security-policy.header';
        $cspHeaderStr = $options->csp->enabled ? $this->getCSPHeader($options) : '';
        if ($cspHeaderStr != '') {
            File::write($filename, $cspHeaderStr);
        } elseif (file_exists($filename)) {
            unlink($filename);
        }
        return $options;
    }

    /**
     * Set oEmbed providers
     *
     * @param array $providers Array of oEmbed providers
     * @return array Modified array of oEmbed providers
     */
    public function setOembedProviders($providers)
    {
        $providers = [
            '#https?://((m|www)\.)?youtube\.com/watch.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://((m|www)\.)?youtube\.com/playlist.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://((m|www)\.)?youtube\.com/shorts/*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://youtu\.be/.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://(.+\.)?vimeo\.com/.*#i' => ['https://vimeo.com/api/oembed.{format}', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/status(es)?/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}$#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/likes$#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/lists/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/timelines/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/i/moments/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(.+?\.)?slideshare\.net/.*#i' => ['https://www.slideshare.net/api/oembed/2', true],
            '#https?://open.spotify.com/.*#i' => ['https://open.spotify.com/oembed', true]
        ];
        foreach ($providers as $provider) {
            $url = array_values($provider)[0] ?? null;
            if ($url) {
                $this->oembedProviders[parse_url($url, PHP_URL_HOST)] = parse_url($url, PHP_URL_HOST);
            }
        }
        return $providers;
    }

    /**
     * Set embed handlers
     *
     * @param array $handlers Array of embed handlers
     * @return array Modified array of embed handlers
     */
    public function setEmbedHandlers($handlers)
    {
        foreach ($handlers as $handler) {
            foreach ($handler['allowed_domains'] as $domain) {
                $this->oembedProviders[$domain] = $domain;
            }
        }
        return $handlers;
    }

    /**
     * Add CSP header to the response
     *
     * @param array $headers Array of headers
     * @return array Modified array of headers
     */
    public function headers($headers)
    {
        if ($this->siteOptions->csp->enabled) {
            $cspHeaderStr = $this->getCSPHeader($this->siteOptions);
            if ($cspHeaderStr != '') {
                $headers['Content-Security-Policy'] = $cspHeaderStr;
            }
        }
        return $headers;
    }

    /**
     * Get the CSP header string
     *
     * @param object $options Options object
     * @return string CSP header string
     */
    protected function getCSPHeader(object $options)
    {
        $allowedDirectives = [];
        $defaultOptions = (array) $this->defaultOptions->csp;
        foreach (array_keys($defaultOptions) as $key) {
            if ($key == 'enabled') {
                continue;
            }
            $allowedDirectives[] = $key;
        }

        $output = [];
        $none = '\'none\'';
        foreach ($allowedDirectives as $key) {
            $oembedDomains = '';
            if ($key == 'default_src' && $options->csp->$key != $none) {
                $oembedDomains = sprintf(' %s', implode(' ', $this->oembedProviders));
            }
            if (!empty($this->siteOptions->csp->$key)) {
                $output[] = sprintf('%1$s %2$s%3$s', $this->sanitizeKey($key), $this->sanitizeValue($this->siteOptions->csp->$key), $oembedDomains);
            }
        }
        return implode('; ', $output) . ';';
    }

    /**
     * Sanitize the CSP key
     *
     * @param string $key The CSP key
     * @return string Sanitized CSP key
     */
    protected function sanitizeKey($key)
    {
        return str_replace('_', '-', $key);
    }

    /**
     * Sanitize the CSP value
     *
     * @param string $value The CSP value
     * @return string Sanitized CSP value
     */
    protected function sanitizeValue($value)
    {
        return stripslashes($value);
    }
}
