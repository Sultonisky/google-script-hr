<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\OutsourceApplyController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\HR\DashboardController;
use App\Http\Controllers\HR\RecruitmentController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\ProbationController;
use App\Http\Controllers\HR\OutsourceController;
use App\Http\Controllers\HR\AuditLogController;
use App\Http\Controllers\HR\MasterDataController;
use App\Http\Controllers\HR\SettingsController;
use App\Http\Controllers\HR\UserController;
use App\Http\Controllers\HR\MprRequestorController;
use App\Http\Controllers\HR\ExportController;
use App\Http\Controllers\HR\MprController;

/*
|--------------------------------------------------------------------------
| Web Routes - MITO HRIS
| 1:1 Mapping with Google Apps Script Routing Architecture
|--------------------------------------------------------------------------
*/

// =========================================================================
// DOMAIN 1: AUTHENTICATION & LOGIN (Manual GSheets Auth)
// =========================================================================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:login')
    ->name('login.post');
Route::any('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================================================================
// DOMAIN 2: PUBLIC CAREER & APPLICANT PORTAL (with Landing & Consent Gate)
// =========================================================================
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('public.seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('public.seo.sitemap');
Route::get('/', [CareerController::class, 'index'])->name('public.career.index');
Route::post('/career/consent', [CareerController::class, 'consent'])->name('public.career.consent');
Route::get('/apply', [CareerController::class, 'form'])->name('public.career.form');
Route::post('/apply', [CareerController::class, 'store'])->name('public.career.store');
Route::get('/career/success', [CareerController::class, 'success'])->name('public.career.success');
Route::get('/check-status', [CareerController::class, 'checkStatus'])->name('public.career.check-status');
Route::get('/self-update/{id}', [CareerController::class, 'selfUpdate'])->name('public.career.self-update');
Route::post('/self-update/{id}', [CareerController::class, 'storeSelfUpdate'])->name('public.career.self-update.store');

// Outsource Public Registration (1:1 from GAS OutsourceForm.html)
Route::get('/outsource/apply', [OutsourceApplyController::class, 'index'])->name('public.outsource.index');
Route::post('/outsource/apply', [OutsourceApplyController::class, 'store'])->name('public.outsource.store');

// =========================================================================
// DOMAIN 3: HR INTERNAL MANAGEMENT SYSTEM (Protected by hr.auth Middleware)
// =========================================================================
Route::prefix('hr')->name('hr.')->middleware(['hr.auth', 'mpr.auth'])->group(function () {
    Route::post('/refresh-data', [\App\Http\Controllers\HR\RefreshController::class, 'refreshData'])->name('refresh-data');

    // 1. Dashboard — all authenticated users can view
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Recruitment Pipeline — uses can middleware for permission-based access
    Route::prefix('recruitment')->name('recruitment.')->middleware('can:view_recruitment')->group(function () {
        Route::get('/', [RecruitmentController::class, 'index'])->name('index')->middleware('can:view_recruitment');
        Route::get('/accepted', [RecruitmentController::class, 'accepted'])->name('accepted')->middleware('can:view_recruitment');
        Route::get('/hold', [RecruitmentController::class, 'holdPage'])->name('hold')->middleware('can:view_recruitment');
        Route::get('/blacklist', [RecruitmentController::class, 'blacklistPage'])->name('blacklist')->middleware('can:view_recruitment');

        Route::post('/{id}/status', [RecruitmentController::class, 'updateStatus'])->name('update-status')->middleware('can:update_candidates');
        Route::post('/{id}/hold', [RecruitmentController::class, 'hold'])->name('hold.post')->middleware('can:manage_hold_blacklist');
        Route::post('/{id}/blacklist', [RecruitmentController::class, 'blacklist'])->name('blacklist.post')->middleware('can:manage_hold_blacklist');
        Route::post('/{id}/accept', [RecruitmentController::class, 'accept'])->name('accept')->middleware('can:create_offering');
        Route::post('/{id}/save-notes', [RecruitmentController::class, 'saveNotes'])->name('save-notes')->middleware('can:create_offering');
        Route::post('/{id}/move-status', [RecruitmentController::class, 'moveStatus'])->name('move-status')->middleware('can:update_candidates');
        Route::post('/{id}/save-offering', [RecruitmentController::class, 'saveOffering'])->name('save-offering')->middleware('can:create_offering');
        Route::post('/{id}/save-offering-response', [RecruitmentController::class, 'saveOfferingResponse'])->name('save-offering-response')->middleware('can:create_offering');
        Route::post('/{id}/save-contract', [RecruitmentController::class, 'saveContract'])->name('save-contract')->middleware('can:create_offering');
        Route::get('/{id}/json', [RecruitmentController::class, 'getJson'])->name('json')->middleware('can:view_recruitment');
    });

    // 3. Master Data Employee — view_employees for viewing, manage_employees for mutations
    Route::prefix('employees')->name('employees.')->middleware('can:view_employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index')->middleware('can:view_employees');
        // IMPORTANT: literal routes (search, import/*, template) MUST come before wildcard routes ({id})
        // to prevent Laravel routing literal segments as {id} parameter
        Route::get('/search', [EmployeeController::class, 'search'])->name('search')->middleware('can:view_employees');
        // Import — preview (no write) + execute + template download
        Route::post('/import/preview', [EmployeeController::class, 'importPreview'])->name('import.preview')->middleware('can:manage_employees');
        Route::get('/import/template', [EmployeeController::class, 'importTemplate'])->name('import.template')->middleware('can:manage_employees');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import')->middleware('can:manage_employees');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update')->middleware('can:manage_employees');
        Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate')->middleware('can:manage_employees');
        Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard')->middleware('can:manage_employees');
        Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract')->middleware('can:manage_employees');
        Route::post('/{id}/promote-probation', [EmployeeController::class, 'promoteToProbation'])->name('promote-probation')->middleware('can:manage_employees');
        Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json')->middleware('can:view_employees');
    });

    // 4. Employee Lifecycle — Probation requires manage_probation, Outsource requires view_employees
    Route::prefix('probation')->name('probation.')->middleware('can:manage_probation')->group(function () {
        Route::get('/', [ProbationController::class, 'index'])->name('index')->middleware('can:manage_probation');
        Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate')->middleware('can:manage_probation');
        Route::get('/{id}/eval-history', [ProbationController::class, 'evalHistory'])->name('eval-history')->middleware('can:manage_probation');

        // Performance Review preview (HTML, same template as the PDF) —
        // data always resolved from the sheet via route {id}, never query params.
        // PDF downloads use the existing on-demand hr.export.* routes.
        Route::get('/{id}/preview', [ProbationController::class, 'previewPerformanceReview'])->name('preview');
    });
    Route::prefix('outsource')->name('outsource.')->middleware('can:view_employees')->group(function () {
        Route::get('/', [OutsourceController::class, 'index'])->name('index')->middleware('can:view_employees');
    });

    // 5. System Management
    Route::prefix('audit-logs')->name('audit-logs.')->middleware('can:view_reports')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index')->middleware('can:view_reports');
    });
    Route::prefix('master-data')->name('master-data.')->middleware('can:manage_settings')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index')->middleware('can:manage_settings');
        Route::post('/', [MasterDataController::class, 'store'])->name('store')->middleware('can:manage_settings');
    });
    Route::prefix('settings')->name('settings.')->middleware('can:manage_settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index')->middleware('can:manage_settings');
        Route::post('/', [SettingsController::class, 'update'])->name('update')->middleware('can:manage_settings');
    });
    Route::prefix('users')->name('users.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:manage_settings');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:manage_settings');
        Route::put('/{email}', [UserController::class, 'update'])->name('update')->middleware('can:manage_settings');
    });
    Route::prefix('mpr-requestors')->name('mpr-requestors.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
        Route::get('/', [MprRequestorController::class, 'index'])->name('index')->middleware('can:manage_settings');
        Route::post('/', [MprRequestorController::class, 'store'])->name('store')->middleware('can:manage_settings');
        Route::put('/{email}', [MprRequestorController::class, 'update'])->name('update')->middleware('can:manage_settings');
    });

    // 6. Candidate profile print is available to every recruitment viewer.
    Route::prefix('export')->name('export.')->middleware('can:view_recruitment')->group(function () {
        Route::get('/candidate-pdf/{id}', [ExportController::class, 'candidatePdf'])->name('candidate-pdf');
    });

    // Offering Letter follows recruitment permission; lifecycle documents
    // require employee-management permission.
    Route::prefix('export')->name('export.')->middleware('can:create_offering')->group(function () {
        Route::get('/offering-letter/{id}', [ExportController::class, 'offeringLetterPdf'])->name('offering-letter');
    });

    Route::prefix('export')->name('export.')->middleware('can:manage_employees')->group(function () {
        Route::get('/kontrak-pkwt/{id}', [ExportController::class, 'kontrakPkwtPdf'])->name('kontrak-pkwt');
        Route::get('/sk-pengangkatan/{id}', [ExportController::class, 'skPengangkatanPdf'])->name('sk-pengangkatan');
        Route::get('/sk-off/{id}', [ExportController::class, 'skOffPdf'])->name('sk-off');
        Route::get('/sk-rotation/{id}', [ExportController::class, 'skRotationPdf'])->name('sk-rotation');
        Route::get('/surat-bpjs/{id}', [ExportController::class, 'suratBpjsPdf'])->name('surat-bpjs');
        Route::get('/paklaring/{id}', [ExportController::class, 'paklaringPdf'])->name('paklaring');
        Route::get('/offboarding-bundle/{id}', [ExportController::class, 'offboardingBundlePdf'])->name('offboarding-bundle');
        Route::get('/performance-review/{id}', [ExportController::class, 'performanceReviewPdf'])->name('performance-review');
    });

    Route::prefix('export')->name('export.')->middleware('role:Super Admin,Admin,Privileged User')->group(function () {
        Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
        Route::get('/employees-csv', [ExportController::class, 'exportEmployeesCsv'])->name('employees-csv');
    });

    // 7. Manpower Request (MPR) — View, Create, Show, PDF Export
    Route::prefix('mpr')->name('mpr.')->middleware('can:view_mpr')->group(function () {
        Route::get('/', [MprController::class, 'index'])->name('index');
        Route::get('/create', [MprController::class, 'index'])->name('create')->middleware('can:create_mpr');
        Route::post('/', [MprController::class, 'store'])->name('store')->middleware('can:create_mpr');
        Route::put('/{id}', [MprController::class, 'update'])->name('update')->middleware('can:update_mpr');
        Route::get('/{id}/preview', [MprController::class, 'preview'])->name('preview');
        Route::get('/{id}', [MprController::class, 'show'])->name('show');
        Route::get('/{id}/json', [MprController::class, 'getJson'])->name('json');
        Route::get('/{id}/pdf', [MprController::class, 'exportPdf'])->name('pdf')->middleware('can:export_mpr');
    });
});
