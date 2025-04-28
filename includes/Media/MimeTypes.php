<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

/**
 * Class MimeTypes
 *
 * This class handles the MIME types for media uploads in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class MimeTypes
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
     * @return void
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
        add_filter('upload_mimes', [$this, 'uploadMimesFilter']);
        add_filter('wp_check_filetype_and_ext', [$this, 'checkFiletypeFilter'], 10, 5);
    }

    /**
     * Filter the MIME types for uploads
     *
     * @param array $mimeTypes Array of MIME types
     * @return array Modified MIME types
     */
    public function uploadMimesFilter($mimeTypes)
    {
        $customMimeTypes = $this->siteOptions->media->mime_types;
        if (is_array($customMimeTypes) && !empty($customMimeTypes)) {
            foreach ($customMimeTypes as $k => $line) {
                $lineAry = explode('=', $line);
                $vAry = explode(' ', $lineAry[1]);
                $mimeTypes[$k] = $vAry[0];
            }
        }
        return $mimeTypes;
    }

    /**
     * Check the file type and extension
     *
     * @param array $data Array of data
     * @param string $file File path
     * @param string $filename File name
     * @param array $mimes Array of MIME types
     * @param string $mimeType MIME type
     * @return array Modified data
     */
    public function checkFiletypeFilter($data, $file, $filename, $mimes, $mimeType)
    {
        $customMimeTypes = $this->siteOptions->media->mime_types;
        if ((empty($data['ext']) || empty($data['type'])) && is_array($customMimeTypes) && !empty($customMimeTypes)) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            foreach ($customMimeTypes as $k => $line) {
                $kAry = explode('|', $k);
                $lineAry = explode('=', $line);
                $vAry = explode(' ', $lineAry[1]);
                if (in_array($ext, $kAry) && in_array($mimeType, $vAry, true)) {
                    $data['ext'] = $ext;
                    $data['type'] = $mimeType;
                    break;
                }
            }
        }
        return $data;
    }
}
