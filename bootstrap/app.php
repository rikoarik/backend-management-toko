<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'deploy/migrate',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Multipart/mobile clients may omit Accept: application/json; still treat /api/* as JSON so
        // auth failures return 401 instead of redirecting to a non-existent named login route.
        $exceptions->shouldRenderJsonWhen(
            function (Request $request, Throwable $e): bool {
                return $request->is('api/*') || $request->expectsJson();
            }
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $statusCode = 500;
                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                    $response['message'] = 'Resource not found.';
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $statusCode = 401;
                    $response['message'] = 'Unauthenticated.';
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                    $response['message'] = 'Validation failed.';
                    $response['errors'] = $e->errors();
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $statusCode = $e->getStatusCode();
                }

                if (config('app.debug') && $statusCode == 500) {
                    $response['trace'] = $e->getTrace();
                }

                return response()->json($response, $statusCode);
            }
        });
    })->create();
