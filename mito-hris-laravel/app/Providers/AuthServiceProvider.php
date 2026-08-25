<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ==============================================================
        // FIX: Override the Auth user resolver so that Laravel's Gate
        // (and can: middleware) reads session('hr_user') instead of
        // Auth::user() — which returns null in this session-based,
        // non-Eloquent architecture.
        //
        // Gate internally calls: call_user_func($app['auth']->userResolver())
        // AuthManager::resolveUsersUsing() replaces that callable globally.
        //
        // This is the single correct fix for all 403s on protected routes.
        // ==============================================================
        Auth::resolveUsersUsing(function () {
            return session('hr_user');
        });

        // ==============================================================
        // Super Admin bypass: any ability returns true immediately.
        // $user is now the session array resolved above.
        // ==============================================================
        Gate::before(function ($user, $ability) {
            $role = $user['role'] ?? 'Viewer';
            if ($role === 'Super Admin') {
                return true;
            }
            return null;
        });

        // ==============================================================
        // Define a gate for every permission declared in config/hris.php.
        // Skips '*' — that is only a wildcard marker, not a real ability.
        // Super Admin bypasses these via Gate::before above.
        // ==============================================================
        $permissions = config('hris.auth.role_permissions', []);
        $allPermissions = array_unique(array_merge(...array_values($permissions)));

        foreach ($allPermissions as $permission) {
            if ($permission === '*') {
                continue; // wildcard marker — handled by Gate::before
            }

            Gate::define($permission, function ($user) use ($permission) {
                $role = $user['role'] ?? 'Viewer';
                $perms = config('hris.auth.role_permissions', [])[$role] ?? [];
                return in_array($permission, $perms, true);
            });
        }
    }
}