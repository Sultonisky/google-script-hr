<?php

namespace Tests\Feature;

use App\Models\OutsourceEmployee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutsourceDirectorySyncApiTest extends TestCase
{
    use RefreshDatabase;

    private const API_TOKEN = 'test-outsource-directory-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'hris.data_driver' => 'pgsql',
            'hris.integration.outsource_sync_api_token' => self::API_TOKEN,
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    public function test_sync_creates_missing_person_and_skips_existing_id_without_overwriting(): void
    {
        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260001',
            'full_name' => 'Nama HRIS',
            'vendor' => 'Damarindo',
            'created_by' => 'HR Administrator',
        ]);

        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', [
                'people' => [
                    ['outsource_id' => 'dm20260001', 'full_name' => 'Nama HRIS'],
                    ['outsource_id' => 'DM20260002', 'full_name' => 'Pekerja Baru'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.outsource_id', 'DM20260001')
            ->assertJsonPath('data.0.status', 'skipped')
            ->assertJsonPath('data.1.status', 'created')
            ->assertJsonPath('meta.processed', 2)
            ->assertJsonPath('meta.created', 1)
            ->assertJsonPath('meta.skipped', 1);

        $this->assertSame(2, OutsourceEmployee::query()->count());
        $existing = OutsourceEmployee::query()->where('outsource_id', 'DM20260001')->firstOrFail();
        $this->assertSame('Nama HRIS', $existing->full_name);
        $this->assertSame('Damarindo', $existing->vendor);

        $created = OutsourceEmployee::query()->where('outsource_id', 'DM20260002')->firstOrFail();
        $this->assertSame('Pekerja Baru', $created->full_name);
        $this->assertSame('Attendance Person List', $created->created_by);
        $this->assertNull($created->vendor);
        $this->assertNull($created->email);
    }

    public function test_dry_run_reports_missing_people_without_writing(): void
    {
        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', [
                'people' => [
                    ['outsource_id' => 'DM20260003', 'full_name' => 'Preview Person'],
                ],
                'dry_run' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.0.status', 'would_create')
            ->assertJsonPath('meta.would_create', 1);

        $this->assertDatabaseMissing('outsource_employees', ['outsource_id' => 'DM20260003']);
    }

    public function test_existing_id_with_different_name_is_reported_as_conflict_without_overwriting(): void
    {
        OutsourceEmployee::query()->create([
            'outsource_id' => 'DM20260124',
            'full_name' => 'THORIQUL MUJAHIDIEN',
            'vendor' => 'Damarindo',
            'created_by' => 'HR Administrator',
        ]);

        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', [
                'people' => [
                    ['outsource_id' => 'dm20260124', 'full_name' => 'ANWAR JAKARTA'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.status', 'conflict')
            ->assertJsonPath('meta.conflict', 1);

        $this->assertSame(1, OutsourceEmployee::query()->count());
        $this->assertDatabaseHas('outsource_employees', [
            'outsource_id' => 'DM20260124',
            'full_name' => 'THORIQUL MUJAHIDIEN',
            'vendor' => 'Damarindo',
        ]);
    }

    public function test_sync_requires_token_and_fails_closed_when_token_is_not_configured(): void
    {
        $payload = ['people' => [['outsource_id' => 'DM20260004', 'full_name' => 'Worker']]];

        $this->postJson('/api/v1/outsource/persons/sync', $payload)->assertUnauthorized();

        config(['hris.integration.outsource_sync_api_token' => '']);
        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', $payload)
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    public function test_sync_rejects_invalid_and_duplicate_ids(): void
    {
        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', [
                'people' => [
                    ['outsource_id' => 'bad id', 'full_name' => 'Invalid ID'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('people.0.outsource_id');

        $this->withToken(self::API_TOKEN)
            ->postJson('/api/v1/outsource/persons/sync', [
                'people' => [
                    ['outsource_id' => 'dm20260005', 'full_name' => 'First'],
                    ['outsource_id' => 'DM20260005', 'full_name' => 'Duplicate'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('people.1.outsource_id');
    }
}
