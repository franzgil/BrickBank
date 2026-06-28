<?php
namespace App\Service;

/**
 * Validiert und speichert hochgeladene Bilder unter public/assets/uploads/.
 */
class ImageUpload
{
    const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    const MAX_BYTES = 5242880; // 5 MB

    /**
     * Speichert ein Ast-Bild und gibt den relativen Pfad (unter public/) zurück.
     * @param array $file Eintrag aus $_FILES
     */
    public static function storeLocationImage(array $file, int $locationId): string
    {
        if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Keine gültige Datei hochgeladen.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('Datei zu groß (max. 5 MB).');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Nur Bilder erlaubt (JPG, PNG, WebP, GIF).');
        }
        $ext = self::ALLOWED[$mime];

        $dir = BASE_PATH . '/public/assets/uploads/locations';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload-Verzeichnis nicht beschreibbar.');
        }

        $name = 'loc-' . $locationId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $dest) && !@rename($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Datei konnte nicht gespeichert werden.');
        }
        @chmod($dest, 0644);

        return 'assets/uploads/locations/' . $name;
    }

    /** Löscht eine Datei (nur innerhalb des Upload-Verzeichnisses). */
    public static function deletePublic(?string $relPath): void
    {
        if ($relPath === null || $relPath === '') {
            return;
        }
        $full = BASE_PATH . '/public/' . ltrim($relPath, '/');
        $base = BASE_PATH . '/public/assets/uploads';
        $real = realpath($full);
        if ($real !== false && strpos($real, realpath($base) ?: $base) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}
