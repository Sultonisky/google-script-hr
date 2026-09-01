<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: mpr.auth
 *
 * Protects routes that a Manpower requestor (from mpr_requestor) is trying to access.
 *
 * Rules:
 *   1. Must be authenticated (session hr_user exists).
 *   2. If identity is an MPR Requestor (auth_domain = mpr_requestor):
 *      - role MUST be Manpower
 *      - status is already verified at login time
 *      - ONLY MPR routes are allowed; all other /hr/* routes return 403
 *   3. If identity is an internal HR user (auth_domain = users):
 *      - passes through without restriction (existing hr.auth handles them)
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
        $legacyUser = session('hr_user');
        $dedicatedUser = session(config('mpr.session_key', 'mpr_requestor_auth'));
        $user = $legacyUser ?: $dedicatedUser;

        // Not authenticated at all — redirect to login
        if (!$user) {
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
        $authDomain = $user['auth_domain'] ?? 'users';
        $role       = $user['role']        ?? 'Viewer';

        // Restrict MPR requestor sessions (Manager or Manpower) to MPR routes only.
        // This covers both legitimate MPR Requestors and anomalous cross-domain data.
        if ($authDomain === 'mpr_requestor' || in_array(strtolower($role), ['manpower', 'manager'], true)) {
            // For mpr_requestor domain: additional role sanity check
            if ($authDomain === 'mpr_requestor' && !in_array(strtolower($role), ['manpower', 'manager'], true)) {
                session()->forget('hr_user');
                session()->forget(config('mpr.session_key', 'mpr_requestor_auth'));
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Akun ini tidak memiliki akses ke sistem.',
                    ], 403);
                }
                return redirect()->route('login')
                    ->with('error', 'Akun ini tidak memiliki akses yang valid.');
            }

            // Route restriction: Manpower can ONLY access /hr/mpr* and /logout
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
