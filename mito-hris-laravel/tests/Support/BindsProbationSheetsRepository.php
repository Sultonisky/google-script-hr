<?php

namespace Tests\Support;

use App\Repositories\Contracts\ProbationRepositoryInterface;
use App\Repositories\Sheets\ProbationSheetsRepository;
use App\Services\Google\GoogleSheetsService;

/**
 * Wire ProbationService to a mocked GoogleSheetsService via the Sheets
 * repository adapter (production path when HRIS_DATA_DRIVER=sheets).
 */
trait BindsProbationSheetsRepository
{
    protected function bindProbationSheetsFromMock(GoogleSheetsService $sheets): void
    {
        $this->app->instance(GoogleSheetsService::class, $sheets);
        $this->app->instance(
            ProbationRepositoryInterface::class,
            new ProbationSheetsRepository($sheets)
        );
    }
}
