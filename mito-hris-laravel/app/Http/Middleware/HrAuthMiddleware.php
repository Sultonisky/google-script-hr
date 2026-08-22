<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HrAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('hr_user')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Session berakhir. Silakan login ulang.'], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses sistem HR.');
        }

        return $next($request);
    }
}
