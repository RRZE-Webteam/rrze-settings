<?php

namespace RRZE\Settings\General;

defined('ABSPATH') || exit;

/**
 * GoogleFonts class
 *
 * @package RRZE\Settings\General
 */
class GoogleFonts
{
    /**
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     *
     * @param object $siteOptions Site options object
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
        // Disable Google Fonts
        if ($this->siteOptions->general->disable_google_fonts) {
            $this->disableGoogleFonts();
        }
    }

    /**
     * Disable Google Fonts
     * 
     * Originally written by {@link https://de.wordpress.org/plugins/disable-google-fonts/ Milan Dinić} and
     * modified by {@link https://github.com/RRZE-Webteam RRZE Webteam}
     * 
     * @return void
     */
    protected function disableGoogleFonts()
    {
        add_filter('gettext_with_context', [$this, 'gettextWithContext'], 99, 4);
    }

    /**
     * Force 'off' as a result of font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function gettextWithContext($translations, $text, $context, $domain)
    {
        switch ($text) {
            case 'on':
                // Pass for most cases.
                if ($this->isEndingWithFontToggler($context) || in_array($context, $this->getFontTogglerVariants())) {
                    $translations = 'off';
                }
                break;
            case 'Noto Serif:400,400i,700,700i':
                if ('Google Font Name and Variants' === $context) {
                    $translations = 'off';
                }
                break;
        }

        return $translations;
    }

    /**
     * Check if text is ending with variation of 'font(s):( )on or off'
     * 
     * For most strings that are used as font togglers, context is ending with this text.
     * This method checks if that is the case.
     * 
     * @param string $text Text to check
     * @return bool Whether text is ending with phrase or not
     */
    protected function isEndingWithFontToggler($text)
    {
        if (preg_match('/font[s]?:\s?on or off$/i', $text)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get context variants that cannot be detected with string font toggler checker
     * 
     * @return array
     */
    protected function getFontTogglerVariants()
    {
        return [
            'arimo:on or off',
            'Assistant:on or off',
            'Atma: on or off',
            'Crimson Text: on or off',
            'Dancing Script: on or off',
            'Droid sans: on or off',
            'Google font: on',
            'Google Font for body text: on or off',
            'Google Font for heading text: on or off',
            'Google Font for menu text: on or off',
            'Google fonts: "on" or "off"',
            'Great Vibes:on or off',
            'greatvibes:on or off',
            'Hind: on or off',
            'Indie Flower: on or off',
            'Josefin Sans: on or off',
            'Lato: on or off',
            'Lato:on or off',
            'Lato : on or off',
            'Lobster:on or off',
            'Lora: on or off',
            'Merriweather: on or off',
            'Merriweather:on or off',
            'montserrat:on or off',
            'Muli: on or off',
            'Nunito Sans: on or off',
            'Open Sans',
            'Open Sans:on or off',
            'Open Sans: on or off',
            'opensans:on or off',
            'Open Sans : on or off',
            'Oswald:on or off',
            'oswald:on or off',
            'Oxygen: on or off',
            'Pacifico: on or off',
            'Pacifico:on or off',
            'pacifico:on or off',
            'Poppins: on or off',
            'playball:on or off',
            'Playfair Display: on or off',
            'Product Sans: on or off',
            'pt_sans:on or off',
            'Raleway: on or off',
            'Roboto',
            'Roboto: on or off',
            'roboto:on or off',
            'Roboto:on or off',
            'Roboto : on or off',
            'Roboto Condensed',
            'Roboto Condensed:on or off',
            'roboto_condensed:on or off',
            'robotocondensed:on or off',
            'Roboto Slab:on or off',
            'Sail:on or off',
            'Scada:on or off',
            'scada:on or off',
            'Shadows Into Light: on or off',
        ];
    }

    /**
     * Register filters that disable fonts for bundled themes.
     *
     * This filters can be directly hooked as RRZE\Settings\Core\GoogleFonts::disableOpenSans
     * but that would mean that comparison is done on each string
     * for each font which creates performance issues.
     * Instead we check active template's name very late and just once
     * and hook appropriate filters.
     * Note that Open Sans disabler is used for both WordPress core
     * and for Twenty Twelve theme.
     * 
     * @uses get_template() To get name of the active parent theme.
     * @uses add_filter()   To hook theme specific fonts disablers.
     */
    public function registerThemeFontsDisabler()
    {
        $template = get_template();

        switch ($template) {
            case 'twentyseventeen':
                add_filter('gettext_with_context', [$this, 'disableLibreFranklin'], 99, 4);
                break;
            case 'twentysixteen':
                add_filter('gettext_with_context', [$this, 'disableMerriweather'], 99, 4);
                add_filter('gettext_with_context', [$this, 'disableMontserrat'], 99, 4);
                add_filter('gettext_with_context', [$this, 'disableInconsolata'], 99, 4);
                break;
            case 'twentyfifteen':
                add_filter('gettext_with_context', [$this, 'disableNotoSans'], 99, 4);
                add_filter('gettext_with_context', [$this, 'disableNotoSerif'], 99, 4);
                add_filter('gettext_with_context', [$this, 'disableInconsolata'], 99, 4);
                break;
            case 'twentyfourteen':
                add_filter('gettext_with_context', [$this, 'disableLato'], 99, 4);
                break;
            case 'twentythirteen':
                add_filter('gettext_with_context', [$this, 'disableSourceSansPro'], 99, 4);
                add_filter('gettext_with_context', [$this, 'disableBitter'], 99, 4);
                break;
        }
    }

    /**
     * Force 'off' as a result of Open Sans font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableOpenSans($translations, $text, $context, $domain)
    {
        if ('Open Sans font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Lato font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text.
     */
    public function disableLato($translations, $text, $context, $domain)
    {
        if ('Lato font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Source Sans Pro font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableSourceSansPro($translations, $text, $context, $domain)
    {
        if ('Source Sans Pro font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Bitter font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableBitter($translations, $text, $context, $domain)
    {
        if ('Bitter font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Noto Sans font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableNotoSans($translations, $text, $context, $domain)
    {
        if ('Noto Sans font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Noto Serif font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableNotoSerif($translations, $text, $context, $domain)
    {
        if ('Noto Serif font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Inconsolata font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableInconsolata($translations, $text, $context, $domain)
    {
        if ('Inconsolata font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Merriweather font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableMerriweather($translations, $text, $context, $domain)
    {
        if ('Merriweather font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Montserrat font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableMontserrat($translations, $text, $context, $domain)
    {
        if ('Montserrat font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }

    /**
     * Force 'off' as a result of Libre Franklin font toggler string translation
     * 
     * @param  string $translations Translated text
     * @param  string $text         Text to translate
     * @param  string $context      Context information for the translators
     * @param  string $domain       Text domain. Unique identifier for retrieving translated strings
     * @return string $translations Translated text
     */
    public function disableLibreFranklin($translations, $text, $context, $domain)
    {
        if ('Libre Franklin font: on or off' == $context && 'on' == $text) {
            $translations = 'off';
        }

        return $translations;
    }
}
