<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: mpr.auth
 *
 * Protects routes that a Manager (from mpr_requestor) is trying to access.
 *
 * Rules:
 *   1. Must be authenticated (session hr_user exists).
 *   2. If identity is an MPR Requestor (auth_domain = mpr_requestor):
 *      - role MUST be Manager
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
        '/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = session('hr_user');

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

        // Restrict any Manager session (regardless of auth_domain) to MPR routes only.
        // This covers both legitimate MPR Requestors (auth_domain=mpr_requestor) and
        // any anomalous case where a Manager ends up in the Users sheet.
        if ($authDomain === 'mpr_requestor' || strtolower($role) === 'manager') {
            // For mpr_requestor domain: additional role sanity check
            if ($authDomain === 'mpr_requestor' && strtolower($role) !== 'manager') {
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

            // Route restriction: Manager can ONLY access /hr/mpr* and /logout
            $path    = '/' . ltrim($request->path(), '/');
            $allowed = false;
            foreach (self::ALLOWED_PREFIXES as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Anda tidak memiliki akses ke halaman ini.',
                    ], 403);
                }
                return redirect()->route('hr.mpr.index')
                    ->with('error', 'Anda hanya dapat mengakses halaman Manpower Request (MPR).');
            }
        }

        return $next($request);
    }
}
