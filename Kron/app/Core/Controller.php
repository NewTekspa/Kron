<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $data['authUser'] = Auth::user();
        $data['isAdmin'] = Auth::isAdmin();
        $data['roleName'] = Auth::roleName();
        $data['basePath'] = $GLOBALS['config']['base_path'] ?? '';
        extract($data, EXTR_SKIP);

        $path = __DIR__ . '/../Views/' . $view . '.php';
        if (! file_exists($path)) {
            http_response_code(500);
            echo 'Vista no encontrada.';
            return;
        }

        require $path;
    }

    protected function redirect(string $path): void
    {
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        header('Location: ' . $basePath . $path);
        exit;
    }

    protected function requireLogin(): void
    {
        if (! Auth::check()) {
            $this->redirect('/acceso');
        }
    }

    protected function requireAdmin(): void
    {
        if (! Auth::isAdmin()) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }
}
