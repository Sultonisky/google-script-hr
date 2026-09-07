<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $host = strtolower($request->getHost());
        $configuredDomains = config('hris.domains', []);

        foreach (['hris', 'mpr', 'assets', 'certificates'] as $privatePortal) {
            if ($host === strtolower((string) ($configuredDomains[$privatePortal] ?? ''))) {
                $portal = $privatePortal;
                $portalAccess = 'private';
                break;
            }
        }

        if ($portal === 'public' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            $path = strtolower(ltrim((string) $request->path(), '/'));

            if (str_starts_with($path, 'hr')) {
                $portal = 'hris';
                $portalAccess = 'private';
            } elseif (str_starts_with($path, 'mpr')) {
                $portal = 'mpr';
                $portalAccess = 'private';
            }
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, [
            'hris.domain.root',
            'auth.portal',
            'login',
            'login.post',
            'logout',
            'mpr.auth.domain.root',
            'mpr.auth.portal',
            'mpr.auth.login',
            'mpr.auth.login.post',
            'mpr.auth.logout',
            'assets.login',
            'assets.login.post',
            'assets.logout',
            'certificates.domain.root',
            'certificates.login',
            'certificates.login.post',
            'certificates.logout',
        ], true)) {
            return $next($request);
        }

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

        // ── Asset / Certificate portal: dedicated portal session only ──
        if ($portal === 'assets' || $portal === 'certificates') {
            $requiredGate = $portal === 'assets' ? 'access_assets_portal' : 'access_certificates_portal';
            $loginRoute   = $portal === 'assets' ? 'assets.login' : 'certificates.login';
            $sessionKey   = $portal === 'assets' ? 'asset_auth' : 'certificate_auth';
            $user         = session($sessionKey, []);

            if (empty($user)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Akses ditolak. Silakan login terlebih dahulu.',
                    ], 401);
                }

                return redirect()->route($loginRoute)
                    ->with('error', 'Silakan login terlebih dahulu untuk mengakses portal ini.');
            }

            $authDomain = $user['auth_domain'] ?? '';
            $expectedDomain = $portal === 'assets' ? 'assets' : 'certificates';
            if ($authDomain !== $expectedDomain) {
                session()->forget($sessionKey);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Autentikasi portal tidak valid.',
                    ], 403);
                }

                return redirect()->route($loginRoute)
                    ->with('error', 'Autentikasi portal tidak valid. Silakan login ulang.');
            }

            if (!Gate::forUser($user)->allows($requiredGate)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Anda tidak memiliki akses ke portal ini.',
                    ], 403);
                }

                abort(403, 'Anda tidak memiliki akses ke portal ini.');
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
