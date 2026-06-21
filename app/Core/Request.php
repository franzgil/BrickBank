<?php
namespace App\Core;

/**
 * Kapselt die HTTP-Anfrage (Methode, Pfad, Eingaben).
 */
class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** Normalisierter Route-Pfad (über PATH_INFO bzw. REQUEST_URI), Root = '/'. */
    public function path(): string
    {
        // Bevorzugt PATH_INFO: Routing über index.php/<route> – ohne Rewrite.
        $info = isset($_SERVER['PATH_INFO']) ? (string) $_SERVER['PATH_INFO'] : '';

        if ($info === '') {
            $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $uri    = is_string($uri) ? $uri : '/';
            $script = app_script();                                  // …/public/index.php
            $dir    = rtrim(str_replace('\\', '/', dirname($script)), '/');

            if ($script !== '' && strpos($uri, $script) === 0) {
                // Direktaufruf .../index.php[/route]
                $info = substr($uri, strlen($script));
            } elseif ($dir !== '' && strpos($uri, $dir) === 0) {
                // Rewrite-Betrieb .../public/route
                $info = substr($uri, strlen($dir));
            } else {
                $info = $uri;
            }
        }

        $path = '/' . trim($info, '/');
        return $path === '/index.php' ? '/' : $path;
    }

    /** @return mixed */
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /** @return mixed */
    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}
