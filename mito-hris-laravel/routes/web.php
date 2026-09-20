<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MprAuthController;
use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\OutsourceApplyController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\HR\DashboardController;
use App\Http\Controllers\HR\RecruitmentController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\HR\ProbationController;
use App\Http\Controllers\HR\OutsourceController;
use App\Http\Controllers\HR\ContractTrackingController;
use App\Http\Controllers\HR\AuditLogController;
use App\Http\Controllers\HR\MasterDataController;
use App\Http\Controllers\HR\SettingsController;
use App\Http\Controllers\HR\UserController;
use App\Http\Controllers\HR\MprRequestorController;
use App\Http\Controllers\HR\ExportController;
use App\Http\Controllers\HR\MprController;
use App\Http\Controllers\HR\AssetController;
use App\Http\Controllers\HR\CertificationController;
use App\Http\Controllers\HR\PermissionController;
use App\Http\Controllers\Auth\AssetAuthController;
use App\Http\Controllers\Auth\CertificateAuthController;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('public.seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('public.seo.sitemap');

if (!app()->environment('local')) {

    Route::domain(config('hris.domains.hris'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [LoginController::class, 'portal'])->name('hris.domain.root');
        Route::get('/portal', function () {
            return redirect()->route('hris.domain.root');
        })->name('auth.portal');
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.post');
        Route::any('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::prefix('hr')->name('hr.')->middleware(['portal.access', 'hr.auth', 'mpr.auth'])->group(function () {
            Route::post('/refresh-data', [\App\Http\Controllers\HR\RefreshController::class, 'refreshData'])->name('refresh-data');
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::prefix('recruitment')->name('recruitment.')->middleware('can:view_recruitment')->group(function () {
                Route::get('/', [RecruitmentController::class, 'index'])->name('index');
                Route::get('/accepted', [RecruitmentController::class, 'accepted'])->name('accepted');
                Route::get('/hold', [RecruitmentController::class, 'holdPage'])->name('hold');
                Route::get('/blacklist', [RecruitmentController::class, 'blacklistPage'])->name('blacklist');
                Route::post('/{id}/status', [RecruitmentController::class, 'updateStatus'])->name('update-status')->middleware('can:update_candidates');
                Route::post('/{id}/hold', [RecruitmentController::class, 'hold'])->name('hold.post')->middleware('can:manage_hold_blacklist');
                Route::post('/{id}/blacklist', [RecruitmentController::class, 'blacklist'])->name('blacklist.post')->middleware('can:manage_hold_blacklist');
                Route::post('/{id}/accept', [RecruitmentController::class, 'accept'])->name('accept')->middleware('can:create_offering');
                Route::post('/{id}/save-notes', [RecruitmentController::class, 'saveNotes'])->name('save-notes')->middleware('can:create_offering');
                Route::post('/{id}/move-status', [RecruitmentController::class, 'moveStatus'])->name('move-status')->middleware('can:update_candidates');
                Route::post('/{id}/save-offering', [RecruitmentController::class, 'saveOffering'])->name('save-offering')->middleware('can:create_offering');
                Route::post('/{id}/save-offering-response', [RecruitmentController::class, 'saveOfferingResponse'])->name('save-offering-response')->middleware('can:create_offering');
                Route::post('/{id}/save-contract', [RecruitmentController::class, 'saveContract'])->name('save-contract')->middleware('can:create_offering');
                Route::get('/{id}/json', [RecruitmentController::class, 'getJson'])->name('json');
            });
            Route::prefix('employees')->name('employees.')->middleware('can:view_employees')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->name('index');
                Route::get('/search', [EmployeeController::class, 'search'])->name('search');
                Route::post('/import/preview', [EmployeeController::class, 'importPreview'])->name('import.preview')->middleware('can:manage_employees');
                Route::get('/import/template', [EmployeeController::class, 'importTemplate'])->name('import.template')->middleware('can:manage_employees');
                Route::post('/import', [EmployeeController::class, 'import'])->name('import')->middleware('can:manage_employees');
                Route::post('/', [EmployeeController::class, 'store'])->name('store')->middleware('can:manage_employees');
                Route::put('/{id}', [EmployeeController::class, 'update'])->name('update')->middleware('can:manage_employees');
                Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate')->middleware('can:manage_employees');
                Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard')->middleware('can:manage_employees');
                Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract')->middleware('can:manage_employees');
                Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json');
            });
            Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->middleware('can:lookup_employee')->name('employees.lookup');

            Route::prefix('assets')->name('assets.')->middleware('can:view_asset')->group(function () {
                Route::get('/', [AssetController::class, 'index'])->name('index');
                Route::get('/missing-code-summary', [AssetController::class, 'missingCodeSummary'])->name('missing-code-summary')->middleware('can:edit_asset');
                Route::get('/preview-next-code/{prefix}', [AssetController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:edit_asset');
                Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('can:edit_asset');
                Route::post('/generate-bulk-codes', [AssetController::class, 'generateBulkCodes'])->name('generate-bulk-codes')->middleware('can:edit_asset');
                Route::put('/{asset}', [AssetController::class, 'update'])->name('update')->middleware('can:edit_asset');
                Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy')->middleware('can:edit_asset');
                Route::post('/{asset}/assign', [AssetController::class, 'assign'])->name('assign')->middleware('can:edit_asset');
                Route::post('/{asset}/return', [AssetController::class, 'returnAsset'])->name('return')->middleware('can:edit_asset');
                Route::post('/{asset}/generate-code', [AssetController::class, 'generateCode'])->name('generate-code')->middleware('can:edit_asset');
                Route::get('/{asset}/json', [AssetController::class, 'getJson'])->name('json');
            });

            Route::prefix('certifications')->name('certifications.')->middleware('can:view_certification')->group(function () {
                Route::get('/', [CertificationController::class, 'index'])->name('index');
                Route::get('/preview-next-code', [CertificationController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:manage_certification');
                Route::post('/', [CertificationController::class, 'store'])->name('store')->middleware('can:manage_certification');
                Route::put('/{certification}', [CertificationController::class, 'update'])->name('update')->middleware('can:manage_certification');
                Route::delete('/{certification}', [CertificationController::class, 'destroy'])->name('destroy')->middleware('can:manage_certification');
                Route::post('/{certification}/generate-code', [CertificationController::class, 'generateCode'])->name('generate-code')->middleware('can:manage_certification');
                Route::get('/{certification}/attachment', [CertificationController::class, 'attachment'])->name('attachment');
                Route::get('/{certification}/json', [CertificationController::class, 'getJson'])->name('json');
            });

            Route::prefix('probation')->name('probation.')->middleware('can:manage_probation')->group(function () {
                Route::get('/', [ProbationController::class, 'index'])->name('index');
                Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate');
                Route::get('/{id}/eval-history', [ProbationController::class, 'evalHistory'])->name('eval-history');
                Route::get('/{id}/preview', [ProbationController::class, 'previewPerformanceReview'])->name('preview');
            });
            Route::prefix('outsource')->name('outsource.')->middleware('can:view_employees')->group(function () {
                Route::get('/', [OutsourceController::class, 'index'])->name('index');
                Route::post('/', [OutsourceController::class, 'store'])->name('store')->middleware('can:manage_employees');
                Route::post('/kontrak-pkwt-tad', [OutsourceController::class, 'generateKontrakPkwtTad'])
                    ->name('kontrak-pkwt-tad')
                    ->middleware('can:manage_employees');
                Route::get('/kontrak-pkwt-tad/download', [OutsourceController::class, 'downloadKontrakPkwtTad'])
                    ->name('kontrak-pkwt-tad.download')
                    ->middleware('can:manage_employees');
            });
            Route::prefix('contracts')->name('contracts.')->middleware('can:view_contracts')->group(function () {
                Route::get('/', [ContractTrackingController::class, 'index'])->name('index');
            });
            Route::prefix('audit-logs')->name('audit-logs.')->middleware('can:view_reports')->group(function () {
                Route::get('/', [AuditLogController::class, 'index'])->name('index');
            });
            Route::prefix('master-data')->name('master-data.')->middleware('can:manage_settings')->group(function () {
                Route::get('/', [MasterDataController::class, 'index'])->name('index');
                Route::post('/', [MasterDataController::class, 'store'])->name('store');
            });
            Route::prefix('settings')->name('settings.')->middleware('can:manage_settings')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::post('/', [SettingsController::class, 'update'])->name('update');
            });
            Route::prefix('users')->name('users.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::put('/{email}', [UserController::class, 'update'])->name('update');
            });
            Route::prefix('permissions')->name('permissions.')->middleware(['can:manage_permissions', 'role:Super Admin'])->group(function () {
                Route::get('/', [PermissionController::class, 'index'])->name('index');
                Route::get('/users', [PermissionController::class, 'users'])->name('users');
                Route::get('/{email}', [PermissionController::class, 'show'])->name('show');
                Route::put('/{email}', [PermissionController::class, 'update'])->name('update');
            });
            Route::prefix('mpr-requestors')->name('mpr-requestors.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
                Route::get('/', [MprRequestorController::class, 'index'])->name('index');
                Route::post('/', [MprRequestorController::class, 'store'])->name('store');
                Route::put('/{email}', [MprRequestorController::class, 'update'])->name('update');
            });
            Route::prefix('export')->name('export.')->middleware('can:view_recruitment')->group(function () {
                Route::get('/candidate-pdf/{id}', [ExportController::class, 'candidatePdf'])->name('candidate-pdf');
            });
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
            Route::prefix('export')->name('export.')->middleware('can:manage_recruitment')->group(function () {
                Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
            });
            Route::prefix('export')->name('export.')->middleware('can:view_employees')->group(function () {
                Route::get('/employees-xlsx', [ExportController::class, 'exportEmployeesXlsx'])->name('employees-xlsx');
            });
            Route::prefix('mpr')->name('mpr.')->middleware('can:view_mpr')->group(function () {
                Route::get('/', [MprController::class, 'index'])->name('index');
                Route::get('/create', function () {
                    return redirect()->route('mpr.auth.request');
                })->name('create')->middleware('can:create_mpr');
                Route::get('/history', [MprController::class, 'history'])->name('history');
                Route::post('/', [MprController::class, 'store'])->name('store')->middleware('can:create_mpr');
                Route::put('/{id}', [MprController::class, 'update'])->name('update')->middleware('can:update_mpr');
                Route::get('/{id}/preview', [MprController::class, 'preview'])->name('preview');
                Route::get('/{id}', [MprController::class, 'show'])->name('show');
                Route::get('/{id}/json', [MprController::class, 'getJson'])->name('json');
                Route::get('/{id}/pdf', [MprController::class, 'exportPdf'])->name('pdf')->middleware('can:export_mpr');
            });
        });
    });

    Route::domain(config('hris.domains.mpr'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [MprAuthController::class, 'portal'])->name('mpr.auth.domain.root');
        Route::get('/mpr', [MprAuthController::class, 'portal'])->name('mpr.auth.portal');
        Route::get('/mpr/login', [MprAuthController::class, 'showLoginForm'])->name('mpr.auth.login');
        Route::post('/mpr/login', [MprAuthController::class, 'login'])->middleware('throttle:mpr-login')->name('mpr.auth.login.post');
        Route::post('/mpr/logout', [MprAuthController::class, 'logout'])->middleware('mpr.auth.dedicated')->name('mpr.auth.logout');
        Route::middleware(['portal.access', 'mpr.auth.dedicated'])->group(function () {
            Route::get('/mpr/request', [MprController::class, 'create'])->name('mpr.auth.request');
            Route::get('/mpr/request/history', [MprController::class, 'history'])->name('mpr.auth.request.history');
            Route::post('/mpr/request', [MprController::class, 'store'])->name('mpr.auth.request.store');
            Route::get('/mpr/request/{id}/pdf', [MprController::class, 'exportPdf'])->name('mpr.auth.pdf');
            Route::get('/mpr/request/{id}/json', [MprController::class, 'getJson'])->name('mpr.auth.json');
        });
    });

    Route::domain(config('hris.domains.recruitment'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [CareerController::class, 'index'])->name('public.career.index');
        Route::post('/career/consent', [CareerController::class, 'consent'])->name('public.career.consent');
        Route::get('/apply', [CareerController::class, 'form'])->name('public.career.form');
        Route::post('/apply/nik-check', [CareerController::class, 'checkNik'])->middleware('throttle:career-apply')->name('public.career.nik-check');
        Route::post('/apply', [CareerController::class, 'store'])->middleware('throttle:career-apply')->name('public.career.store');
        Route::get('/career/submission-success', [CareerController::class, 'submissionSuccess'])->name('public.career.submission-success');
        Route::get('/check-status', [CareerController::class, 'checkStatus'])->name('public.career.check-status');
        Route::get('/self-update/{id}', [CareerController::class, 'selfUpdate'])->name('public.career.self-update');
        Route::post('/self-update/{id}', [CareerController::class, 'storeSelfUpdate'])->name('public.career.self-update.store');
    });

    Route::domain(config('hris.domains.outsource'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [OutsourceApplyController::class, 'index'])->name('public.outsource.index');
        Route::get('/apply', [OutsourceApplyController::class, 'index'])->name('public.outsource.apply');
        Route::post('/apply', [OutsourceApplyController::class, 'store'])->name('public.outsource.store');
        Route::get('/success', [OutsourceApplyController::class, 'success'])->name('public.outsource.success');
    });

    Route::domain(config('hris.domains.assets'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('assets.portal.index')->middleware('can:access_assets_portal');
        Route::get('/login', [AssetAuthController::class, 'showLoginForm'])->name('assets.login');
        Route::post('/login', [AssetAuthController::class, 'login'])->middleware('throttle:login')->name('assets.login.post');
        Route::post('/logout', [AssetAuthController::class, 'logout'])->name('assets.logout');

        Route::name('assets.portal.')->middleware('can:access_assets_portal')->group(function () {
            Route::get('/missing-code-summary', [AssetController::class, 'missingCodeSummary'])->name('missing-code-summary')->middleware('can:assets.view');
            Route::get('/preview-next-code/{prefix}', [AssetController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:assets.generate_code');
            Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('can:assets.create');
            Route::post('/generate-bulk-codes', [AssetController::class, 'generateBulkCodes'])->name('generate-bulk-codes')->middleware('can:assets.generate_code');
            Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup')->middleware('can:lookup_employee');
            Route::put('/{asset}', [AssetController::class, 'update'])->name('update')->middleware('can:assets.update');
            Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy')->middleware('can:assets.delete');
            Route::post('/{asset}/assign', [AssetController::class, 'assign'])->name('assign')->middleware('can:assets.assign');
            Route::post('/{asset}/return', [AssetController::class, 'returnAsset'])->name('return')->middleware('can:assets.return');
            Route::post('/{asset}/generate-code', [AssetController::class, 'generateCode'])->name('generate-code')->middleware('can:assets.generate_code');
            Route::get('/{asset}/json', [AssetController::class, 'getJson'])->name('json')->middleware('can:assets.view');
        });
    });
    Route::domain(config('hris.domains.certificates'))->middleware(['web', 'domain'])->group(function () {
        Route::get('/', [CertificateAuthController::class, 'portal'])->name('certificates.domain.root');
        Route::get('/login', [CertificateAuthController::class, 'showLoginForm'])->name('certificates.login');
        Route::post('/login', [CertificateAuthController::class, 'login'])->middleware('throttle:login')->name('certificates.login.post');
        Route::post('/logout', [CertificateAuthController::class, 'logout'])->name('certificates.logout');

            Route::prefix('certifications')->name('certificates.portal.')->group(function () {
            Route::get('/', [CertificationController::class, 'index'])->name('index')->middleware('can:access_certificates_portal');
            Route::get('/preview-next-code', [CertificationController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:certificates.generate_code');
            Route::post('/', [CertificationController::class, 'store'])->name('store')->middleware('can:certificates.create');
            Route::put('/{certification}', [CertificationController::class, 'update'])->name('update')->middleware('can:certificates.update');
            Route::delete('/{certification}', [CertificationController::class, 'destroy'])->name('destroy')->middleware('can:certificates.delete');
            Route::post('/{certification}/generate-code', [CertificationController::class, 'generateCode'])->name('generate-code')->middleware('can:certificates.generate_code');
            Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup')->middleware('can:lookup_employee');
                Route::get('/{certification}/attachment', [CertificationController::class, 'attachment'])->name('attachment')->middleware(['can:access_certificates_portal', 'can:certificates.view']);
            Route::get('/{certification}/json', [CertificationController::class, 'getJson'])->name('json')->middleware('can:certificates.view');
        });
    });
} // end !local

if (app()->environment('local')) {
    Route::middleware(['web', 'domain'])->group(function () {
        Route::get('/', [LoginController::class, 'portal'])->name('hris.domain.root');
        Route::get('/portal', [LoginController::class, 'portal'])->name('auth.portal');
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.post');
        Route::any('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::prefix('hr')->name('hr.')->middleware(['portal.access', 'hr.auth', 'mpr.auth'])->group(function () {
            Route::post('/refresh-data', [\App\Http\Controllers\HR\RefreshController::class, 'refreshData'])->name('refresh-data');
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::prefix('recruitment')->name('recruitment.')->middleware('can:view_recruitment')->group(function () {
                Route::get('/', [RecruitmentController::class, 'index'])->name('index');
                Route::get('/accepted', [RecruitmentController::class, 'accepted'])->name('accepted');
                Route::get('/hold', [RecruitmentController::class, 'holdPage'])->name('hold');
                Route::get('/blacklist', [RecruitmentController::class, 'blacklistPage'])->name('blacklist');
                Route::post('/{id}/status', [RecruitmentController::class, 'updateStatus'])->name('update-status')->middleware('can:update_candidates');
                Route::post('/{id}/hold', [RecruitmentController::class, 'hold'])->name('hold.post')->middleware('can:manage_hold_blacklist');
                Route::post('/{id}/blacklist', [RecruitmentController::class, 'blacklist'])->name('blacklist.post')->middleware('can:manage_hold_blacklist');
                Route::post('/{id}/accept', [RecruitmentController::class, 'accept'])->name('accept')->middleware('can:create_offering');
                Route::post('/{id}/save-notes', [RecruitmentController::class, 'saveNotes'])->name('save-notes')->middleware('can:create_offering');
                Route::post('/{id}/move-status', [RecruitmentController::class, 'moveStatus'])->name('move-status')->middleware('can:update_candidates');
                Route::post('/{id}/save-offering', [RecruitmentController::class, 'saveOffering'])->name('save-offering')->middleware('can:create_offering');
                Route::post('/{id}/save-offering-response', [RecruitmentController::class, 'saveOfferingResponse'])->name('save-offering-response')->middleware('can:create_offering');
                Route::post('/{id}/save-contract', [RecruitmentController::class, 'saveContract'])->name('save-contract')->middleware('can:create_offering');
                Route::get('/{id}/json', [RecruitmentController::class, 'getJson'])->name('json');
            });

            Route::prefix('employees')->name('employees.')->middleware('can:view_employees')->group(function () {
                Route::get('/', [EmployeeController::class, 'index'])->name('index');
                Route::get('/search', [EmployeeController::class, 'search'])->name('search');
                Route::post('/import/preview', [EmployeeController::class, 'importPreview'])->name('import.preview')->middleware('can:manage_employees');
                Route::get('/import/template', [EmployeeController::class, 'importTemplate'])->name('import.template')->middleware('can:manage_employees');
                Route::post('/import', [EmployeeController::class, 'import'])->name('import')->middleware('can:manage_employees');
                Route::post('/', [EmployeeController::class, 'store'])->name('store')->middleware('can:manage_employees');
                Route::put('/{id}', [EmployeeController::class, 'update'])->name('update')->middleware('can:manage_employees');
                Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate')->middleware('can:manage_employees');
                Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard')->middleware('can:manage_employees');
                Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract')->middleware('can:manage_employees');
                Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json');
            });
            Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->middleware('can:lookup_employee')->name('employees.lookup');

            Route::prefix('assets')->name('assets.')->middleware('can:assets.view')->group(function () {
                Route::get('/', [AssetController::class, 'index'])->name('index');
                Route::get('/missing-code-summary', [AssetController::class, 'missingCodeSummary'])->name('missing-code-summary')->middleware('can:assets.view');
                Route::get('/preview-next-code/{prefix}', [AssetController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:assets.generate_code');
                Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('can:assets.create');
                Route::post('/generate-bulk-codes', [AssetController::class, 'generateBulkCodes'])->name('generate-bulk-codes')->middleware('can:assets.generate_code');
                Route::put('/{asset}', [AssetController::class, 'update'])->name('update')->middleware('can:assets.update');
                Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy')->middleware('can:assets.delete');
                Route::post('/{asset}/assign', [AssetController::class, 'assign'])->name('assign')->middleware('can:assets.assign');
                Route::post('/{asset}/return', [AssetController::class, 'returnAsset'])->name('return')->middleware('can:assets.return');
                Route::post('/{asset}/generate-code', [AssetController::class, 'generateCode'])->name('generate-code')->middleware('can:assets.generate_code');
                Route::get('/{asset}/json', [AssetController::class, 'getJson'])->name('json');
            });

            Route::prefix('certifications')->name('certifications.')->middleware('can:certificates.view')->group(function () {
                Route::get('/', [CertificationController::class, 'index'])->name('index');
                Route::get('/preview-next-code', [CertificationController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:certificates.generate_code');
                Route::post('/', [CertificationController::class, 'store'])->name('store')->middleware('can:certificates.create');
                Route::put('/{certification}', [CertificationController::class, 'update'])->name('update')->middleware('can:certificates.update');
                Route::delete('/{certification}', [CertificationController::class, 'destroy'])->name('destroy')->middleware('can:certificates.delete');
                Route::post('/{certification}/generate-code', [CertificationController::class, 'generateCode'])->name('generate-code')->middleware('can:certificates.generate_code');
                Route::get('/{certification}/attachment', [CertificationController::class, 'attachment'])->name('attachment')->middleware('can:certificates.view');
                Route::get('/{certification}/json', [CertificationController::class, 'getJson'])->name('json');
            });

            Route::prefix('probation')->name('probation.')->middleware('can:manage_probation')->group(function () {
                Route::get('/', [ProbationController::class, 'index'])->name('index');
                Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate');
                Route::get('/{id}/eval-history', [ProbationController::class, 'evalHistory'])->name('eval-history');
                Route::get('/{id}/preview', [ProbationController::class, 'previewPerformanceReview'])->name('preview');
            });

            Route::prefix('outsource')->name('outsource.')->middleware('can:view_employees')->group(function () {
                Route::get('/', [OutsourceController::class, 'index'])->name('index');
                Route::post('/', [OutsourceController::class, 'store'])->name('store')->middleware('can:manage_employees');
                Route::post('/kontrak-pkwt-tad', [OutsourceController::class, 'generateKontrakPkwtTad'])
                    ->name('kontrak-pkwt-tad')
                    ->middleware('can:manage_employees');
                Route::get('/kontrak-pkwt-tad/download', [OutsourceController::class, 'downloadKontrakPkwtTad'])
                    ->name('kontrak-pkwt-tad.download')
                    ->middleware('can:manage_employees');
            });

            Route::prefix('contracts')->name('contracts.')->middleware('can:view_contracts')->group(function () {
                Route::get('/', [ContractTrackingController::class, 'index'])->name('index');
            });

            Route::prefix('audit-logs')->name('audit-logs.')->middleware('can:view_reports')->group(function () {
                Route::get('/', [AuditLogController::class, 'index'])->name('index');
            });

            Route::prefix('master-data')->name('master-data.')->middleware('can:manage_settings')->group(function () {
                Route::get('/', [MasterDataController::class, 'index'])->name('index');
                Route::post('/', [MasterDataController::class, 'store'])->name('store');
            });

            Route::prefix('settings')->name('settings.')->middleware('can:manage_settings')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::post('/', [SettingsController::class, 'update'])->name('update');
            });

            Route::prefix('users')->name('users.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::put('/{email}', [UserController::class, 'update'])->name('update');
            });
            Route::prefix('permissions')->name('permissions.')->middleware(['can:manage_permissions', 'role:Super Admin'])->group(function () {
                Route::get('/', [PermissionController::class, 'index'])->name('index');
                Route::get('/users', [PermissionController::class, 'users'])->name('users');
                Route::get('/{email}', [PermissionController::class, 'show'])->name('show');
                Route::put('/{email}', [PermissionController::class, 'update'])->name('update');
            });

            Route::prefix('mpr-requestors')->name('mpr-requestors.')->middleware(['can:manage_settings', 'role:Super Admin'])->group(function () {
                Route::get('/', [MprRequestorController::class, 'index'])->name('index');
                Route::post('/', [MprRequestorController::class, 'store'])->name('store');
                Route::put('/{email}', [MprRequestorController::class, 'update'])->name('update');
            });

            Route::prefix('export')->name('export.')->middleware('can:view_recruitment')->group(function () {
                Route::get('/candidate-pdf/{id}', [ExportController::class, 'candidatePdf'])->name('candidate-pdf');
            });

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

            Route::prefix('export')->name('export.')->middleware('can:manage_recruitment')->group(function () {
            Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
            });
            Route::prefix('export')->name('export.')->middleware('can:view_employees')->group(function () {
                Route::get('/employees-xlsx', [ExportController::class, 'exportEmployeesXlsx'])->name('employees-xlsx');
            });

            Route::prefix('mpr')->name('mpr.')->middleware('can:view_mpr')->group(function () {
                Route::get('/', [MprController::class, 'index'])->name('index');
                Route::get('/create', function () {
                    return redirect()->route('mpr.auth.request');
                })->name('create')->middleware('can:create_mpr');
                Route::get('/history', [MprController::class, 'history'])->name('history');
                Route::post('/', [MprController::class, 'store'])->name('store')->middleware('can:create_mpr');
                Route::put('/{id}', [MprController::class, 'update'])->name('update')->middleware('can:update_mpr');
                Route::get('/{id}/preview', [MprController::class, 'preview'])->name('preview');
                Route::get('/{id}', [MprController::class, 'show'])->name('show');
                Route::get('/{id}/json', [MprController::class, 'getJson'])->name('json');
                Route::get('/{id}/pdf', [MprController::class, 'exportPdf'])->name('pdf')->middleware('can:export_mpr');
            });
        });

        Route::get('/mpr', [MprAuthController::class, 'portal'])->name('mpr.auth.portal');
        Route::prefix('mpr')->name('mpr.auth.')->group(function () {
            Route::get('/', [MprAuthController::class, 'portal'])->name('domain.root');
            Route::get('/login', [MprAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [MprAuthController::class, 'login'])->middleware('throttle:mpr-login')->name('login.post');
            Route::post('/logout', [MprAuthController::class, 'logout'])->middleware('mpr.auth.dedicated')->name('logout');

            Route::middleware(['portal.access', 'mpr.auth.dedicated'])->group(function () {
                Route::get('/request', [MprController::class, 'create'])->name('request');
                Route::get('/request/history', [MprController::class, 'history'])->name('request.history');
                Route::post('/request', [MprController::class, 'store'])->name('request.store');
                Route::get('/request/{id}/pdf', [MprController::class, 'exportPdf'])->name('pdf');
                Route::get('/request/{id}/json', [MprController::class, 'getJson'])->name('json');
            });
        });

        Route::get('/career', [CareerController::class, 'index'])->name('public.career.index');
        Route::post('/career/consent', [CareerController::class, 'consent'])->name('public.career.consent');
        Route::get('/career/apply', [CareerController::class, 'form'])->name('public.career.form');
        Route::post('/career/apply/nik-check', [CareerController::class, 'checkNik'])->middleware('throttle:career-apply')->name('public.career.nik-check');
        Route::post('/career/apply', [CareerController::class, 'store'])->middleware('throttle:career-apply')->name('public.career.store');
        Route::get('/career/submission-success', [CareerController::class, 'submissionSuccess'])->name('public.career.submission-success');
        Route::get('/career/check-status', [CareerController::class, 'checkStatus'])->name('public.career.check-status');
        Route::get('/career/self-update/{id}', [CareerController::class, 'selfUpdate'])->name('public.career.self-update');
        Route::post('/career/self-update/{id}', [CareerController::class, 'storeSelfUpdate'])->name('public.career.self-update.store');

        Route::get('/outsource', [OutsourceApplyController::class, 'index'])->name('public.outsource.index');
        Route::get('/outsource/apply', [OutsourceApplyController::class, 'index'])->name('public.outsource.apply');
        Route::post('/outsource/apply', [OutsourceApplyController::class, 'store'])->name('public.outsource.store');
        Route::get('/outsource/success', [OutsourceApplyController::class, 'success'])->name('public.outsource.success');

        Route::middleware(['domain'])->group(function () {
            Route::get('/assets/login', [AssetAuthController::class, 'showLoginForm'])->name('assets.login');
            Route::post('/assets/login', [AssetAuthController::class, 'login'])->middleware('throttle:login')->name('assets.login.post');
            Route::post('/assets/logout', [AssetAuthController::class, 'logout'])->name('assets.logout');
            Route::prefix('assets')->name('assets.portal.')->group(function () {
                Route::get('/', [AssetController::class, 'index'])->name('index');
                Route::get('/missing-code-summary', [AssetController::class, 'missingCodeSummary'])->name('missing-code-summary')->middleware('can:assets.view');
                Route::get('/preview-next-code/{prefix}', [AssetController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:assets.generate_code');
                Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('can:assets.create');
                Route::post('/generate-bulk-codes', [AssetController::class, 'generateBulkCodes'])->name('generate-bulk-codes')->middleware('can:assets.generate_code');
                Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup')->middleware('can:lookup_employee');
                Route::put('/{asset}', [AssetController::class, 'update'])->name('update')->middleware('can:assets.update');
                Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy')->middleware('can:assets.delete');
                Route::post('/{asset}/assign', [AssetController::class, 'assign'])->name('assign')->middleware('can:assets.assign');
                Route::post('/{asset}/return', [AssetController::class, 'returnAsset'])->name('return')->middleware('can:assets.return');
                Route::post('/{asset}/generate-code', [AssetController::class, 'generateCode'])->name('generate-code')->middleware('can:assets.generate_code');
                Route::get('/{asset}/json', [AssetController::class, 'getJson'])->name('json');
            });

            Route::get('/certifications/login', [CertificateAuthController::class, 'showLoginForm'])->name('certificates.login');
            Route::post('/certifications/login', [CertificateAuthController::class, 'login'])->middleware('throttle:login')->name('certificates.login.post');
            Route::post('/certifications/logout', [CertificateAuthController::class, 'logout'])->name('certificates.logout');
            Route::prefix('certifications')->name('certificates.portal.')->middleware('can:access_certificates_portal')->group(function () {
                Route::get('/', [CertificationController::class, 'index'])->name('index');
                Route::get('/preview-next-code', [CertificationController::class, 'previewNextCode'])->name('preview-next-code')->middleware('can:certificates.generate_code');
                Route::post('/', [CertificationController::class, 'store'])->name('store')->middleware('can:certificates.create');
                Route::put('/{certification}', [CertificationController::class, 'update'])->name('update')->middleware('can:certificates.update');
                Route::delete('/{certification}', [CertificationController::class, 'destroy'])->name('destroy')->middleware('can:certificates.delete');
                Route::post('/{certification}/generate-code', [CertificationController::class, 'generateCode'])->name('generate-code')->middleware('can:certificates.generate_code');
                Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup')->middleware('can:lookup_employee');
                Route::get('/{certification}/attachment', [CertificationController::class, 'attachment'])->name('attachment')->middleware('can:certificates.view');
                Route::get('/{certification}/json', [CertificationController::class, 'getJson'])->name('json');
            });
        });

        // Asset & Certificate CRUD are also available through the HR-internal compatibility routes
        // above. Dedicated domain routes remain canonical for their respective portals.
    });
}
