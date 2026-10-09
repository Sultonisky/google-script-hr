<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OutsourceEmployee;
use App\Services\OutsourceIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReassignOutsourceIdCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'hris.data_driver' => 'pgsql',
            'hris.outsource.reserved_ids' => ['DM20261000'],
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    public function test_generator_skips_reserved_ids_when_continuing_the_sequence(): void
    {
        $generator = app(OutsourceIdGenerator::class);
        $now = Carbon::parse('2026-10-09', 'Asia/Jakarta');

        $this->assertSame('DM20260135', $generator->generate(['DM20260134', 'DM20261000'], $now));
        $this->assertSame('DM20261001', $generator->generate(['DM20260999', 'DM20261000'], $now));
        $this->assertSame('DM20261001', $generator->generate(['DM20260999'], $now));
    }

    public function test_preview_reports_rows_without_writing(): void
    {
        $this->seedSelviana();

        $this->artisan('mito:outsource-reassign-id', ['from' => 'DM20260129', 'to' => 'DM20261000'])
            ->expectsOutputToContain('Preview saja')
            ->assertSuccessful();

        $this->assertDatabaseHas('outsource_employees', ['outsource_id' => 'DM20260129']);
        $this->assertDatabaseHas('employee_documents', ['employee_id' => 'DM20260129']);
    }

    public function test_execute_moves_person_and_all_related_rows(): void
    {
        $this->seedSelviana();

        $this->artisan('mito:outsource-reassign-id', [
            'from' => 'dm20260129',
            'to' => 'DM20261000',
            '--execute' => true,
            '--reason' => 'Bentrok ID dengan Attendance',
        ])->assertSuccessful();

        $this->assertSame('Selviana', OutsourceEmployee::query()->where('outsource_id', 'DM20261000')->value('full_name'));
        foreach (['outsource_employees' => 'outsource_id', 'outsource_payslips' => 'outsource_id', 'outsource_incentives' => 'outsource_id', 'employee_documents' => 'employee_id'] as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $this->assertSame(0, DB::table($table)->where($column, 'DM20260129')->count(), $table);
            $this->assertSame(1, DB::table($table)->where($column, 'DM20261000')->count(), $table);
        }

        $audit = AuditLog::query()->where('action', 'id_reassigned')->firstOrFail();
        $this->assertSame('DM20261000', $audit->entity_id);
        $this->assertSame('DM20260129', $audit->old_value);
        $this->assertStringContainsString('Bentrok ID dengan Attendance', (string) $audit->field);
    }

    public function test_refuses_unknown_source_taken_target_and_sequence_shifting_target(): void
    {
        $this->seedSelviana();
        OutsourceEmployee::query()->create(['outsource_id' => 'DM20260130', 'full_name' => 'Imam']);

        $this->artisan('mito:outsource-reassign-id', ['from' => 'DM20269999', 'to' => 'DM20261000', '--execute' => true])
            ->assertFailed();
        $this->artisan('mito:outsource-reassign-id', ['from' => 'DM20260129', 'to' => 'DM20260130', '--execute' => true])
            ->assertFailed();
        $this->artisan('mito:outsource-reassign-id', ['from' => 'DM20260129', 'to' => 'DM20260500', '--execute' => true])
            ->expectsOutputToContain('reserved_ids')
            ->assertFailed();

        $this->assertDatabaseHas('outsource_employees', ['outsource_id' => 'DM20260129']);
    }

    private function seedSelviana(): void
    {
        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260129',
            'full_name' => 'Selviana',
            'vendor' => 'Damarindo',
            'remarks' => 'Resign per 20/9',
        ]);
        if (Schema::hasTable('outsource_payslips')) {
            DB::table('outsource_payslips')->insert([
                'period' => '2026-09',
                'outsource_id' => 'DM20260129',
                'full_name' => 'Selviana',
                'hke' => 20,
                'basic_salary' => 1000000,
                'take_home_pay' => 1000000,
            ]);
        }
        if (Schema::hasTable('outsource_incentives')) {
            DB::table('outsource_incentives')->insert([
                'period' => '2026-09',
                'outsource_id' => 'DM20260129',
                'full_name' => 'Selviana',
                'incentive_amount' => 50000,
            ]);
        }
        DB::table('employee_documents')->insert([
            'document_id' => 'DOC-TEST-1',
            'employee_id' => 'DM20260129',
            'doc_code' => 'PKWT',
        ]);
    }
}
