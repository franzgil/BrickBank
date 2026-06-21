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

    /** Normalisierter Pfad ohne base_url, ohne Trailing-Slash (Root = '/'). */
    public function path(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri  = is_string($uri) ? $uri : '/';
        $base = rtrim(Config::get('app.base_url', ''), '/');
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . trim($uri, '/');
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
