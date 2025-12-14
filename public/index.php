<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

// 1. Force Errors to display in Debug Mode (Critical for diagnosing issues on cPanel)
// These will only show if APP_DEBUG=1 is set in your env file.
ini_set('display_errors', $_SERVER['APP_DEBUG'] ?? 0);
ini_set('display_startup_errors', $_SERVER['APP_DEBUG'] ?? 0);
error_reporting(E_ALL);

// 2. Define Project Root
$projectRoot = dirname(__DIR__);

// 3. Load Autoloader
if (!file_exists($projectRoot . '/vendor/autoload.php')) {
    die("Vendor autoload missing. Run composer install.");
}
require_once $projectRoot . '/vendor/autoload.php';


// 4. Custom Env Loading (for .env.prod.php)
$existingEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV');
// Skip .env.prod.php if in Docker (likely local dev) or explicitly dev/test
if (file_exists($projectRoot . '/.env.prod.php') && $existingEnv !== 'dev' && $existingEnv !== 'test' && !file_exists('/.dockerenv')) {
    $env = require $projectRoot . '/.env.prod.php';
    foreach ($env as $k => $v) {
        $_ENV[$k] = $_SERVER[$k] = (string) $v;
    }
} elseif (class_exists(Symfony\Component\Dotenv\Dotenv::class) && file_exists($projectRoot . '/.env')) {
    // Fallback to .env files for Local Development
    (new Symfony\Component\Dotenv\Dotenv())->bootEnv($projectRoot . '/.env');
}

// 5. Classic Symfony Boot (Bypassing specific Runtime wrapper to avoid 500s)
// This is the standard index.php from Symfony 4/5, which is more robust on shared hosting.

$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
