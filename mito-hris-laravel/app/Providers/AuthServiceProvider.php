<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Support\Rbac;

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
            if (in_array($ability, ['access_assets_portal', 'access_certificates_portal'], true)) {
                $portal = $ability === 'access_assets_portal' ? 'assets' : 'certificates';

                return Rbac::allowsDedicatedPortal($user, $portal);
            }

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
        $permissions = config('hris.auth.role_permissions', []);
        $allPermissions = array_unique(array_merge(...array_values($permissions)));

        foreach ($allPermissions as $permission) {
            if ($permission === '*') {
                continue; // wildcard marker — handled by Gate::before
            }

            Gate::define($permission, function ($user) use ($permission) {
                return Rbac::allows($user['role'] ?? null, $permission);
            });
        }

        // ==============================================================
        // Composite ability used by the Asset / Certification employee
        // pickers: the picker only returns id/name/division/department, so
        // any role that can open those modules (or the full employee
        // directory) is allowed. GA_IT and LEGAL must NOT need the whole
        // view_employees permission just to pick an assignee.
        // ==============================================================
        Gate::define('lookup_employee', function ($user) {
            $role = $user['role'] ?? null;

            return Rbac::allows($role, 'view_employees')
                || Rbac::allows($role, 'view_asset')
                || Rbac::allows($role, 'view_certification');
        });

        Gate::define('access_assets_portal', fn ($user) => Rbac::allowsDedicatedPortal($user, 'assets'));
        Gate::define('access_certificates_portal', fn ($user) => Rbac::allowsDedicatedPortal($user, 'certificates'));
    }
}