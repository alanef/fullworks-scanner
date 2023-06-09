<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitea99db91b51039676228e7e2c38b3817
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitea99db91b51039676228e7e2c38b3817', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitea99db91b51039676228e7e2c38b3817', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitea99db91b51039676228e7e2c38b3817::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
