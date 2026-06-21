<?php
namespace App\Integration;

use App\Core\Config;
use App\Core\Database;
use PDO;

/**
 * WoltLab-Suite-5.5-SSO (read-only).
 *
 * Liest das WSC-Session-Cookie, löst es gegen die WSC-Datenbank auf und liefert
 * den eingeloggten Benutzer. BrickBank speichert keine Passwörter und schreibt
 * NIE in WSC-Tabellen.
 *
 * Hinweis: Tabellen-/Spalten-/Cookie-Namen sind WSC-typisch, aber instanz-/
 * versionsabhängig. Vor dem Produktiveinsatz an der laufenden 5.5-Instanz
 * verifizieren (Konfiguration unter 'wsc' anpassen). Siehe docs/INTEGRATION.md.
 */
class WcfSession
{
    /** @var bool */
    private static $resolved = false;
    /** @var WcfUser|null */
    private static $user = null;

    /** Eingeloggter Benutzer oder null (Gast). Ergebnis wird gecacht. */
    public static function user(): ?WcfUser
    {
        if (!self::$resolved) {
            self::$resolved = true;
            self::$user = self::resolve();
        }
        return self::$user;
    }

    private static function resolve(): ?WcfUser
    {
        $prefix     = Config::get('wsc.table_prefix', 'wcf1_');
        $cookieName = Config::get('wsc.cookie_prefix', 'wsc_') . 'user_session';

        $sessionId = $_COOKIE[$cookieName] ?? null;
        if (!is_string($sessionId) || $sessionId === '') {
            return null;
        }

        try {
            $db = Database::wsc();

            // 1. Session → userID
            $stmt = $db->prepare(
                'SELECT userID FROM ' . $prefix . 'user_session WHERE sessionID = ? LIMIT 1'
            );
            $stmt->execute([$sessionId]);
            $userId = $stmt->fetchColumn();
            if (!$userId) {
                return null; // Gast-Session
            }

            // 2. Benutzer-Stammdaten
            $stmt = $db->prepare(
                'SELECT userID, username, email FROM ' . $prefix . 'user WHERE userID = ? LIMIT 1'
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }

            // 3. Gruppenzugehörigkeit (für Autorisierung)
            $stmt = $db->prepare(
                'SELECT groupID FROM ' . $prefix . 'user_to_group WHERE userID = ?'
            );
            $stmt->execute([$userId]);
            $groups = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            return new WcfUser(
                (int) $row['userID'],
                (string) $row['username'],
                (string) $row['email'],
                $groups
            );
        } catch (\PDOException $e) {
            // WSC-Anbindung fehlerhaft konfiguriert → wie „nicht eingeloggt" behandeln,
            // aber zur Diagnose protokollieren.
            error_log('[BrickBank] WSC-SSO-Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /** WSC-Login-URL inkl. Rücksprung auf die aktuelle Seite. */
    public static function loginUrl(): string
    {
        $login = Config::get('wsc.login_url', '/');
        $sep   = strpos($login, '?') === false ? '?' : '&';
        return $login . $sep . 'url=' . urlencode(self::currentUrl());
    }

    private static function currentUrl(): string
    {
        $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}
