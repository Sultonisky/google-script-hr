<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: mpr.auth
 *
 * Guards HRIS-domain routes that are accessible via hr_user session.
 *
 * Rules:
 *   1. Must be authenticated via hr_user session (HRIS authentication).
 *   2. Internal HRIS users (auth_domain = 'users') pass through;
 *      hr.auth middleware and Gate RBAC handle per-route authorization.
 *
 * This middleware does NOT read mpr_requestor_auth and does NOT perform any
 * cross-portal session inspection. MPR domain routes are protected by
 * EnsureMprAuthenticated instead.
 *
 * The legacy branch that checked auth_domain = 'mpr_requestor' inside
 * hr_user has been removed: AuthService never stores that value in hr_user,
 * so the branch was dead code that opened a crossover path.
 */
class MprRequestorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $hrUser = session('hr_user');

        // Not authenticated at all — redirect to HRIS login.
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

        // All remaining authorization (role, permissions, RBAC) is handled
        // downstream by Gate via CheckRole / can: middleware.
        return $next($request);
    }
}
