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
            $prefix = (string) \App\Core\Config::get('wsc.table_prefix', 'wcf1_');
            $cpref  = (string) \App\Core\Config::get('wsc.cookie_prefix', '');
            $expect = $cpref . 'user_session';

            echo "== Konfiguration ==\n";
            echo '  config/config.php vorhanden: ' . (is_file(BASE_PATH . '/config/config.php') ? 'ja' : 'NEIN (Beispiel-Defaults aktiv)') . "\n";
            echo '  wsc.same_database: ' . (\App\Core\Config::get('wsc.same_database', false) ? 'true' : 'false') . "\n";
            echo '  wsc.db.name: ' . (string) \App\Core\Config::get('wsc.db.name', '') . "\n";
            echo '  table_prefix: ' . $prefix . '   cookie_prefix: ' . $cpref . "\n";

            echo "\n== Empfangene Cookies (nur Namen) ==\n";
            foreach ($_COOKIE as $name => $val) {
                echo '  ' . $name . '  (len ' . strlen((string) $val) . ")\n";
            }
            echo 'Erwarteter Session-Cookie: ' . $expect
                . '  → ' . (isset($_COOKIE[$expect]) ? 'VORHANDEN' : 'FEHLT') . "\n";

            echo "\n== Erreichbarkeit wcf1_user ==\n";
            echo '  app-Verbindung:  ';
            try {
                $c = \App\Core\Database::app()->query('SELECT COUNT(*) FROM ' . $prefix . 'user')->fetchColumn();
                echo 'OK (' . $c . " Nutzer)\n";
            } catch (\Throwable $e) {
                echo 'FEHLER: ' . $e->getMessage() . "\n";
            }
            echo '  wsc-Verbindung:  ';
            try {
                $c = \App\Core\Database::wsc()->query('SELECT COUNT(*) FROM ' . $prefix . 'user')->fetchColumn();
                echo 'OK (' . $c . " Nutzer)\n";
            } catch (\Throwable $e) {
                echo 'FEHLER: ' . $e->getMessage() . "\n";
            }

            echo "\n== Session-Tabellen (aktive wcf()-Verbindung) ==\n";
            foreach (['user_session', 'session'] as $t) {
                echo '  ' . $prefix . $t . ': ';
                try {
                    $c = \App\Core\Database::wcf()->query('SELECT COUNT(*) FROM ' . $prefix . $t)->fetchColumn();
                    echo $c . " Zeilen\n";
                } catch (\Throwable $e) {
                    echo "nicht vorhanden/Fehler\n";
                }
            }

            echo "\n== Lookup mit aktuellem Cookie ==\n";
            if (isset($_COOKIE[$expect]) && $_COOKIE[$expect] !== '') {
                try {
                    $stmt = \App\Core\Database::wcf()->prepare(
                        'SELECT userID FROM ' . $prefix . 'user_session WHERE sessionID = ? LIMIT 1'
                    );
                    $stmt->execute([$_COOKIE[$expect]]);
                    $uid = $stmt->fetchColumn();
                    echo '  ' . ($uid !== false ? ('Treffer, userID=' . $uid) : 'KEIN Treffer (Cookie-Wert ≠ sessionID)') . "\n";
                } catch (\Throwable $e) {
                    echo '  Fehler: ' . $e->getMessage() . "\n";
                }
            } else {
                echo "  (kein Session-Cookie vorhanden)\n";
            }

            echo "\n== Ergebnis ==\n";
            $u = \App\Integration\WcfSession::user();
            echo '  WcfSession::user() → ' . ($u ? ('userID=' . $u->userId . ' (' . $u->username . ')') : 'null') . "\n";
        } catch (\Throwable $e) {
            echo "\nFATAL: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
        }
    }
}
