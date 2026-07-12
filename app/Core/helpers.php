<?php
/**
 * Globale Hilfsfunktionen für Views und URL-Aufbau.
 *
 * Routing-Modell: Routen laufen über die index.php (PATH_INFO), z. B.
 *   /apps/BrickBank/public/index.php/container
 * Das funktioniert ohne mod_rewrite in beliebigen Unterverzeichnissen.
 * Liegt eine Rewrite-Regel vor (public/.htaccess), greifen auch saubere URLs.
 */

use App\Core\Config;

if (!function_exists('e')) {
    /** HTML-sicheres Escaping für jede Ausgabe. */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_script')) {
    /** Web-Pfad zur index.php, z. B. /apps/BrickBank/public/index.php */
    function app_script(): string
    {
        $s = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        return str_replace('\\', '/', $s);
    }
}

if (!function_exists('app_base_dir')) {
    /** Verzeichnis der App (für statische Dateien). Leer = Domain-Root. */
    function app_base_dir(): string
    {
        $configured = (string) Config::get('app.base_url', '');
        if ($configured !== '') {
            return rtrim($configured, '/');
        }
        $dir = rtrim(str_replace('\\', '/', dirname(app_script())), '/');
        return ($dir === '' || $dir === '.') ? '' : $dir;
    }
}

if (!function_exists('asset_url')) {
    /** URL für statische Dateien unter public/ (CSS, JS, Bilder). */
    function asset_url(string $path = ''): string
    {
        return app_base_dir() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('contrast_text_color')) {
    /** Lesbare Textfarbe (#000/#fff) für einen Hintergrund-Hex (z. B. Farb-RGB). */
    function contrast_text_color($rgbHex): string
    {
        $hex = ltrim((string) $rgbHex, '#');
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '#000';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // wahrgenommene Helligkeit (ITU-R BT.601)
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 140 ? '#fff' : '#000';
    }
}

if (!function_exists('part_image_url')) {
    /**
     * Bild-URL eines LEGO-Elements über das öffentliche Rebrickable-CDN
     * (nach offizieller Element-ID). Liefert null, wenn keine ID bekannt ist.
     * Nicht jede ID hat ein Bild – Views blenden fehlende per onerror aus.
     */
    function part_image_url($elementId): ?string
    {
        $elementId = trim((string) $elementId);
        if ($elementId === '') {
            return null;
        }
        return 'https://cdn.rebrickable.com/media/parts/elements/' . rawurlencode($elementId) . '.jpg';
    }
}

if (!function_exists('base_url')) {
    /** URL für eine Route (läuft über index.php / PATH_INFO). */
    function base_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $script = app_script();
        if ($path === '' || $path === '/') {
            return $script;
        }
        return $script . '/' . $path;
    }
}
