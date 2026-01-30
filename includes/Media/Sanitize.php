<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

/**
 * Class Sanitize
 *
 * This class handles the sanitization of media items in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class Sanitize
{
    /**
     * Plugin loaded action
     *
     * @return void
     */
    public function loaded()
    {
        add_filter('sanitize_file_name', [$this, 'sanitizeFilename']);
    }

    /**
     * Sanitize the filename.
     * @param string $filename Filename
     * @return string Sanitized filename
     */
    public function sanitizeFilename($filename = '')
    {
        if (
            (function_exists('wp_is_valid_utf8') && wp_is_valid_utf8($filename))
            || (!function_exists('wp_is_valid_utf8') && seems_utf8($filename))
        ) {
            $filename = $this->transliterator($filename);
        }

        $fileParts = explode('.', $filename);
        $extension = array_pop($fileParts);
        $extension = mb_strtolower($extension);
        $filename = implode('.', $fileParts);
        return sprintf('%1$s.%2$s', $filename, $extension);
    }

    /**
     * Convert a string to 7-bit ASCII.
     * @param  string  $string
     * @return string
     */
    public function transliterator($string)
    {
        $ascii = $this->ascii();
        $string = preg_replace(array_keys($ascii), array_values($ascii), $string);
        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $string);
    }

    /**
     * 7-bit ASCII array map.
     * @return array
     */
    public function ascii(): array
    {
        return [
            '/Ä/' => 'Ae',
            '/æ|ǽ|ä/' => 'ae',
            '/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ|А/' => 'A',
            '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|а/' => 'a',
            '/Б/' => 'B',
            '/б/' => 'b',
            '/Ç|Ć|Ĉ|Ċ|Č|Ц/' => 'C',
            '/ç|ć|ĉ|ċ|č|ц/' => 'c',
            '/Ð|Ď|Đ/' => 'Dj',
            '/ð|ď|đ/' => 'dj',
            '/Д/' => 'D',
            '/д/' => 'd',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Е|Ё|Э/' => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě|е|ё|э/' => 'e',
            '/Ф/' => 'F',
            '/ƒ|ф/' => 'f',
            '/Ĝ|Ğ|Ġ|Ģ|Г/' => 'G',
            '/ĝ|ğ|ġ|ģ|г/' => 'g',
            '/Ĥ|Ħ|Х/' => 'H',
            '/ĥ|ħ|х/' => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|И/' => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|и/' => 'i',
            '/Ĵ|Й/' => 'J',
            '/ĵ|й/' => 'j',
            '/Ķ|К/' => 'K',
            '/ķ|к/' => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł|Л/' => 'L',
            '/ĺ|ļ|ľ|ŀ|ł|л/' => 'l',
            '/М/' => 'M',
            '/м/' => 'm',
            '/Ñ|Ń|Ņ|Ň|Н/' => 'N',
            '/ñ|ń|ņ|ň|ŉ|н/' => 'n',
            '/Ö/' => 'Oe',
            '/œ|ö/' => 'oe',
            '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|О/' => 'O',
            '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|о/' => 'o',
            '/П/' => 'P',
            '/п/' => 'p',
            '/Ŕ|Ŗ|Ř|Р/' => 'R',
            '/ŕ|ŗ|ř|р/' => 'r',
            '/Ś|Ŝ|Ş|Ș|Š|С/' => 'S',
            '/ś|ŝ|ş|ș|š|ſ|с/' => 's',
            '/Ţ|Ț|Ť|Ŧ|Т/' => 'T',
            '/ţ|ț|ť|ŧ|т/' => 't',
            '/Ü/' => 'Ue',
            '/ü/' => 'ue',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у/' => 'u',
            '/В/' => 'V',
            '/в/' => 'v',
            '/Ý|Ÿ|Ŷ|Ы/' => 'Y',
            '/ý|ÿ|ŷ|ы/' => 'y',
            '/Ŵ/' => 'W',
            '/ŵ/' => 'w',
            '/Ź|Ż|Ž|З/' => 'Z',
            '/ź|ż|ž|з/' => 'z',
            '/Æ|Ǽ/' => 'AE',
            '/ß/' => 'ss',
            '/Ĳ/' => 'IJ',
            '/ĳ/' => 'ij',
            '/Œ/' => 'OE',
            '/Ч/' => 'Ch',
            '/ч/' => 'ch',
            '/Ю/' => 'Ju',
            '/ю/' => 'ju',
            '/Я/' => 'Ja',
            '/я/' => 'ja',
            '/Ш/' => 'Sh',
            '/ш/' => 'sh',
            '/Щ/' => 'Shch',
            '/щ/' => 'shch',
            '/Ж/' => 'Zh',
            '/ж/' => 'zh',
        ];
    }
}
