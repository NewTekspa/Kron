<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-server.err.log');
error_reporting(E_ALL);

$app = require __DIR__ . '/../bootstrap/app.php';

session_start();

$app['router']->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
