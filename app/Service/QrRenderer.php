<?php
namespace App\Service;

/**
 * Serverseitige QR-Erzeugung via GD.
 *
 * Nutzt die Bibliothek phpqrcode, falls sie unter vendor/phpqrcode/qrlib.php
 * vorliegt (Drop-in oder via Composer). Ist sie nicht vorhanden, antwortet der
 * Endpunkt mit HTTP 501 – die Views rendern den QR dann clientseitig als
 * Fallback. Siehe docs/MODUL-BEHAELTER-INVENTUR.md (§5/§8).
 */
class QrRenderer
{
    public static function available(): bool
    {
        return self::loadLib();
    }

    private static function loadLib(): bool
    {
        if (class_exists('\\QRcode')) {
            return true;
        }
        $lib = BASE_PATH . '/vendor/phpqrcode/qrlib.php';
        if (is_file($lib)) {
            require_once $lib;
            return class_exists('\\QRcode');
        }
        return false;
    }

    /** Gibt das QR-PNG für $data direkt aus (oder 501, falls keine Bibliothek). */
    public static function output(string $data, int $size = 6, int $margin = 2): void
    {
        if (!self::available()) {
            http_response_code(501);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Serverseitige QR-Bibliothek (phpqrcode) nicht installiert – Fallback im Browser aktiv.';
            return;
        }
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        // phpqrcode: QRcode::png($text, $outfile=false, $level, $size, $margin)
        \QRcode::png($data, false, defined('QR_ECLEVEL_M') ? QR_ECLEVEL_M : 'M', $size, $margin);
    }
}
