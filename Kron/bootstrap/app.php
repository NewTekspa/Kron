<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($path)) {
        require $path;
    }
});

use App\Core\Router;

$config = [
    'app' => require __DIR__ . '/../config/app.php',
    'db' => require __DIR__ . '/../config/database.php',
];

// Hacer disponible la configuración de app globalmente para las vistas
$GLOBALS['config'] = $config['app'];

$router = new Router();
require __DIR__ . '/../config/routes.php';

return [
    'config' => $config,
    'router' => $router,
];
