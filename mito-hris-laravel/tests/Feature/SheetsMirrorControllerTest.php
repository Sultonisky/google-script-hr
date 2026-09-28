<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Services\Etl\DatabaseToSheetsEtlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class SheetsMirrorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'hris.data_driver' => 'pgsql',
            'google.enabled' => true,
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function loginAs(string $role, array $permissions = ['*']): void
    {
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'name' => 'Admin',
            'role' => $role,
            'permissions' => $permissions,
            'auth_domain' => 'users',
        ]);
    }

    private function mockEtl(): Mockery\MockInterface
    {
        $etl = Mockery::mock(DatabaseToSheetsEtlService::class);
        $this->app->instance(DatabaseToSheetsEtlService::class, $etl);

        return $etl;
    }

    public function test_super_admin_can_sync_database_to_sheets(): void
    {
        $this->mockEtl()->shouldReceive('run')->once()->with(['all'])->andReturn([
            'employees' => ['read' => 2, 'written' => 2, 'skipped' => 0, 'errors' => []],
            'mpr' => ['read' => 1, 'written' => 1, 'skipped' => 0, 'errors' => []],
        ]);
        $this->loginAs('Super Admin');

        $response = $this->postJson('/hr/sync-to-sheets');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.written', 3)
            ->assertJsonPath('data.domains.employees.written', 2);
        $this->assertSame(1, AuditLog::query()->where('action', 'Sync')->count());
    }

    public function test_partial_failure_reports_failed_domains(): void
    {
        $this->mockEtl()->shouldReceive('run')->once()->andReturn([
            'employees' => ['read' => 2, 'written' => 2, 'skipped' => 0, 'errors' => []],
            'audit' => ['read' => 5, 'written' => 0, 'skipped' => 0, 'errors' => ['audit: quota exceeded']],
        ]);
        $this->loginAs('Super Admin');

        $response = $this->postJson('/hr/sync-to-sheets');

        $response->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.domains.audit.failed', true);
        $this->assertStringContainsString('audit', (string) $response->json('message'));
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $this->mockEtl()->shouldReceive('run')->never();
        $this->loginAs('HR', ['manage_settings']);

        $this->postJson('/hr/sync-to-sheets')->assertForbidden();
    }

    public function test_refused_when_sheets_is_still_source_of_truth(): void
    {
        config(['hris.data_driver' => 'sheets']);
        $this->mockEtl()->shouldReceive('run')->never();
        $this->loginAs('Super Admin');

        $this->postJson('/hr/sync-to-sheets')
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_refused_while_another_sync_is_running(): void
    {
        $this->mockEtl()->shouldReceive('run')->never();
        $this->loginAs('Super Admin');

        $lock = Cache::lock('hris:mirror-db-to-sheets', 60);
        $this->assertTrue($lock->get());

        try {
            $this->postJson('/hr/sync-to-sheets')
                ->assertStatus(409)
                ->assertJsonPath('success', false);
        } finally {
            $lock->release();
        }
    }
}
