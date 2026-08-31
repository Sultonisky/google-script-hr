<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Events\CandidateApplied;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use App\Listeners\InvalidateCacheListener;
use App\Listeners\WriteAuditLogListener;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\GoogleSheets\UserSheetsRepository;
use App\Repositories\Sheets\AuditLogSheetsRepository;
use App\Repositories\Sheets\CandidateSheetsRepository;
use App\Repositories\Sheets\EmployeeSheetsRepository;
use App\Repositories\Sheets\MprSheetsRepository;
use App\Repositories\Sheets\MprRequestorSheetsRepository;
use App\Support\Rbac;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserSheetsRepository::class);
        $this->app->bind(CandidateRepositoryInterface::class, CandidateSheetsRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeSheetsRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogSheetsRepository::class);
        $this->app->bind(MprRepositoryInterface::class, MprSheetsRepository::class);
        $this->app->bind(MprRequestorRepositoryInterface::class, MprRequestorSheetsRepository::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('identifier', '')));

            return Limit::perMinute(5)->by($identifier . '|' . $request->ip());
        });

        RateLimiter::for('mpr-login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // ==============================================================
        // Register Event → Listener mappings.
        // These listeners were previously NOT wired, so cache invalidation
        // and audit log writes were silently skipped on every status change.
        // ==============================================================
        Event::listen([CandidateApplied::class, CandidateStatusChanged::class, EmployeeHired::class], InvalidateCacheListener::class);
        Event::listen([CandidateApplied::class, CandidateStatusChanged::class, EmployeeHired::class], WriteAuditLogListener::class);

        // ==============================================================
        // NOTE: The custom @can / @endcan Blade directive overrides have
        // been removed. Laravel 12 already ships @can / @endcan / @cannot
        // / @endcannot as built-in directives that go through Gate. With
        // the Gate::userResolver() now wired in AuthServiceProvider, the
        // built-in directives work correctly without any override.
        // Defining them here shadowed the compiler and caused conflicts.
        // ==============================================================

        View::composer('*', function ($view) {
            $user = session('hr_user');

            // ===========================================================
            // FIX: Expand wildcard permissions for Super Admin so that
            // the $permissions view variable (used in sidebar @if checks)
            // contains every actual permission name rather than just ['*'].
            // in_array('view_employees', ['*']) is false — this was why
            // "Master Data" was hidden for Super Admin in the sidebar.
            // ===========================================================
            $rawPermissions = Rbac::permissionsForRole($user['role'] ?? null);

            if (in_array('*', $rawPermissions, true)) {
                // Flatten all defined permissions from config, exclude the wildcard itself
                $allRolePerms = config('hris.auth.role_permissions', []);
                $expanded = array_unique(array_merge(...array_values($allRolePerms)));
                $permissions = array_values(array_filter($expanded, fn ($p) => $p !== '*'));
            } else {
                $permissions = $rawPermissions;
            }

            $view->with('user', $user);
            $view->with('permissions', $permissions);
        });
    }
}