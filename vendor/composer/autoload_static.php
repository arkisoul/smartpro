<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9f86bb082d2d30664ece3353bcbd1759
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9f86bb082d2d30664ece3353bcbd1759::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9f86bb082d2d30664ece3353bcbd1759::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
