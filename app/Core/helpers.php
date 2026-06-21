<?php
/**
 * Globale View-Hilfsfunktionen.
 */

use App\Core\Config;

if (!function_exists('e')) {
    /** HTML-sicheres Escaping für jede Ausgabe. */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    /** Baut eine URL relativ zur konfigurierten base_url. */
    function base_url(string $path = ''): string
    {
        $base = rtrim(Config::get('app.base_url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
