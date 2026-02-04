<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Deploy endpoint untuk GitHub Actions (protected by secret)
Route::post('/deploy/migrate', function () {
    $secret = request()->header('X-Deploy-Secret');
    // Force load .env to bypass config cache issues during deployment
    // This is necessary because if config is cached (but outdated), env() returns null AND config() returns old values.
    if (file_exists(base_path('.env'))) {
        \Dotenv\Dotenv::createImmutable(base_path())->safeLoad();
    }

    $expectedSecret = $_ENV['DEPLOY_SECRET'] ?? env('DEPLOY_SECRET');

    if (!$secret || !$expectedSecret || $secret !== $expectedSecret) {
        return response()->json([
            'error' => 'Unauthorized',
            'debug' => [
                'secret_provided' => !empty($secret),
                'expected_secret_loaded' => !empty($expectedSecret),
                'secrets_match' => $secret === $expectedSecret,
                'env_file_exists' => file_exists(base_path('.env')),
            ]
        ], 401);
    }

    try {
        // Run migration
        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        // Re-cache for production
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return response()->json([
            'success' => true,
            'message' => 'Migration and cache refresh completed',
            'migrate_output' => $migrateOutput,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});
