<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $portal = $request->attributes->get('portal', 'public');
        $portalAccess = $request->attributes->get('portal_access', 'public');

        if ($portalAccess === 'private') {
            $legacyMprUser = session(config('mpr.session_key', 'mpr_requestor_auth'), []);
            $legacyHrisUser = session('hr_user', []);

            $portalUser = $portal === 'mpr'
                ? $legacyMprUser
                : $legacyHrisUser;
            $user = $portalUser ?: ($portal === 'hris' ? $legacyHrisUser : $legacyMprUser);

            $otherPortalSession = $portal === 'mpr'
                ? $legacyHrisUser
                : $legacyMprUser;

            $authDomain = strtolower(trim((string) ($user['auth_domain'] ?? '')));
            $portalFromSession = $user['portal'] ?? null;
            $role = strtolower(trim((string) ($user['role'] ?? '')));

            if ($portalFromSession === null && $authDomain === 'mpr_requestor' && $portal === 'mpr') {
                $portalFromSession = 'mpr';
            }

            if ($portalFromSession === null && $authDomain === 'users' && $portal === 'hris') {
                $portalFromSession = 'hris';
            }

            if ($portalFromSession === null && !empty($user) && $portal === 'hris' && !in_array($role, ['manpower', 'manager'], true)) {
                $portalFromSession = 'hris';
                $authDomain = 'users';
            }

            if ($portalFromSession === null && !empty($user) && $portal === 'mpr' && in_array($role, ['manpower', 'manager'], true)) {
                $portalFromSession = 'mpr';
                $authDomain = 'mpr_requestor';
            }

            if (!empty($otherPortalSession) && empty($user) && (($otherPortalSession['portal'] ?? null) === ($portal === 'mpr' ? 'hris' : 'mpr'))) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'Portal session tidak valid untuk akses ini.'], 403);
                }

                $redirectRoute = $portal === 'mpr' ? 'hr.dashboard' : 'mpr.auth.request';
                return redirect()->route($redirectRoute)->with('error', 'Anda tidak memiliki akses ke portal ini.');
            }

            if (empty($user)) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 401);
                }

                $redirectRoute = $portal === 'mpr' ? 'mpr.auth.login' : 'login';
                return redirect()->route($redirectRoute)->with('error', 'Silakan login terlebih dahulu untuk mengakses portal ini.');
            }

            if ($portalFromSession !== null && $portalFromSession !== $portal) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'Portal session tidak valid untuk akses ini.'], 403);
                }

                $redirectRoute = $portalFromSession === 'mpr' ? 'mpr.auth.request' : 'hr.dashboard';
                return redirect()->route($redirectRoute)->with('error', 'Anda tidak memiliki akses ke portal ini.');
            }

            $path = '/' . ltrim((string) $request->path(), '/');
            $isMprLegacyRoute = $portal === 'hris' && (
                str_starts_with($path, '/hr/mpr') ||
                $path === '/hr/refresh-data'
            );
            $expectedAuthSource = config("hris.portal_access.{$portal}.auth_source", 'none');

            if ($portal === 'hris' && ($authDomain === 'mpr_requestor' || in_array($role, ['manpower', 'manager'], true))) {
                if ($path === '/hr/dashboard') {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'error' => 'Anda hanya dapat mengakses halaman Manpower Request (MPR).'], 403);
                    }

                    return redirect()->route('hr.mpr.create')->with('error', 'Anda hanya dapat mengakses halaman Manpower Request (MPR).');
                }

                if (!$isMprLegacyRoute) {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'error' => 'Anda tidak memiliki akses ke portal HRIS.'], 403);
                    }

                    abort(403, 'Anda tidak memiliki akses ke halaman ini.');
                }
            }

            if ($expectedAuthSource !== 'none' && $authDomain !== $expectedAuthSource && !$isMprLegacyRoute) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => 'Autentikasi portal tidak valid.'], 403);
                }

                $redirectRoute = $portal === 'mpr' ? 'mpr.auth.login' : 'login';
                return redirect()->route($redirectRoute)->with('error', 'Autentikasi portal tidak valid.');
            }
        }

        return $next($request);
    }
}
