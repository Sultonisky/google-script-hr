<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\V1\OutsourcePayrollController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Internal endpoints for NIK Autofill, Cascading Provinces, Cities, and Districts.
*/

Route::prefix('v1')->group(function () {
    Route::get('/provinces', [RegionController::class, 'getProvinces']);
    Route::get('/cities/{provinceCode}', [RegionController::class, 'getCities']);
    Route::get('/districts/{cityCode}', [RegionController::class, 'getDistricts']);
    Route::post('/nik/parse', [RegionController::class, 'parseNik']);

    Route::prefix('outsource')
        ->middleware(['outsource.payroll.integration', 'throttle:60,1'])
        ->group(function () {
            Route::get('/{outsourceId}/payslips', [OutsourcePayrollController::class, 'payslips']);
            Route::get('/{outsourceId}/incentives', [OutsourcePayrollController::class, 'incentives']);
        });
});
