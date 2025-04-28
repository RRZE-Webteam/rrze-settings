<?php

namespace RRZE\Settings\Media;

defined('ABSPATH') || exit;

/**
 * Class ImageMagick
 *
 * This class handles image processing using ImageMagick in WordPress.
 *
 * @package RRZE\Settings\Media
 */
class ImageMagick
{
    /**
     * Radius for unsharp mask
     * 
     * @var int
     */
    protected $radius = 0;

    /**
     * Sigma for unsharp mask
     * 
     * @var float
     */
    protected $sigma = 0.5;

    /**
     * Sharpening amount
     * 
     * @var int
     */
    protected $sharpening = 1;

    /**
     * Threshold for unsharp mask
     * 
     * @var int
     */
    protected $threshold = 0;

    /**
     * Compression quality for JPEG
     * 
     * @var int
     */
    protected $compressionQuality = 92;

    /**
     * Auto convert level
     * 
     * @var int
     */
    protected $autoConLev = 1;

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        add_filter('image_make_intermediate_size', [$this, 'sharpenJPGFile'], 99);
    }

    /**
     * Sharpen JPG file using ImageMagick
     *
     * @param string $resizedFile Path to the resized file
     * @return string Path to the sharpened file
     */
    public function sharpenJPGFile($resizedFile)
    {
        if (!extension_loaded('imagick') && !class_exists('\Imagick')) {
            return $resizedFile;
        }

        $image = new \Imagick($resizedFile);
        $size = @getimagesize($resizedFile);
        if (!$size) {
            return $resizedFile;
        }

        list($origWidth, $origHeight, $origType) = $size;

        switch ($origType) {
            case IMAGETYPE_JPEG:
                if ((bool) $this->autoConLev) {
                    $image->normalizeImage();
                }

                $image->unsharpMaskImage($this->radius, $this->sigma, $this->sharpening, $this->threshold);

                $image->setImageFormat('jpg');
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($this->compressionQuality);
                $image->writeImage($resizedFile);

                break;
            default:
                return $resizedFile;
        }

        $image->destroy();

        return $resizedFile;
    }
}
