<?php

namespace RRZE\Settings\Library\Files;

defined('ABSPATH') || exit;

/**
 * File class
 *
 * @package RRZE\Settings\Library\Files
 */
final class File
{
    /**
     * write
     * 
     * @param  string $file
     * @param  string $data
     * @return boolean
     */
    public static function write(string $file, string $data): bool
    {
        if (!$handle = @fopen($file, 'wb')) {
            return false;
        }
        @fwrite($handle, $data);
        fclose($handle);
        clearstatcache();
        return self::chmod($file);
    }

    /**
     * chmod
     * 
     * @param  string $file
     * @return boolean
     */
    public static function chmod(string $file): bool
    {
        $stat = @stat(dirname($file));
        $perms = $stat['mode'] & 0007777;
        $perms = $perms & 0000666;
        $return = @chmod($file, $perms);
        clearstatcache();
        return $return;
    }
}
