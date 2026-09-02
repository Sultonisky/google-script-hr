<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: mpr.auth
 *
 * Guards HRIS-domain routes for users authenticated via hr_user session.
 *
 * Rules:
 *   1. Must be authenticated via hr_user session (HRIS authentication).
 *   2. If auth_domain is 'mpr_requestor': restrict to ALLOWED_PREFIXES only.
 *   3. If auth_domain is 'users': pass through (hr.auth middleware handles RBAC).
 *
 * This middleware does NOT accept mpr_requestor_auth sessions.
 * MPR domain routes are protected by EnsureMprAuthenticated instead.
 */
class MprRequestorMiddleware
{
    // Routes accessible by an MPR Requestor (pattern-matched against full path)
    protected const ALLOWED_PREFIXES = [
        '/hr/mpr',
        '/hr/refresh-data',
        '/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $hrUser = session('hr_user');

        // Not authenticated at all — redirect to login
        if (!$hrUser) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Session berakhir. Silakan login ulang.',
                ], 401);
            }
            return redirect()->route('login')
                ->with('error', 'Silakan masuk terlebih dahulu untuk mengakses sistem HR.');
        }

        // If the session belongs to an MPR Requestor, enforce strict route restriction
        $authDomain = $hrUser['auth_domain'] ?? 'users';
        $role       = $hrUser['role']        ?? 'Viewer';

        // Restrict MPR requestor sessions to MPR routes only
        if ($authDomain === 'mpr_requestor') {
            // For mpr_requestor domain: verify role is valid
            if (!in_array(strtolower($role), ['manpower', 'manager'], true)) {
                session()->forget('hr_user');
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Akun ini tidak memiliki akses ke sistem.',
                    ], 403);
                }
                return redirect()->route('login')
                    ->with('error', 'Akun ini tidak memiliki akses yang valid.');
            }

            // Route restriction: MPR Requestors can ONLY access /hr/mpr* and /logout
            $path    = '/' . ltrim($request->path(), '/');
            $allowed = false;
            foreach (self::ALLOWED_PREFIXES as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                if ($path === '/hr/dashboard') {
                    return redirect()->route('mpr.auth.request')
                        ->with('error', 'Anda hanya dapat mengakses halaman Manpower Request (MPR).');
                }
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Anda tidak memiliki akses ke halaman ini.',
                    ], 403);
                }
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        return $next($request);
    }
}
