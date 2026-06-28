<?php
namespace App\Controller;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home/index', ['title' => 'BrickBank – Start']);
    }

    /** Diagnose ohne Datenbank: prüft Routing/Pfade im Deployment. */
    public function ping(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "pong\n";
        echo 'path        = ' . $this->request->path() . "\n";
        echo 'script_name = ' . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
        echo 'path_info   = ' . ($_SERVER['PATH_INFO'] ?? '') . "\n";
        echo 'request_uri = ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
        echo 'asset_base  = ' . app_base_dir() . "\n";
        echo 'route(home) = ' . base_url('/') . "\n";
        echo 'route(cont) = ' . base_url('container') . "\n";
        echo 'asset(css)  = ' . asset_url('assets/app.css') . "\n";
    }

    /**
     * SSO-Diagnose (zeigt KEINE Cookie-/Session-Werte, nur Namen/Status).
     * Nach erfolgreicher Einrichtung Route in app/routes.php wieder entfernen.
     */
    public function authDebug(): void
    {
        @ini_set('display_errors', '1');
        error_reporting(E_ALL);
        header('Content-Type: text/plain; charset=utf-8');

        try {
            $wcfPath = \App\Integration\WcfSession::globalPhpPath();

            echo "== WoltLab-Bootstrap (SSO) ==\n";
            echo '  config/config.php vorhanden: ' . (is_file(BASE_PATH . '/config/config.php') ? 'ja' : 'NEIN (Beispiel-Defaults aktiv)') . "\n";
            echo '  BASE_PATH: ' . BASE_PATH . "\n";
            echo '  wsc.wcf_global (konfiguriert): ' . (string) \App\Core\Config::get('wsc.wcf_global', '(leer → automatisch)') . "\n";
            echo '  global.php (effektiver Pfad): ' . $wcfPath . "\n";
            echo '  global.php vorhanden: ' . (@is_file($wcfPath) ? 'JA' : 'NEIN') . "\n";

            $u = \App\Integration\WcfSession::user();
            echo '  WCF-Klasse geladen: ' . (class_exists('\\wcf\\system\\WCF', false) ? 'ja' : 'nein') . "\n";
            if ($u) {
                echo '  ✅ EINGELOGGT: userID=' . $u->userId . ', user=' . $u->username
                    . ', Gruppen=' . implode(',', $u->groupIds) . "\n";
            } else {
                echo "  Ergebnis: nicht eingeloggt / null\n";
            }

            echo "\n== Empfangene Cookies (nur Namen) ==\n";
            foreach ($_COOKIE as $name => $val) {
                echo '  ' . $name . "\n";
            }
        } catch (\Throwable $e) {
            echo "\nFATAL: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
        }
    }
}
