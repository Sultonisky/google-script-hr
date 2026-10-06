<?php

use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\V1\EmployeeIntegrationController;
use Illuminate\Support\Facades\Route;

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

    Route::prefix('employees')
        ->middleware(['employee.integration', 'throttle:60,1'])
        ->group(function () {
            Route::get('/', [EmployeeIntegrationController::class, 'index']);
            Route::get('/by-nik/{nik}', [EmployeeIntegrationController::class, 'showByNik']);
        });
});
