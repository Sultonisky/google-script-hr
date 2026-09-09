<?php

namespace Tests\Feature;

use App\Enums\CertStatus;
use App\Enums\CertType;
use App\Models\Certification;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use App\Services\CertificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Backend employee validation (ExistingEmployeeId) resolves against the
        // active provider. Feature tests use the isolated local dummy source.
        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
    }

    private function service(): CertificationService
    {
        return app(CertificationService::class);
    }

    private function makeCert(array $attrs = []): Certification
    {
        return Certification::create(array_merge([
            'cert_type'            => CertType::ISO,
            'name'                 => 'Sertifikasi Engineer',
            'issuing_organization' => 'BNSP',
            'issue_date'           => '2026-01-01',
            'expiry_date'          => '2028-01-01',
            'status'               => CertStatus::ACTIVE,
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            'division'             => 'GA',
            'department'           => 'Facility Management',
            'created_by'           => 'test@mito.id',
        ], $attrs));
    }

    private function authAs(string $role): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => strtolower($role) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));
    }

    private function fakeAuditLogRepo(): void
    {
        $this->app->instance(AuditLogRepositoryInterface::class, $this->createMock(AuditLogRepositoryInterface::class));
    }

    // ── Basic creation ─────────────────────────────────────────────

    #[Test]
    public function certification_can_be_created_without_code(): void
    {
        $cert = $this->makeCert(['cert_code' => null]);
        $this->assertNull($cert->cert_code);
        $this->assertDatabaseHas('certifications', ['id' => $cert->id]);
    }

    #[Test]
    public function certification_types_have_the_expected_business_classification(): void
    {
        $this->assertSame('Standar produk atau barang', CertType::SNI->description());
        $this->assertSame('Sistem manajemen perusahaan', CertType::ISO->description());
        $this->assertSame('Keselamatan dan kesehatan kerja', CertType::K3->description());
        $this->assertSame('Keamanan pangan dan material kontak makanan', CertType::FOOD_SAFETY->description());
        $this->assertSame('Keselamatan dan kepatuhan produk konsumen', CertType::PRODUCT_SAFETY->description());
        $this->assertSame(['SNI', 'ISO', 'K3', 'Food Safety', 'Product Safety'], array_map(
            static fn (CertType $type): string => $type->value,
            CertType::cases(),
        ));
    }

    #[Test]
    public function certification_can_be_created_with_manual_code(): void
    {
        $cert = $this->makeCert(['cert_code' => 'SRT-CUSTOM-001']);
        $this->assertSame('SRT-CUSTOM-001', $cert->cert_code);
    }

    #[Test]
    public function duplicate_manual_code_is_rejected(): void
    {
        $this->makeCert(['cert_code' => 'SRT-CUSTOM-001']);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->makeCert(['cert_code' => 'SRT-CUSTOM-001']);
    }

    // ── Code generation ────────────────────────────────────────────

    #[Test]
    public function automatic_code_generation_works(): void
    {
        $this->makeCert(['cert_code' => 'SRT-00001', 'name' => 'A']);
        $this->assertSame('SRT-00002', $this->service()->generateCode());
    }

    #[Test]
    public function sequence_uses_highest_existing_numeric_code(): void
    {
        foreach (['SRT-00001', 'SRT-00002', 'SRT-00005'] as $i => $code) {
            $this->makeCert(['cert_code' => $code, 'name' => "A{$i}"]);
        }
        $this->assertSame('SRT-00006', $this->service()->generateCode());
    }

    #[Test]
    public function manual_custom_codes_do_not_interfere_with_numeric_sequence(): void
    {
        $this->makeCert(['cert_code' => 'SRT-CUSTOM-001', 'name' => 'Manual']);
        $this->makeCert(['cert_code' => 'SRT-00001', 'name' => 'A']);
        $this->assertSame('SRT-00002', $this->service()->generateCode());
    }

    // ── Status computation ─────────────────────────────────────────

    #[Test]
    public function active_status_is_set_for_future_expiry(): void
    {
        $this->assertSame(CertStatus::ACTIVE, $this->service()->computeStatus(now()->addDays(30)->toDateString()));
    }

    #[Test]
    public function expired_status_is_set_for_past_expiry(): void
    {
        $this->assertSame(CertStatus::EXPIRED, $this->service()->computeStatus(now()->subDays(1)->toDateString()));
    }

    #[Test]
    public function null_expiry_date_gives_active_status(): void
    {
        $this->assertSame(CertStatus::ACTIVE, $this->service()->computeStatus(null));
    }

    #[Test]
    public function revoked_status_is_preserved(): void
    {
        $result = $this->service()->computeStatus(now()->subDays(5)->toDateString(), CertStatus::REVOKED);
        $this->assertSame(CertStatus::REVOKED, $result);
    }

    #[Test]
    public function suspended_status_is_preserved(): void
    {
        $result = $this->service()->computeStatus(now()->subDays(5)->toDateString(), CertStatus::SUSPENDED);
        $this->assertSame(CertStatus::SUSPENDED, $result);
    }

    // ── Database persistence ───────────────────────────────────────

    #[Test]
    public function certification_is_stored_in_database(): void
    {
        $cert = $this->makeCert(['name' => 'Sertifikasi K3', 'employee_id' => 'EMP-010']);
        $this->assertDatabaseHas('certifications', [
            'id'          => $cert->id,
            'employee_id' => 'EMP-010',
            'name'        => 'Sertifikasi K3',
        ]);
    }

    // ── Audit logging ──────────────────────────────────────────────

    #[Test]
    public function audit_log_occurs_when_certification_code_is_generated(): void
    {
        $audit = $this->createMock(\App\Repositories\Contracts\AuditLogRepositoryInterface::class);
        $audit->expects($this->atLeastOnce())->method('log');
        $this->app->instance(\App\Repositories\Contracts\AuditLogRepositoryInterface::class, $audit);

        $cert = $this->makeCert(['cert_code' => null]);
        $controller = app(\App\Http\Controllers\HR\CertificationController::class);
        $controller->generateCode($cert);
    }

    // ── HTTP endpoints (POST/PUT/DELETE) ───────────────────────────

    #[Test]
    public function legal_can_create_certification_via_http(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'SIM A Kendaraan Dinas',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'issuing_organization' => 'Korlantas Polri',
            'certificate_number'   => 'SIM-88776655',
            'issue_date'           => '2026-03-01',
            'expiry_date'          => '2029-03-01',
            'employee_id'          => '2021072301',
            'employee_name'        => 'Siti Rahayu',
            'division'             => 'Legal',      // must be ignored server-side
            'department'           => 'Legal Compliance',
            'notes'                => 'Created via HTTP test.',
        ];

        $response = $this->postJson('/hr/certifications', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('certification.name', 'SIM A Kendaraan Dinas');

        $id = $response->json('certification.id');
        $this->assertDatabaseHas('certifications', [
            'id'        => $id,
            'cert_type' => CertType::SNI->value,
            'name'      => 'SIM A Kendaraan Dinas',
            'cert_code' => null,
            'status'    => CertStatus::ACTIVE->value, // auto-derived (no status in payload)
        ]);
        // Server is source of truth: employee fields come from the provider.
        $this->assertDatabaseHas('certifications', [
            'id'            => $id,
            'employee_id'   => '2021072301',
            'employee_name' => 'Siti Rahayu',
            'division'      => 'Quality Control',
            'department'    => 'Quality Management System',
        ]);
    }

    #[Test]
    public function certification_can_be_created_without_employee_owner(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $response = $this->postJson('/hr/certifications', [
            'cert_type'            => CertType::ISO->value,
            'name'                 => 'ISO Company Scope Only',
            'company_scope'        => 'Quality Management System',
            'issuing_organization' => 'TUV Rheinland Indonesia',
            'issue_date'           => '2026-01-01',
        ])->assertCreated();

        $this->assertDatabaseHas('certifications', [
            'id'            => $response->json('certification.id'),
            'company_scope' => 'Quality Management System',
            'employee_id'   => null,
            'employee_name' => null,
        ]);
    }

    #[Test]
    public function legacy_certification_type_is_rejected(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $this->postJson('/hr/certifications', [
            'cert_type'            => 'Professional',
            'name'                 => 'Legacy Type Must Fail',
            'issuing_organization' => 'BNSP',
            'issue_date'           => '2026-03-01',
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
        ])->assertUnprocessable()->assertJsonValidationErrors(['cert_type']);

        $this->assertDatabaseMissing('certifications', ['name' => 'Legacy Type Must Fail']);
    }

    #[Test]
    public function legal_can_update_certification_via_http(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $cert = $this->makeCert();

        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'SIM B1 Umum - Diperpanjang',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'description'          => 'Perpanjangan berkala via HTTP test.',
            'issuing_organization' => 'Korlantas Polri',
            'certificate_number'   => 'SIM-B1-889900',
            'issue_date'           => '2026-03-01',
            'expiry_date'          => '2029-03-01',
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            'division'             => 'Legal',      // must be ignored server-side
            'department'           => 'Legal Compliance',
            'notes'                => 'Diubah lewat UI PUT.',
        ];

        $this->putJson("/hr/certifications/{$cert->id}", $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('certification.name', 'SIM B1 Umum - Diperpanjang');

        $fresh = $cert->fresh();
        $this->assertSame(CertType::SNI, $fresh->cert_type);
        $this->assertSame(CertStatus::ACTIVE, $fresh->status);
        $this->assertSame('SIM B1 Umum - Diperpanjang', $fresh->name);
        $this->assertSame('Korlantas Polri', $fresh->issuing_organization);
        $this->assertSame('2026-03-01', $fresh->issue_date->toDateString());
        $this->assertSame('2029-03-01', $fresh->expiry_date->toDateString());
        // Employee profile is denormalized from the provider, not from the request.
        $this->assertSame('2019031401', $fresh->employee_id);
        $this->assertSame('Budi Santoso', $fresh->employee_name);
        $this->assertSame('GA', $fresh->division);
        $this->assertSame('Facility Management', $fresh->department);
    }

    #[Test]
    public function certification_attachment_can_be_uploaded_served_and_replaced(): void
    {
        Storage::fake('local');
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'SIM A dengan Attachment',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'issuing_organization' => 'Korlantas Polri',
            'issue_date'           => '2026-03-01',
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            'attachment'           => UploadedFile::fake()->create('sim-a.pdf', 100, 'application/pdf'),
        ];

        $response = $this->post('/hr/certifications', $payload)->assertCreated();
        $cert = Certification::findOrFail($response->json('certification.id'));
        $oldPath = $cert->attachment_path;

        Storage::disk('local')->assertExists($oldPath);
        $this->get("/hr/certifications/{$cert->id}/attachment")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $updatePayload = [
            '_method'             => 'PUT',
            'cert_type'           => CertType::SNI->value,
            'name'                => 'SIM A dengan Attachment Baru',
            'product_scope'       => 'Rice Cooker',
            'brand'              => 'MITO',
            'issuing_organization' => 'Korlantas Polri',
            'issue_date'          => '2026-03-01',
            'employee_id'         => '2019031401',
            'employee_name'       => 'Budi Santoso',
            'attachment'          => UploadedFile::fake()->image('sim-a-baru.jpg'),
        ];

        $this->post("/hr/certifications/{$cert->id}", $updatePayload)->assertOk();

        $newPath = $cert->fresh()->attachment_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    #[Test]
    public function classification_rejects_mixed_product_and_company_fields(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $this->postJson('/hr/certifications', [
            'cert_type'            => CertType::ISO->value,
            'name'                 => 'ISO Mixed Scope',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'company_scope'        => 'Quality Management System',
            'issuing_organization' => 'TUV',
            'issue_date'           => '2026-01-01',
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
        ])->assertUnprocessable()->assertJsonValidationErrors(['product_scope', 'brand']);
    }

    #[Test]
    public function update_autoderives_expired_status_when_expiry_moved_to_past(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $cert = $this->makeCert();
        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'SIM A - Kedaluwarsa',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'issuing_organization' => 'Korlantas Polri',
            'issue_date'           => '2023-01-01',
            'expiry_date'          => now()->subDays(5)->toDateString(),
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            // status deliberately omitted -> "Auto"
        ];

        $this->putJson("/hr/certifications/{$cert->id}", $payload)->assertOk();

        $this->assertSame(CertStatus::EXPIRED, $cert->fresh()->status);
    }

    #[Test]
    public function update_preserves_explicit_suspended_status(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $cert = $this->makeCert(['status' => CertStatus::SUSPENDED, 'expiry_date' => now()->subDays(5)->toDateString()]);
        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'SIM B2 - Ditahan',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'issuing_organization' => 'Korlantas Polri',
            'issue_date'           => '2023-01-01',
            'expiry_date'          => now()->subDays(5)->toDateString(),
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
        ];

        $this->putJson("/hr/certifications/{$cert->id}", $payload)->assertOk();

        $this->assertSame(CertStatus::SUSPENDED, $cert->fresh()->status);
    }

    #[Test]
    public function legal_can_delete_certification_via_http(): void
    {
        $this->authAs('LEGAL');
        $this->fakeAuditLogRepo();

        $cert = $this->makeCert();

        $this->deleteJson("/hr/certifications/{$cert->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('certifications', ['id' => $cert->id]);
    }

    #[Test]
    public function viewer_role_cannot_update_or_delete_certification(): void
    {
        $this->authAs('User'); // view_certification only
        $this->fakeAuditLogRepo();

        $cert = $this->makeCert();

        $payload = [
            'cert_type'            => CertType::SNI->value,
            'name'                 => 'Percobaan Tidak Sah',
            'product_scope'        => 'Rice Cooker',
            'brand'                => 'MITO',
            'issuing_organization' => 'X',
            'issue_date'           => '2026-01-01',
            'employee_id'          => 'EMP-001',
            'employee_name'        => 'Budi',
        ];

        $this->getJson("/hr/certifications/{$cert->id}/json")->assertOk();
        $this->putJson("/hr/certifications/{$cert->id}", $payload)->assertForbidden();
        $this->deleteJson("/hr/certifications/{$cert->id}")->assertForbidden();

        $this->assertDatabaseHas('certifications', ['id' => $cert->id]);
    }
}
