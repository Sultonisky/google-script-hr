<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = session('hr_user');

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Session berakhir. Silakan login ulang.'], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $userRole = $user['role'] ?? 'Viewer';

        if (!in_array($userRole, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Anda tidak memiliki akses ke halaman ini.'], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}