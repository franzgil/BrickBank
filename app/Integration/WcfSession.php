<?php
namespace App\Integration;

use App\Core\Config;

/**
 * WoltLab-Suite-SSO durch Bootstrappen von WoltLab (wcf/global.php).
 *
 * Statt das Session-Cookie selbst zu zerlegen, wird WoltLab geladen –
 * WoltLab löst Cookie/Session/Signatur korrekt auf, und wir lesen nur den
 * eingeloggten Benutzer über WCF::getUser(). Gleicher Ansatz wie im Projekt
 * MemberMgt. WoltLab nutzt dabei seine eigene Datenbankverbindung
 * (wcf/config.inc.php) – ein separater DB-Zugang für BrickBank ist nicht nötig.
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

    /** Effektiver Pfad zu WoltLabs wcf/global.php (konfigurierbar, sonst geraten). */
    public static function globalPhpPath(): string
    {
        $path = (string) Config::get('wsc.wcf_global', '');
        if ($path !== '') {
            return $path;
        }
        // Standard-Annahme: Apps liegen unter {webroot}/apps/<App>/,
        // WoltLab unter {webroot}/wcf/ – also zwei Ebenen über dem App-Verzeichnis.
        return dirname(dirname(BASE_PATH)) . '/wcf/global.php';
    }

    private static function resolve(): ?WcfUser
    {
        $global = self::globalPhpPath();
        if (!is_file($global)) {
            error_log('[BrickBank] WCF global.php nicht gefunden: ' . $global);
            return null;
        }
        try {
            if (!class_exists('\\wcf\\system\\WCF', false)) {
                require_once $global;
            }
            if (!class_exists('\\wcf\\system\\WCF', false)) {
                return null;
            }

            $u = \wcf\system\WCF::getUser();
            if (!$u || !$u->userID) {
                return null; // Gast
            }

            $groups = method_exists($u, 'getGroupIDs')
                ? array_map('intval', $u->getGroupIDs())
                : [];

            return new WcfUser(
                (int) $u->userID,
                (string) $u->username,
                (string) $u->email,
                $groups
            );
        } catch (\Throwable $e) {
            error_log('[BrickBank] WCF-Bootstrap-Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /** WSC-Login-URL inkl. Rücksprung auf die aktuelle Seite. */
    public static function loginUrl(): string
    {
        $login = (string) Config::get('wsc.login_url', '/');
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
