<?php

use App\Kernel;

$_SERVER['APP_ENV'] = 'prod';
$_SERVER['APP_DEBUG'] = '1';

// Custom: Load .env.prod.php if it exists
if (file_exists($prodEnv = dirname(__DIR__).'/.env.prod.php')) {
    $env = require $prodEnv;
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_SERVER[$k] = (string) $v;
    }
    // Ensure APP_ENV is set to prod if it was in the file
    if (!isset($_SERVER['APP_ENV'])) {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] ?? 'prod';
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
