<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health Check Endpoint (no auth required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()->toISOString(),
        'app' => config('app.name'),
    ]);
});

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/users/me', [AuthController::class, 'me']); // Alias

        Route::apiResource('users', \App\Http\Controllers\UserController::class)->only(['index', 'show', 'update']);
        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
        Route::apiResource('products', \App\Http\Controllers\ProductController::class);
        Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class);

        Route::prefix('reports')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\ReportController::class, 'dashboard']);
            Route::get('sales', [\App\Http\Controllers\ReportController::class, 'sales']);
        });
    });
});

