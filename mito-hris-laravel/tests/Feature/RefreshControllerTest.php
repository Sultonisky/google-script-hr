<?php

namespace Tests\Feature;

use App\Http\Controllers\HR\RefreshController;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Mockery;
use Tests\TestCase;

class RefreshControllerTest extends TestCase
{
    public function test_refresh_invalidates_cache_without_clearing_sheet_data(): void
    {
        config(['hris.data_driver' => 'sheets']);

        $service = Mockery::mock(GoogleSheetsService::class);
        $service->shouldReceive('healthCheck')
            ->once()
            ->with(['Employee', 'data_kandidat', 'MPR'])
            ->andReturn([
                'success' => true,
                'status' => 'healthy',
                'message' => 'Koneksi Google Sheets berhasil.',
                'sheets' => ['Employee', 'data_kandidat', 'MPR'],
            ]);

        $service->shouldReceive('clearCache')
            ->times(3)
            ->withArgs(function ($sheetName) {
                return in_array($sheetName, ['Employee', 'data_kandidat', 'MPR'], true);
            });

        $service->shouldReceive('getRange')
            ->times(3)
            ->withArgs(function ($sheetName, $range, $useCache) {
                return in_array($sheetName, ['Employee', 'data_kandidat', 'MPR'], true)
                    && $range === 'A:ZZ'
                    && $useCache === false;
            })
            ->andReturn([['Header A', 'Header B']]);

        $service->shouldNotReceive('clearAllSheets');

        $controller = new RefreshController(
            $service,
            Mockery::mock(EmployeeRepositoryInterface::class),
            Mockery::mock(CandidateRepositoryInterface::class),
            Mockery::mock(MprRepositoryInterface::class),
        );
        $response = $controller->refreshData();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('Data berhasil diperbarui.', $response->getData(true)['message']);
        $this->assertSame(['Employee', 'data_kandidat', 'MPR'], $response->getData(true)['data']['sheets']);
    }
}
