<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Deploy endpoint untuk GitHub Actions (protected by secret)
Route::post('/deploy/migrate', function () {
    $secret = request()->header('X-Deploy-Secret');
    $expectedSecret = env('DEPLOY_SECRET'); // Use env() directly to bypass config cache

    if (!$secret || !$expectedSecret || $secret !== $expectedSecret) {
        return response()->json(['error' => 'Unauthorized'], 401);
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
