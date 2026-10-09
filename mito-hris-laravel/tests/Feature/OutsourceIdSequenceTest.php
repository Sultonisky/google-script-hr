<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OutsourceIdSequenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-09 10:00', 'Asia/Jakarta'));
        config([
            'hris.data_driver' => 'pgsql',
            'hris.outsource.id_prefix' => 'DM',
            'hris.outsource.reserved_ids' => ['DM20261000'],
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();

        foreach (['DM20250999' => 'Tahun Lalu', 'DM20260134' => 'Muhammad Rosidin', 'DM20261000' => 'Selviana'] as $id => $name) {
            OutsourceEmployee::query()->create(['outsource_id' => $id, 'full_name' => $name, 'vendor' => 'Damarindo']);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hr_manual_add_and_public_form_continue_the_same_dm_sequence(): void
    {
        $this->actingAsOutsourceManager();

        $fromHr = $this->postJson('/hr/outsource', [
            'outsourceId' => 'DM20269999',
            'fullName' => 'Input HR',
            'vendor' => 'Damarindo',
        ])->assertCreated()->json('employee.outsourceId');

        $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->publicPayload(['outsource_id' => 'DM20269998']))
            ->assertRedirect(route('public.outsource.success'));
        $fromForm = OutsourceEmployee::query()->where('full_name', 'Pelamar Form')->value('outsource_id');

        $this->assertSame('DM20260135', $fromHr);
        $this->assertSame('DM20260136', $fromForm);
        $this->assertDatabaseMissing('outsource_employees', ['outsource_id' => 'DM20269999']);
        $this->assertDatabaseMissing('outsource_employees', ['outsource_id' => 'DM20269998']);
        $this->assertDatabaseMissing('outsource_employees', ['outsource_id' => 'DM20261001']);
    }

    private function actingAsOutsourceManager(): void
    {
        $email = 'hr.outsource@mito.id';
        $permissions = ['view_outsource', 'manage_outsource'];
        $repository = app(UserPermissionRepositoryInterface::class);
        foreach ($permissions as $permission) {
            $repository->upsert($email, $permission, true, 'test');
        }
        app(PermissionResolver::class)->forget($email);

        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'HR Outsource',
            'role' => 'User',
            'permissions' => $permissions,
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);
    }

    private function publicPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Pelamar Form',
            'citizen_id_address' => 'Jl. Merdeka No. 1, RT 01/RW 02, Bandung',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Bandung',
            'last_education' => 'SLTA',
            'whatsapp_number' => '81234500001',
            'email' => 'pelamar@example.com',
            'vendor' => 'Damarindo',
            'job_title' => 'SPB/SPG Toko',
            'work_location' => 'Toko Sinar Jaya',
            'work_city' => 'Kota Bandung',
            'cost_center' => 'Bandung',
            'entity' => 'PT. Mahakarya Sukses Indonesia',
            'mito_join_date' => '2026-10-01',
            'contract_start_date' => '2026-10-01',
            'contract_end_date' => '2026-12-31',
            'payroll_scheme' => '70/30',
            'umk_amount' => '5396761',
            'bank_account' => '1234567890',
            'agreement' => '1',
        ], $overrides);
    }
}
