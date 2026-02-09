<?php


$isLocal = isset($_SERVER['SERVER_NAME']) && (
    $_SERVER['SERVER_NAME'] === 'localhost' ||
    $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
    stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Development Server') !== false
);

return [
    'name' => 'KRON',
    'env' => $isLocal ? 'local' : 'production',
    'debug' => $isLocal,
    'url' => $isLocal ? 'http://localhost:8000' : 'https://www.newtek.cl/kron',
    'base_path' => $isLocal ? '' : '/kron',
];
