<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInited62f52b9124362c842eaf500b251b20
{
    public static $files = array (
        'ad1caa44c8b5bb0e72c7cc6d744bea62' => __DIR__ . '/..' . '/anthonybudd/wp_mail/src/WP_Mail.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInited62f52b9124362c842eaf500b251b20::$classMap;

        }, null, ClassLoader::class);
    }
}
