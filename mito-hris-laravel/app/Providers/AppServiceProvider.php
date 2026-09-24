<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Repositories\GoogleSheets\UserSheetsRepository;
use App\Repositories\GoogleSheets\UserPermissionSheetsRepository;
use App\Repositories\GoogleSheets\PermissionCatalogSheetsRepository;
use App\Repositories\Database\UserDatabaseRepository;
use App\Repositories\Local\ArrayEmployeeDocumentRepository;
use App\Repositories\Local\ArrayUserPermissionRepository;
use App\Repositories\Local\StaticPermissionCatalogRepository;
use App\Repositories\Local\LocalEmployeeRepository;
use App\Repositories\Sheets\AuditLogSheetsRepository;
use App\Repositories\Sheets\CandidateSheetsRepository;
use App\Repositories\Sheets\EmployeeDocumentSheetsRepository;
use App\Repositories\Sheets\EmployeeSheetsRepository;
use App\Repositories\Sheets\MprSheetsRepository;
use App\Repositories\Sheets\MprRequestorSheetsRepository;
use App\Support\PermissionCatalog;
use App\Services\PermissionResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Data source selection is driven by google.enabled config, not APP_ENV.
        // This decouples data source from environment:
        // - Local dev with Google Sheets configured → use Sheets repositories
        // - Testing/CI without Google Sheets → use local/mock repositories
        // - Production with Google Sheets → use Sheets repositories
        if (config('google.enabled', false)) {
            $this->app->bind(UserRepositoryInterface::class, UserSheetsRepository::class);
            $this->app->singleton(UserPermissionRepositoryInterface::class, UserPermissionSheetsRepository::class);
            $this->app->singleton(PermissionCatalogRepositoryInterface::class, PermissionCatalogSheetsRepository::class);
            $this->app->bind(EmployeeRepositoryInterface::class, EmployeeSheetsRepository::class);
            $this->app->bind(EmployeeDocumentRepositoryInterface::class, EmployeeDocumentSheetsRepository::class);
        } else {
            $this->app->bind(UserRepositoryInterface::class, UserDatabaseRepository::class);
            $this->app->singleton(UserPermissionRepositoryInterface::class, ArrayUserPermissionRepository::class);
            $this->app->singleton(PermissionCatalogRepositoryInterface::class, StaticPermissionCatalogRepository::class);
            $this->app->bind(EmployeeRepositoryInterface::class, LocalEmployeeRepository::class);
            $this->app->singleton(EmployeeDocumentRepositoryInterface::class, ArrayEmployeeDocumentRepository::class);
        }
        $this->app->singleton(PermissionResolver::class);
        $this->app->bind(CandidateRepositoryInterface::class, CandidateSheetsRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogSheetsRepository::class);
        $this->app->bind(MprRepositoryInterface::class, MprSheetsRepository::class);
        $this->app->bind(MprRequestorRepositoryInterface::class, MprRequestorSheetsRepository::class);
    }

    public function boot(): void
    {
        $loginLimiter = function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('identifier', '')));

            return Limit::perMinute(5)->by($identifier . '|' . $request->ip());
        };

        RateLimiter::for('login', $loginLimiter);
        RateLimiter::for('mpr-login', $loginLimiter);

        RateLimiter::for('career-apply', function (Request $request) {
            $nik = preg_replace('/\D+/', '', (string) $request->input('nik', ''));

            return Limit::perMinute(8)->by(($nik !== '' ? $nik : 'anon') . '|' . $request->ip());
        });

        RateLimiter::for('outsource-apply', function (Request $request) {
            $nik = preg_replace('/\D+/', '', (string) $request->input('nik', ''));

            return Limit::perMinute(8)->by(($nik !== '' ? $nik : 'anon') . '|' . $request->ip());
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

            $permissions = array_values(array_filter(
                PermissionCatalog::keys(),
                fn (string $permission): bool => Gate::allows($permission)
            ));

            $view->with('user', $user);
            $view->with('permissions', $permissions);
        });

        Vite::useScriptTagAttributes(function (): array {
            $nonce = request()->attributes->get('csp_nonce');

            return is_string($nonce) && $nonce !== '' ? ['nonce' => $nonce] : [];
        });
    }
}