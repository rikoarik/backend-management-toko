<?php
/**
 * Direct Migration Script - bypasses Laravel routing and caching
 * URL: /deploy-migrate.php
 * Method: POST with X-Deploy-Secret header
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Laravel (paths relative to public/ directory)
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force reload .env to bypass any cached config
if (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();
}

// Get secret from header
$secret = $_SERVER['HTTP_X_DEPLOY_SECRET'] ?? '';
$expectedSecret = $_ENV['DEPLOY_SECRET'] ?? getenv('DEPLOY_SECRET');

header('Content-Type: application/json');

// Debug info
$debug = [
    'secret_provided' => !empty($secret),
    'expected_secret_loaded' => !empty($expectedSecret),
    'secrets_match' => $secret === $expectedSecret,
    'env_file_exists' => file_exists(__DIR__ . '/.env'),
    'php_version' => PHP_VERSION,
];

if (!$secret || !$expectedSecret || $secret !== $expectedSecret) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'debug' => $debug,
    ]);
    exit;
}

try {
    // Use Artisan facade through the app instance
    $artisan = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // Run migration
    $migrateStatus = $artisan->call('migrate', ['--force' => true]);
    $migrateOutput = $artisan->output();

    // Clear caches
    $artisan->call('config:clear');
    $artisan->call('cache:clear');
    $artisan->call('view:clear');
    $artisan->call('route:clear');

    // Re-cache
    $artisan->call('config:cache');
    $artisan->call('route:cache');
    $artisan->call('view:cache');

    echo json_encode([
        'success' => true,
        'message' => 'Migration and cache refresh completed',
        'migrate_output' => $migrateOutput,
        'migrate_status' => $migrateStatus,
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
