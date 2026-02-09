<?php

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|string $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|string $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Eliminar base_path si existe
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
            if ($path === '') $path = '/';
        }
        $path = $this->normalize($path);
        $handler = $this->routes[$method][$path] ?? null;

        if (! $handler) {
            http_response_code(404);
            echo '404 No encontrado';
            return;
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$controller, $action] = explode('@', $handler);
        $class = 'App\\Controllers\\' . $controller;
        if (! class_exists($class)) {
            http_response_code(500);
            echo 'Controlador no encontrado';
            return;
        }

        $instance = new $class();
        if (! method_exists($instance, $action)) {
            http_response_code(500);
            echo 'Accion no encontrada';
            return;
        }

        $instance->$action();
    }

    private function normalize(string $path): string
    {
        // Eliminar el prefijo /kron/public/ si existe (para subdirectorios en hosting)
        $path = preg_replace('#^/kron/public/#', '/', $path);
        $path = preg_replace('#^/kron/public$#', '/', $path);
        
        // Eliminar el prefijo /kron/ si existe
        $path = preg_replace('#^/kron/#', '/', $path);
        $path = preg_replace('#^/kron$#', '/', $path);
        
        // Eliminar /public/ si existe
        $path = preg_replace('#^/public/#', '/', $path);
        $path = preg_replace('#^/public$#', '/', $path);
        
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
}
