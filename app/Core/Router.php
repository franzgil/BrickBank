<?php
namespace App\Core;

/**
 * Minimaler Router. Unterstützt statische Routen und {param}-Platzhalter.
 * Handler-Format: [ControllerKlasse::class, 'methode'].
 */
class Router
{
    /** @var array */
    private $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        if (isset($this->routes[$method][$path])) {
            $this->call($this->routes[$method][$path], []);
            return;
        }
        foreach ($this->routes[$method] as $route => $handler) {
            $params = $this->match($route, $path);
            if ($params !== null) {
                $this->call($handler, $params);
                return;
            }
        }
        http_response_code(404);
        echo '404 – Seite nicht gefunden';
    }

    private function match(string $route, string $path): ?array
    {
        if (strpos($route, '{') === false) {
            return null;
        }
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route);
        if (!preg_match('#^' . $pattern . '$#', $path, $m)) {
            return null;
        }
        array_shift($m);
        return array_map('urldecode', $m);
    }

    private function call(array $handler, array $params): void
    {
        list($class, $action) = $handler;
        $controller = new $class();
        call_user_func_array([$controller, $action], $params);
    }
}
