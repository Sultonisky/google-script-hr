<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\OutsourceApplyController;
use App\Http\Controllers\HR\DashboardController;
use App\Http\Controllers\HR\RecruitmentController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\ProbationController;
use App\Http\Controllers\HR\OutsourceController;
use App\Http\Controllers\HR\AuditLogController;
use App\Http\Controllers\HR\MasterDataController;
use App\Http\Controllers\HR\SettingsController;
use App\Http\Controllers\HR\UserController;
use App\Http\Controllers\HR\ExportController;

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
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::any('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================================================================
// DOMAIN 2: PUBLIC CAREER & APPLICANT PORTAL (with Landing & Consent Gate)
// =========================================================================
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
Route::prefix('hr')->name('hr.')->middleware('hr.auth')->group(function () {
    // 1. Dashboard — all authenticated users can view
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Recruitment Pipeline — Super Admin, HR Manager, HR Recruitment
    Route::prefix('recruitment')->name('recruitment.')->middleware('role:Super Admin,HR Manager,HR Recruitment')->group(function () {
        Route::get('/', [RecruitmentController::class, 'index'])->name('index');
        Route::get('/accepted', [RecruitmentController::class, 'accepted'])->name('accepted');
        Route::get('/hold', [RecruitmentController::class, 'holdPage'])->name('hold');
        Route::get('/blacklist', [RecruitmentController::class, 'blacklistPage'])->name('blacklist');

        Route::post('/{id}/status', [RecruitmentController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/hold', [RecruitmentController::class, 'hold'])->name('hold.post');
        Route::post('/{id}/blacklist', [RecruitmentController::class, 'blacklist'])->name('blacklist.post');
        Route::post('/{id}/accept', [RecruitmentController::class, 'accept'])->name('accept');
        Route::post('/{id}/save-notes', [RecruitmentController::class, 'saveNotes'])->name('save-notes');
        Route::post('/{id}/move-status', [RecruitmentController::class, 'moveStatus'])->name('move-status');
        Route::post('/{id}/save-offering', [RecruitmentController::class, 'saveOffering'])->name('save-offering');
        Route::post('/{id}/save-offering-response', [RecruitmentController::class, 'saveOfferingResponse'])->name('save-offering-response');
        Route::post('/{id}/save-contract', [RecruitmentController::class, 'saveContract'])->name('save-contract');
        Route::get('/{id}/json', [RecruitmentController::class, 'getJson'])->name('json');
    });

    // 3. Master Data Employee — Super Admin, HR Manager
    Route::prefix('employees')->name('employees.')->middleware('role:Super Admin,HR Manager')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import');
        Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate');
        Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard');
        Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract');
        Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json');
    });

    // 4. Employee Lifecycle — Super Admin, HR Manager
    Route::prefix('probation')->name('probation.')->middleware('role:Super Admin,HR Manager')->group(function () {
        Route::get('/', [ProbationController::class, 'index'])->name('index');
        Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate');
    });
    Route::prefix('outsource')->name('outsource.')->middleware('role:Super Admin,HR Manager,HR Recruitment')->group(function () {
        Route::get('/', [OutsourceController::class, 'index'])->name('index');
    });

    // 5. System Management — Super Admin, HR Manager
    Route::prefix('audit-logs')->name('audit-logs.')->middleware('role:Super Admin,HR Manager')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
    });
    Route::prefix('master-data')->name('master-data.')->middleware('role:Super Admin,HR Manager')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::post('/', [MasterDataController::class, 'store'])->name('store');
    });
    Route::prefix('settings')->name('settings.')->middleware('role:Super Admin,HR Manager')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
    });
    Route::prefix('users')->name('users.')->middleware('role:Super Admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
    });

    // 6. PDF Generation & CSV Exports — Super Admin, HR Manager, HR Recruitment
    Route::prefix('export')->name('export.')->middleware('role:Super Admin,HR Manager,HR Recruitment')->group(function () {
        Route::get('/candidate-pdf/{id}', [ExportController::class, 'candidatePdf'])->name('candidate-pdf');
        Route::get('/offering-letter/{id}', [ExportController::class, 'offeringLetterPdf'])->name('offering-letter');
        Route::get('/kontrak-pkwt/{id}', [ExportController::class, 'kontrakPkwtPdf'])->name('kontrak-pkwt');
        Route::get('/sk-pengangkatan/{id}', [ExportController::class, 'skPengangkatanPdf'])->name('sk-pengangkatan');
        Route::get('/sk-off/{id}', [ExportController::class, 'skOffPdf'])->name('sk-off');
        Route::get('/sk-rotation/{id}', [ExportController::class, 'skRotationPdf'])->name('sk-rotation');
        Route::get('/surat-bpjs/{id}', [ExportController::class, 'suratBpjsPdf'])->name('surat-bpjs');
        Route::get('/paklaring/{id}', [ExportController::class, 'paklaringPdf'])->name('paklaring');

        Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
        Route::get('/employees-csv', [ExportController::class, 'exportEmployeesCsv'])->name('employees-csv');
    });
});
