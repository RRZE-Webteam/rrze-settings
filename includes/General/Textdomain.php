<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/*
 * Textdomain class
 * @package RRZE\Settings\General
 */
class Textdomain
{
    /**
     * @var object $siteOptions Site options object.
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options object.
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Load textdomain settings
     *
     * @return void
     */
    public function loaded()
    {
        // Enable textdomain fallback
        if ($this->siteOptions->general->textdomain_fallback) {
            add_filter('load_textdomain_mofile', [$this, 'loadTextDomainMoFile']);
        }
    }

    /**
     * Load a MO file for a given text domain.
     *
     * @param string $mofile Path to the translation file to load.
     * @return string
     */
    public function loadTextDomainMoFile($mofile)
    {
        if (!is_readable($mofile)) {
            $locale = get_locale();
            if (strpos($locale, 'de_') === 0) {
                $localeFallback = 'de_DE';
            } elseif (strpos($locale, 'es_') === 0) {
                $localeFallback = 'es_ES';
            } else {
                $localeFallback = 'en_US';
            }

            $fallbackFile = str_replace($locale, $localeFallback, $mofile);
            if (is_readable($fallbackFile)) {
                return $fallbackFile;
            }
        }

        return $mofile;
    }
}
