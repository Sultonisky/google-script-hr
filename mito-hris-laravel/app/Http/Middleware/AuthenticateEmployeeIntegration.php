<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateEmployeeIntegration
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('hris.integration.employee_api_token', '');

        if ($expectedToken === '') {
            Log::error('Employee integration API token is not configured.');

            return response()->json([
                'success' => false,
                'message' => 'Employee integration API is not configured.',
            ], 503);
        }

        $providedToken = (string) $request->bearerToken();

        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
