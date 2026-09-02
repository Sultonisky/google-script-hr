<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PortalAccessMiddleware
 *
 * Enforces strict per-portal session isolation:
 *
 *   HRIS portal  → reads ONLY hr_user session   (auth_domain = 'users')
 *   MPR portal   → reads ONLY mpr_requestor_auth (auth_domain = 'mpr_requestor')
 *
 * A session from the other portal is NEVER used as a fallback, NEVER read for
 * role inference, and NEVER used to determine the redirect destination.
 * This ensures that identical username/email across both account stores cannot
 * cause any authentication crossover.
 */
class PortalAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $portal       = $request->attributes->get('portal', 'public');
        $portalAccess = $request->attributes->get('portal_access', 'public');

        if ($portalAccess !== 'private') {
            return $next($request);
        }

        // ── HRIS portal: ONLY hr_user ─────────────────────────────────────────
        if ($portal === 'hris') {
            $user = session('hr_user', []);

            if (empty($user)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Akses ditolak. Silakan login terlebih dahulu.',
                    ], 401);
                }

                return redirect()->route('login')
                    ->with('error', 'Silakan login terlebih dahulu untuk mengakses sistem HR.');
            }

            // Verify the session actually belongs to the HRIS domain.
            // AuthService always sets auth_domain = 'users'; any other value is invalid.
            $authDomain = $user['auth_domain'] ?? 'users';
            if ($authDomain !== 'users') {
                session()->forget('hr_user');

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Autentikasi portal tidak valid.',
                    ], 403);
                }

                return redirect()->route('login')
                    ->with('error', 'Autentikasi portal tidak valid. Silakan login ulang.');
            }

            return $next($request);
        }

        // ── MPR portal: ONLY mpr_requestor_auth ──────────────────────────────
        if ($portal === 'mpr') {
            $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
            $user       = session($sessionKey, []);

            if (empty($user)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Akses ditolak. Silakan login terlebih dahulu.',
                    ], 401);
                }

                return redirect()->route('mpr.auth.login')
                    ->with('error', 'Silakan login terlebih dahulu untuk mengakses portal MPR.');
            }

            // Verify the session belongs to the MPR domain.
            // MprAuthController always sets auth_domain = 'mpr_requestor'.
            $authDomain = $user['auth_domain'] ?? '';
            if ($authDomain !== 'mpr_requestor') {
                session()->forget($sessionKey);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Autentikasi portal tidak valid.',
                    ], 403);
                }

                return redirect()->route('mpr.auth.login')
                    ->with('error', 'Autentikasi portal tidak valid. Silakan login ulang.');
            }

            return $next($request);
        }

        // Unknown portal type — deny access.
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => 'Portal tidak dikenal.'], 403);
        }

        abort(403, 'Portal tidak dikenal.');
    }
}
