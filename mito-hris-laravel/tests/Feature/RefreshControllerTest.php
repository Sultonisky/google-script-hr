<?php

namespace Tests\Feature;

use App\Http\Controllers\HR\RefreshController;
use App\Services\Google\GoogleSheetsService;
use Mockery;
use Tests\TestCase;

class RefreshControllerTest extends TestCase
{
    public function test_refresh_invalidates_cache_without_clearing_sheet_data(): void
    {
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

        $controller = new RefreshController($service);
        $response = $controller->refreshData();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('Data berhasil diperbarui.', $response->getData(true)['message']);
        $this->assertSame(['Employee', 'data_kandidat', 'MPR'], $response->getData(true)['data']['sheets']);
    }
}
