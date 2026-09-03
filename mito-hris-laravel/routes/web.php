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
use App\Http\Controllers\HR\AuditLogController;
use App\Http\Controllers\HR\MasterDataController;
use App\Http\Controllers\HR\SettingsController;
use App\Http\Controllers\HR\UserController;
use App\Http\Controllers\HR\MprRequestorController;
use App\Http\Controllers\HR\ExportController;
use App\Http\Controllers\HR\MprController;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('public.seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('public.seo.sitemap');

Route::domain(config('hris.domains.hris'))->middleware('web')->group(function () {
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
            Route::put('/{id}', [EmployeeController::class, 'update'])->name('update')->middleware('can:manage_employees');
            Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate')->middleware('can:manage_employees');
            Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard')->middleware('can:manage_employees');
            Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract')->middleware('can:manage_employees');
            Route::post('/{id}/promote-probation', [EmployeeController::class, 'promoteToProbation'])->name('promote-probation')->middleware('can:manage_employees');
            Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json');
        });
        Route::prefix('probation')->name('probation.')->middleware('can:manage_probation')->group(function () {
            Route::get('/', [ProbationController::class, 'index'])->name('index');
            Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate');
            Route::get('/{id}/eval-history', [ProbationController::class, 'evalHistory'])->name('eval-history');
            Route::get('/{id}/preview', [ProbationController::class, 'previewPerformanceReview'])->name('preview');
        });
        Route::prefix('outsource')->name('outsource.')->middleware('can:view_employees')->group(function () {
            Route::get('/', [OutsourceController::class, 'index'])->name('index');
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
        Route::prefix('export')->name('export.')->middleware('role:Super Admin,Admin,User')->group(function () {
            Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
        });
        Route::prefix('export')->name('export.')->middleware('can:view_employees')->group(function () {
            Route::get('/employees-csv', [ExportController::class, 'exportEmployeesCsv'])->name('employees-csv');
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

Route::domain(config('hris.domains.mpr'))->middleware('web')->group(function () {
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

Route::domain(config('hris.domains.recruitment'))->middleware('web')->group(function () {
    Route::get('/', [CareerController::class, 'index'])->name('public.career.index');
    Route::post('/career/consent', [CareerController::class, 'consent'])->name('public.career.consent');
    Route::get('/apply', [CareerController::class, 'form'])->name('public.career.form');
    Route::post('/apply', [CareerController::class, 'store'])->name('public.career.store');
    Route::get('/career/success', [CareerController::class, 'success'])->name('public.career.success');
    Route::get('/check-status', [CareerController::class, 'checkStatus'])->name('public.career.check-status');
    Route::get('/self-update/{id}', [CareerController::class, 'selfUpdate'])->name('public.career.self-update');
    Route::post('/self-update/{id}', [CareerController::class, 'storeSelfUpdate'])->name('public.career.self-update.store');
});

Route::domain(config('hris.domains.outsource'))->middleware('web')->group(function () {
    Route::get('/', [OutsourceApplyController::class, 'index'])->name('public.outsource.index');
    Route::get('/apply', [OutsourceApplyController::class, 'index'])->name('public.outsource.apply');
    Route::post('/apply', [OutsourceApplyController::class, 'store'])->name('public.outsource.store');
});

if (app()->environment('local')) {
    Route::middleware('web')->group(function () {
        Route::get('/', [LoginController::class, 'portal'])->name('local.hris.domain.root');
        Route::get('/portal', [LoginController::class, 'portal'])->name('local.auth.portal');
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('local.login');
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('local.login.post');
        Route::post('/logout', [LoginController::class, 'logout'])->name('local.logout');

        Route::prefix('hr')->name('local.hr.')->middleware(['portal.access', 'hr.auth', 'mpr.auth'])->group(function () {
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
                Route::put('/{id}', [EmployeeController::class, 'update'])->name('update')->middleware('can:manage_employees');
                Route::post('/{id}/rotate', [EmployeeController::class, 'rotate'])->name('rotate')->middleware('can:manage_employees');
                Route::post('/{id}/offboard', [EmployeeController::class, 'offboard'])->name('offboard')->middleware('can:manage_employees');
                Route::post('/{id}/off-contract', [EmployeeController::class, 'offContract'])->name('off-contract')->middleware('can:manage_employees');
                Route::post('/{id}/promote-probation', [EmployeeController::class, 'promoteToProbation'])->name('promote-probation')->middleware('can:manage_employees');
                Route::get('/{id}/json', [EmployeeController::class, 'getJson'])->name('json');
            });

            Route::prefix('probation')->name('probation.')->middleware('can:manage_probation')->group(function () {
                Route::get('/', [ProbationController::class, 'index'])->name('index');
                Route::post('/{id}/evaluate', [ProbationController::class, 'evaluate'])->name('evaluate');
                Route::get('/{id}/eval-history', [ProbationController::class, 'evalHistory'])->name('eval-history');
                Route::get('/{id}/preview', [ProbationController::class, 'previewPerformanceReview'])->name('preview');
            });

            Route::prefix('outsource')->name('outsource.')->middleware('can:view_employees')->group(function () {
                Route::get('/', [OutsourceController::class, 'index'])->name('index');
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

            Route::prefix('export')->name('export.')->middleware('role:Super Admin,Admin,User')->group(function () {
                Route::get('/candidates-csv', [ExportController::class, 'exportCandidatesCsv'])->name('candidates-csv');
            });
            Route::prefix('export')->name('export.')->middleware('can:view_employees')->group(function () {
                Route::get('/employees-csv', [ExportController::class, 'exportEmployeesCsv'])->name('employees-csv');
            });

            Route::prefix('mpr')->name('mpr.')->middleware('can:view_mpr')->group(function () {
                Route::get('/', [MprController::class, 'index'])->name('index');
                Route::get('/create', function () {
                    return redirect()->route('local.mpr.request');
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

        Route::get('/mpr', [MprAuthController::class, 'portal'])->name('local.mpr.portal');
        Route::prefix('mpr')->name('local.mpr.')->group(function () {
            Route::get('/', [MprAuthController::class, 'portal'])->name('domain.root');
            Route::get('/login', [MprAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [MprAuthController::class, 'login'])->middleware('throttle:mpr-login')->name('login.post');
            Route::post('/logout', [MprAuthController::class, 'logout'])->middleware('mpr.auth.dedicated')->name('logout');

            Route::middleware(['portal.access', 'mpr.auth.dedicated'])->group(function () {
                Route::get('/request', [MprController::class, 'create'])->name('request');
                Route::get('/request/history', [MprController::class, 'history'])->name('request.history');
                Route::post('/request', [MprController::class, 'store'])->name('request.store');
                Route::get('/request/{id}/pdf', [MprController::class, 'exportPdf'])->name('pdf');
            });
        });

        Route::get('/career', [CareerController::class, 'index'])->name('local.public.career.index');
        Route::post('/career/consent', [CareerController::class, 'consent'])->name('local.public.career.consent');
        Route::get('/career/apply', [CareerController::class, 'form'])->name('local.public.career.form');
        Route::post('/career/apply', [CareerController::class, 'store'])->name('local.public.career.store');
        Route::get('/career/success', [CareerController::class, 'success'])->name('local.public.career.success');
        Route::get('/career/check-status', [CareerController::class, 'checkStatus'])->name('local.public.career.check-status');
        Route::get('/career/self-update/{id}', [CareerController::class, 'selfUpdate'])->name('local.public.career.self-update');
        Route::post('/career/self-update/{id}', [CareerController::class, 'storeSelfUpdate'])->name('local.public.career.self-update.store');

        Route::get('/outsource', [OutsourceApplyController::class, 'index'])->name('local.public.outsource.index');
        Route::get('/outsource/apply', [OutsourceApplyController::class, 'index'])->name('local.public.outsource.apply');
        Route::post('/outsource/apply', [OutsourceApplyController::class, 'store'])->name('local.public.outsource.store');
    });
}
