<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit94e23cbc47a4750e27ee21b7644f1866
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'ACF\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ACF\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'ACF\\Blocks\\Bindings' => __DIR__ . '/../..' . '/src/Blocks/Bindings.php',
        'ACF\\Meta\\Comment' => __DIR__ . '/../..' . '/src/Meta/Comment.php',
        'ACF\\Meta\\MetaLocation' => __DIR__ . '/../..' . '/src/Meta/MetaLocation.php',
        'ACF\\Meta\\Option' => __DIR__ . '/../..' . '/src/Meta/Option.php',
        'ACF\\Meta\\Post' => __DIR__ . '/../..' . '/src/Meta/Post.php',
        'ACF\\Meta\\Term' => __DIR__ . '/../..' . '/src/Meta/Term.php',
        'ACF\\Meta\\User' => __DIR__ . '/../..' . '/src/Meta/User.php',
        'ACF\\Pro\\Forms\\WC_Order' => __DIR__ . '/../..' . '/src/Pro/Forms/WC_Order.php',
        'ACF\\Pro\\Meta\\WooOrder' => __DIR__ . '/../..' . '/src/Pro/Meta/WooOrder.php',
        'ACF\\Site_Health\\Site_Health' => __DIR__ . '/../..' . '/src/Site_Health/Site_Health.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit94e23cbc47a4750e27ee21b7644f1866::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit94e23cbc47a4750e27ee21b7644f1866::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit94e23cbc47a4750e27ee21b7644f1866::$classMap;

        }, null, ClassLoader::class);
    }
}
