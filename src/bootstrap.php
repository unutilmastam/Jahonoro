<?php

declare(strict_types=1);

/*
 * Ilova ishga tushishi: xatolar sozlamasi, autoload (PSR-4, Composer'siz),
 * config va vaqt zonasi.
 */

define('APP_ROOT', dirname(__DIR__));

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/**
 * config/config.php qiymatini qaytaradi. Fayl bo'lmasa — default.
 */
function config(string $key, mixed $default = null): mixed
{
    static $values = null;
    if ($values === null) {
        $file = APP_ROOT . '/config/config.php';
        $loaded = is_file($file) ? require $file : [];
        $values = is_array($loaded) ? $loaded : [];
    }
    return array_key_exists($key, $values) ? $values[$key] : $default;
}

function config_loaded(): bool
{
    return is_file(APP_ROOT . '/config/config.php');
}

date_default_timezone_set((string) config('TIMEZONE', 'Asia/Tashkent'));
