<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit45fb479a073f5079a92369872d0463b7
{
    public static $prefixLengthsPsr4 = array (
        'e' => 
        array (
            'enshrined\\svgSanitize\\' => 22,
        ),
        'R' => 
        array (
            'RRZE\\Settings\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'enshrined\\svgSanitize\\' => 
        array (
            0 => __DIR__ . '/..' . '/enshrined/svg-sanitize/src',
        ),
        'RRZE\\Settings\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit45fb479a073f5079a92369872d0463b7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit45fb479a073f5079a92369872d0463b7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit45fb479a073f5079a92369872d0463b7::$classMap;

        }, null, ClassLoader::class);
    }
}
