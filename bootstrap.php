<?php
/**
 * BrickBank – Bootstrap.
 * Autoloader, Konfiguration und Session initialisieren.
 */

define('BASE_PATH', __DIR__);

// Einfacher PSR-4-Autoloader: App\ → app/
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

use App\Core\Config;

$configFile = BASE_PATH . '/config/config.php';
Config::load(is_file($configFile) ? $configFile : BASE_PATH . '/config/config.example.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
