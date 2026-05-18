<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken()
            ?? $request->header('X-Access-Token')
            ?? $request->header('x-access-token')
            ?? $request->header('token')
            ?? $request->input('access_token')
            ?? $request->input('token');

        if (is_string($bearer)) {
            $bearer = trim($bearer, " \t\n\r\0\x0B\"'");
            $bearer = preg_replace('/^\s*Bearer\s+/i', '', $bearer) ?? '';
        }

        if (!$bearer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($bearer);

        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = $accessToken->tokenable;

        if (property_exists($user, 'is_active') && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);
        $accessToken->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
