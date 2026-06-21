<?php
namespace App\Core;

/**
 * Zentrale Konfiguration. Zugriff über Punktnotation: Config::get('db.host').
 */
class Config
{
    /** @var array */
    private static $data = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Konfiguration fehlt: ' . $file
                . ' (config/config.example.php nach config/config.php kopieren).');
        }
        self::$data = require $file;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$data;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }
}
