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
        header('Content-Type: text/plain; charset=utf-8');
        $prefix  = (string) \App\Core\Config::get('wsc.table_prefix', 'wcf1_');
        $cpref   = (string) \App\Core\Config::get('wsc.cookie_prefix', '');
        $expect  = $cpref . 'user_session';

        echo "== Empfangene Cookies (nur Namen) ==\n";
        foreach ($_COOKIE as $name => $val) {
            echo '  ' . $name . '  (len ' . strlen((string) $val) . ")\n";
        }
        echo "\nErwarteter Session-Cookie: " . $expect
            . '  → ' . (isset($_COOKIE[$expect]) ? 'VORHANDEN' : 'FEHLT') . "\n";

        echo "\n== Erreichbarkeit der WSC-Tabellen ==\n";
        foreach (['app' => \App\Core\Database::class . '::app', 'wsc(separat)' => \App\Core\Database::class . '::wsc'] as $label => $fn) {
            try {
                $pdo = call_user_func($fn);
                $cnt = $pdo->query('SELECT COUNT(*) FROM ' . $prefix . 'user')->fetchColumn();
                echo '  ' . $label . ': ' . $prefix . 'user OK (' . $cnt . " Nutzer)\n";
            } catch (\Throwable $e) {
                echo '  ' . $label . ': FEHLER ' . $e->getMessage() . "\n";
            }
        }

        echo "\n== Session-Tabellen (über aktive wcf()-Verbindung) ==\n";
        foreach (['user_session', 'session'] as $t) {
            try {
                $cnt = \App\Core\Database::wcf()->query('SELECT COUNT(*) FROM ' . $prefix . $t)->fetchColumn();
                echo '  ' . $prefix . $t . ': ' . $cnt . " Zeilen\n";
            } catch (\Throwable $e) {
                echo '  ' . $prefix . $t . ": nicht vorhanden/Fehler\n";
            }
        }

        echo "\n== Treffer für aktuelles Session-Cookie ==\n";
        if (isset($_COOKIE[$expect]) && $_COOKIE[$expect] !== '') {
            try {
                $stmt = \App\Core\Database::wcf()->prepare(
                    'SELECT userID FROM ' . $prefix . 'user_session WHERE sessionID = ? LIMIT 1'
                );
                $stmt->execute([$_COOKIE[$expect]]);
                $uid = $stmt->fetchColumn();
                echo '  Lookup ' . $prefix . 'user_session.sessionID: '
                    . ($uid !== false ? ('Treffer, userID=' . $uid) : 'KEIN Treffer (Cookie-Wert ≠ sessionID)') . "\n";
            } catch (\Throwable $e) {
                echo '  Lookup-Fehler: ' . $e->getMessage() . "\n";
            }
        } else {
            echo "  (kein Session-Cookie vorhanden)\n";
        }

        echo "\n== Ergebnis ==\n";
        $u = \App\Integration\WcfSession::user();
        echo '  WcfSession::user() → ' . ($u ? ('userID=' . $u->userId . ' (' . $u->username . ')') : 'null') . "\n";
    }
}
