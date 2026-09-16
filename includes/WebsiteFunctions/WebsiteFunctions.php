<?php

namespace RRZE\Settings\WebsiteFunctions;

defined('ABSPATH') || exit;

use RRZE\Settings\Main;
use RRZE\Settings\Options;

/**
 * Website functions class.
 *
 * @package RRZE\Settings\WebsiteFunctions
 */
class WebsiteFunctions extends Main
{
    /**
     * Plugin loaded action.
     *
     * @return void
     */
    public function loaded(): void
    {
        (new Settings(
            $this->optionName,
            $this->options,
            $this->siteOptions,
            $this->defaultOptions
        ))->loaded();

        (new Tools())->loaded();
        (new Discussion())->loaded();
        (new XMLRPC($this->siteOptions))->loaded();
        (new AdminEmailVerification($this->siteOptions))->loaded();
        (new Emoji($this->siteOptions))->loaded();
        (new GoogleFonts($this->siteOptions))->loaded();
        (new AI($this->siteOptions))->loaded();
        (new FontLibrary($this->siteOptions))->loaded();

        add_action('wp_head', [$this, 'printGoogleNotranslateMetaTag'], 1);
    }

    /**
     * Print the Google notranslate metatag.
     *
     * @return void
     */
    public function printGoogleNotranslateMetaTag(): void
    {
        $networkAllowed = $this->isGoogleNotranslateAllowedNetworkWide();
        $localEnabled = !empty(get_option('rrze_settings_google_notranslate', 0));

        if (!$networkAllowed || !$localEnabled) {
            return;
        }

        echo '<meta name="googlebot" content="notranslate">' . "\n";
    }

    /**
     * Check whether the network setting allows local Google notranslate output.
     *
     * @return bool True when allowed.
     */
    protected function isGoogleNotranslateAllowedNetworkWide(): bool
    {
        $siteOptions = get_site_option(Options::OPTION_NAME);
        $metatags = $this->getOptionValue($siteOptions, 'metatags');

        if (empty($metatags)) {
            return false;
        }

        return !empty($this->getOptionValue($metatags, 'allow_google_notranslate'));
    }

    /**
     * Gets a value from an option array or object.
     *
     * @param mixed  $source Option source.
     * @param string $key Option key.
     * @return mixed Option value or null.
     */
    protected function getOptionValue($source, string $key)
    {
        if (is_array($source) && array_key_exists($key, $source)) {
            return $source[$key];
        }

        if (is_object($source) && property_exists($source, $key)) {
            return $source->{$key};
        }

        return null;
    }
}
