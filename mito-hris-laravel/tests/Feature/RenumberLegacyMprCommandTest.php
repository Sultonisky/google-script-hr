<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MprRequest;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Database\AuditLogDatabaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenumberLegacyMprCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogDatabaseRepository::class);
    }

    public function test_preview_lists_legacy_numbers_without_writing(): void
    {
        $this->seedNumbers();

        $this->artisan('mito:renumber-legacy-mpr')
            ->expectsOutputToContain('MPR-20261001-1001')
            ->expectsOutputToContain('Preview saja')
            ->assertSuccessful();

        $this->assertDatabaseHas('mpr_requests', ['mpr_number' => 'MPR-20261001-1001']);
        $this->assertDatabaseHas('mpr_requests', ['mpr_number' => 'MPR-20261020-1002']);
        $this->assertDatabaseMissing('mpr_requests', ['mpr_number' => 'MPR-001']);
    }

    public function test_execute_renumbers_legacy_numbers_in_request_date_order_and_moves_audit_ids(): void
    {
        $this->seedNumbers();
        AuditLog::query()->create([
            'audit_id' => 'AUD-1',
            'entity_type' => 'MPR',
            'entity_id' => 'MPR-20261020-1002',
            'action' => 'created',
            'field' => 'position',
            'new_value' => 'Staff',
            'user' => 'HR',
            'source' => 'Dashboard',
            'logged_at' => now(),
        ]);

        $this->artisan('mito:renumber-legacy-mpr', ['--execute' => true])
            ->expectsOutputToContain('2 nomor MPR format lama diubah.')
            ->assertSuccessful();

        $this->assertDatabaseHas('mpr_requests', [
            'mpr_number' => 'MPR-010',
            'position' => 'Paling Lama',
        ]);
        $this->assertDatabaseHas('mpr_requests', [
            'mpr_number' => 'MPR-011',
            'position' => 'Lebih Baru',
        ]);
        $this->assertDatabaseHas('mpr_requests', [
            'mpr_number' => 'MPR-009',
            'position' => 'Sudah Pendek',
        ]);
        $this->assertDatabaseHas('mpr_requests', [
            'mpr_number' => 'DRAFT-X',
            'position' => 'Bukan Format Lama',
        ]);
        $this->assertSame('MPR-011', AuditLog::query()->where('action', 'created')->value('entity_id'));
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'MPR',
            'entity_id' => 'MPR-011',
            'action' => 'renumbered',
            'old_value' => 'MPR-20261020-1002',
            'new_value' => 'MPR-011',
            'source' => 'Artisan',
        ]);
    }

    private function seedNumbers(): void
    {
        MprRequest::query()->create([
            'mpr_number' => 'MPR-009',
            'request_date' => '2026-10-15',
            'position' => 'Sudah Pendek',
        ]);
        MprRequest::query()->create([
            'mpr_number' => 'MPR-20261020-1002',
            'request_date' => '2026-10-20',
            'position' => 'Lebih Baru',
        ]);
        MprRequest::query()->create([
            'mpr_number' => 'MPR-20261001-1001',
            'request_date' => '2026-10-01',
            'position' => 'Paling Lama',
        ]);
        MprRequest::query()->create([
            'mpr_number' => 'DRAFT-X',
            'request_date' => '2026-09-01',
            'position' => 'Bukan Format Lama',
        ]);
    }
}
