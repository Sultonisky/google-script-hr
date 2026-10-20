<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OutsourceDirectorySyncController;
use App\Http\Controllers\Api\V1\OutsourcePayrollController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Region lookup used to be public here. The live application form embeds
| that data in the page, so those endpoints are no longer registered.
*/

Route::prefix('v1')->group(function () {
    Route::prefix('outsource')
        ->middleware(['outsource.payroll.integration', 'throttle:60,1'])
        ->group(function () {
            Route::get('/{outsourceId}/payslips', [OutsourcePayrollController::class, 'payslips']);
            Route::get('/{outsourceId}/incentives', [OutsourcePayrollController::class, 'incentives']);
        });

    Route::post('/outsource/persons/sync', [OutsourceDirectorySyncController::class, 'sync'])
        ->middleware(['outsource.sync.integration', 'throttle:60,1']);
});
