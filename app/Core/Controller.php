<?php
namespace App\Core;

use App\Integration\WcfSession;
use App\Integration\WcfUser;

/**
 * Basis-Controller. Rendering, Auth-Gates und Redirects.
 */
abstract class Controller
{
    /** @var Request */
    protected $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        if (!array_key_exists('currentUser', $data)) {
            $data['currentUser'] = WcfSession::user();
        }
        if (!array_key_exists('title', $data)) {
            $data['title'] = 'BrickBank';
        }
        if (!array_key_exists('flash', $data)) {
            $data['flash'] = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
        }
        View::render($template, $data, $layout);
    }

    /** Flash-Meldung für die nächste Anfrage (nach Redirect) hinterlegen. */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /** Erzwingt einen eingeloggten Benutzer (sonst Redirect zur WSC-Anmeldung). */
    protected function requireLogin(): WcfUser
    {
        $user = WcfSession::user();
        if ($user === null) {
            $this->redirect(WcfSession::loginUrl());
        }
        return $user;
    }

    /** Erzwingt Schreibberechtigung (eingeloggt + erlaubte WSC-Gruppe). */
    protected function requireWrite(): WcfUser
    {
        $user = $this->requireLogin();
        if (!$user->canWrite()) {
            http_response_code(403);
            exit('Keine Berechtigung für diese Aktion.');
        }
        return $user;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
