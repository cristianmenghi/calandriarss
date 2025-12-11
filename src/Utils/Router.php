<?php

namespace App\Utils;

class Router
{
    private $routes = [];

    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Simple wildcard matching for ID parameters (e.g., /api/articles/123)
        foreach ($this->routes[$method] as $route => $callback) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $route);
            if (preg_match("#^$pattern$#", $path, $matches)) {
                array_shift($matches); // Remove full match
                return call_user_func_array($callback, $matches);
            }
        }

        if (isset($this->routes[$method][$path])) {
            return call_user_func($this->routes[$method][$path]);
        }

        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
}
