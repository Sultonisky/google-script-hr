<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AttendanceOutsourcePushTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://attendance.example.test/api/v1/integrations/hris/outsource-persons';
    private const TOKEN = 'test-attendance-outsource-push-token';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-09 10:00', 'Asia/Jakarta'));
        config([
            'hris.data_driver' => 'pgsql',
            'hris.outsource.reserved_ids' => ['DM20261000'],
            'hris.integration.attendance.base_url' => 'https://attendance.example.test',
            'hris.integration.attendance.outsource_push_api_token' => self::TOKEN,
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
        OutsourceEmployee::query()->create(['outsource_id' => 'DM20260134', 'full_name' => 'Muhammad Rosidin', 'vendor' => 'Damarindo']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hr_manual_add_pushes_id_and_name_to_attendance(): void
    {
        Http::fake([self::URL => Http::response([
            'success' => true,
            'data' => [['outsource_id' => 'DM20260135', 'status' => 'created']],
            'meta' => [],
        ])]);
        $this->actingAsOutsourceManager();

        $this->postJson('/hr/outsource', ['fullName' => 'Input HR', 'vendor' => 'Damarindo'])
            ->assertCreated()
            ->assertJsonPath('employee.outsourceId', 'DM20260135');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::URL
            && $request->hasHeader('Authorization', 'Bearer ' . self::TOKEN)
            && $request['people'] === [['outsource_id' => 'DM20260135', 'full_name' => 'Input HR']]
            && $request['dry_run'] === false);
    }

    public function test_public_form_pushes_and_attendance_outage_does_not_block_registration(): void
    {
        Http::fake([self::URL => Http::response(['message' => 'down'], 503)]);

        $this->onDomain('outsource')
            ->from(route('public.outsource.apply'))
            ->post(route('public.outsource.store'), $this->publicPayload())
            ->assertRedirect(route('public.outsource.success'));

        $this->assertDatabaseHas('outsource_employees', ['outsource_id' => 'DM20260135', 'full_name' => 'Pelamar Form']);
        Http::assertSent(fn (Request $request): bool => $request['people'] === [['outsource_id' => 'DM20260135', 'full_name' => 'Pelamar Form']]);
    }

    public function test_nothing_is_sent_when_attendance_is_not_configured(): void
    {
        config(['hris.integration.attendance.base_url' => '']);
        Http::fake();
        $this->actingAsOutsourceManager();

        $this->postJson('/hr/outsource', ['fullName' => 'Input HR', 'vendor' => 'Damarindo'])->assertCreated();

        Http::assertNothingSent();
    }

    public function test_push_command_previews_by_default_and_executes_explicitly(): void
    {
        Http::fake([self::URL => fn (Request $request) => Http::response([
            'success' => true,
            'data' => [['outsource_id' => 'DM20260134', 'status' => $request['dry_run'] ? 'would_create' : 'created']],
        ])]);

        $this->artisan('mito:outsource-push-attendance')
            ->expectsOutputToContain('WOULD_CREATE: DM20260134')
            ->expectsOutputToContain('Preview saja')
            ->assertSuccessful();

        $this->artisan('mito:outsource-push-attendance', ['--execute' => true])
            ->expectsOutputToContain('CREATED: DM20260134')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request['dry_run'] === true);
        Http::assertSent(fn (Request $request): bool => $request['dry_run'] === false);
    }

    public function test_push_command_fails_on_unexpected_attendance_response(): void
    {
        Http::fake([self::URL => Http::response(['success' => true, 'data' => []])]);

        $this->artisan('mito:outsource-push-attendance', ['--execute' => true])->assertFailed();
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

    private function publicPayload(): array
    {
        return [
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
        ];
    }
}
