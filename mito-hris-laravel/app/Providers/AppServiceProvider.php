<?php

namespace App\Providers;

use App\Events\CandidateApplied;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use App\Listeners\InvalidateCacheListener;
use App\Listeners\WriteAuditLogListener;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Sheets\AuditLogSheetsRepository;
use App\Repositories\Sheets\CandidateSheetsRepository;
use App\Repositories\Sheets\EmployeeSheetsRepository;
use App\Repositories\Sheets\UserSheetsRepository;
use App\Services\Google\GoogleClientFactory;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Google Services Singletons
        $this->app->singleton(GoogleClientFactory::class, fn() => new GoogleClientFactory());
        $this->app->singleton(GoogleSheetsService::class, fn($app) => new GoogleSheetsService($app->make(GoogleClientFactory::class)));
        $this->app->singleton(GoogleDriveService::class, fn($app) => new GoogleDriveService($app->make(GoogleClientFactory::class)));

        // Repository Bindings
        $this->app->bind(CandidateRepositoryInterface::class, CandidateSheetsRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeSheetsRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogSheetsRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserSheetsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event Listeners Auto-Registration
        Event::listen(CandidateApplied::class, [WriteAuditLogListener::class, 'handle']);
        Event::listen(CandidateApplied::class, [InvalidateCacheListener::class, 'handle']);

        Event::listen(CandidateStatusChanged::class, [WriteAuditLogListener::class, 'handle']);
        Event::listen(CandidateStatusChanged::class, [InvalidateCacheListener::class, 'handle']);

        Event::listen(EmployeeHired::class, [WriteAuditLogListener::class, 'handle']);
        Event::listen(EmployeeHired::class, [InvalidateCacheListener::class, 'handle']);
    }
}
