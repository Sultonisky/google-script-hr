<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMprAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $user = session($sessionKey);

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akun tidak memiliki akses ke MPR.',
                ], 401);
            }

            return redirect()->route('mpr.auth.login')->with('error', 'Akun tidak memiliki akses ke MPR.');
        }

        if (($user['auth_domain'] ?? '') !== 'mpr_requestor') {
            session()->forget($sessionKey);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akun tidak memiliki akses ke MPR.',
                ], 403);
            }

            return redirect()->route('mpr.auth.login')->with('error', 'Akun tidak memiliki akses ke MPR.');
        }

        return $next($request);
    }
}
