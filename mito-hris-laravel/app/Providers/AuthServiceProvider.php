<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Support\Rbac;
use App\Support\PermissionCatalog;
use App\Services\PermissionResolver;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ==============================================================
        // Resolve Gate's user from the session belonging to the current portal.
        //
        // Gate internally calls: call_user_func($app['auth']->userResolver())
        // AuthManager::resolveUsersUsing() replaces that callable globally.
        //
        // This is the single correct fix for all 403s on protected routes.
        // ==============================================================
        Auth::resolveUsersUsing(function () {
            return match (request()->attributes->get('portal', 'hris')) {
                'assets' => session('asset_auth'),
                'certificates' => session('certificate_auth'),
                'mpr' => session(config('mpr.session_key', 'mpr_requestor_auth')),
                default => session('hr_user'),
            };
        });

        // ==============================================================
        // Super Admin bypass: any ability returns true immediately.
        // $user is now the session array resolved above.
        // ==============================================================
        Gate::before(function ($user, $ability) {
            if (Rbac::normalizeRole($user['role'] ?? null) === 'Super Admin') {
                return true;
            }
            return null;
        });

        // ==============================================================
        // Define a gate for every permission declared in config/hris.php.
        // Skips '*' — that is only a wildcard marker, not a real ability.
        // Super Admin bypasses these via Gate::before above.
        // ==============================================================
        $allPermissions = PermissionCatalog::keys();

        foreach ($allPermissions as $permission) {
            if ($permission === '*') {
                continue; // wildcard marker — handled by Gate::before
            }

            Gate::define($permission, function ($user) use ($permission) {
                return app(PermissionResolver::class)->allows($user, $permission);
            });
        }

        // ==============================================================
        // Composite ability used by the Asset / Certification employee
        // pickers: the picker only returns id/name/division/department, so
        // any role that can open those modules (or the full employee
        // directory) is allowed. Asset and certificate users must NOT need the whole
        // view_employees permission just to pick an assignee.
        // ==============================================================
        Gate::define('access_assets_portal', fn ($user) => app(PermissionResolver::class)->allows($user, 'assets.access'));
        Gate::define('access_certificates_portal', fn ($user) => app(PermissionResolver::class)->allows($user, 'certificates.access'));
    }
}