<?php

namespace Tests\Feature;

use App\Enums\CertStatus;
use App\Enums\CertType;
use App\Models\Certification;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Certification flows that cross the employee picker contract:
 * backend validates the picked employee and persists provider-supplied
 * name/division/department (server is the source of truth).
 */
class CertificationEmployeeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
        $this->app->instance(
            AuditLogRepositoryInterface::class,
            $this->createMock(AuditLogRepositoryInterface::class),
        );
    }

    private function actingAsLegal(): static
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => 'legal@mito.id',
            'fullName'    => 'Legal User',
            'role'        => 'LEGAL',
            'permissions' => config('hris.auth.role_permissions')['LEGAL'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

        return $this;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'cert_type'            => CertType::ISO->value,
            'company_scope'        => 'Quality Management System',
            'name'                 => 'Sertifikasi Auditor ISO 45001',
            'issuing_organization' => 'PT Surveyor Indonesia',
            'certificate_number'   => 'SI/45K/2025/0522',
            'issue_date'           => '2025-06-11',
            'expiry_date'          => '2028-06-10',
            'employee_id'          => '2020093001',
            'employee_name'        => 'Maya Anggraini',
            'division'             => 'Operations',  // must be ignored server-side
            'department'           => 'Logistics',
            'notes'                => 'picker flow',
        ], $overrides);
    }

    #[Test]
    public function create_with_picked_employee_persists_provider_profile(): void
    {
        $this->actingAsLegal();

        $this->postJson('/hr/certifications', $this->payload())
            ->assertCreated()
            ->assertJsonPath('certification.employee_id', '2020093001');

        $this->assertDatabaseHas('certifications', [
            'name'          => 'Sertifikasi Auditor ISO 45001',
            'employee_id'   => '2020093001',
            'employee_name' => 'Maya Anggraini',
            'division'      => 'Legal',            // provider value, not the posted one
            'department'    => 'Legal Compliance',
        ]);
    }

    #[Test]
    public function invalid_employee_id_is_rejected_with_422(): void
    {
        $this->actingAsLegal();

        $this->postJson('/hr/certifications', $this->payload([
            'employee_id'   => 'EMP-NOT-EXIST',
            'employee_name' => 'Hacker',
            'division'      => 'X',
            'department'    => 'Y',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id']);

        $this->assertDatabaseCount('certifications', 0);
    }

    #[Test]
    public function edit_can_change_employee_and_view_reflects_it(): void
    {
        $this->actingAsLegal();

        $cert = Certification::create([
            'cert_type'            => CertType::ISO->value,
            'name'                 => 'Sertifikasi ISO 9001',
            'issuing_organization' => 'TUV',
            'issue_date'           => '2024-01-01',
            'expiry_date'          => '2027-01-01',
            'status'               => CertStatus::ACTIVE->value,
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            'division'             => 'GA',
            'department'           => 'Facility Management',
            'created_by'           => 'legal@mito.id',
        ]);

        // Change employee from Budi (GA) to Siti (Quality Control).
        $this->putJson('/hr/certifications/' . $cert->id, $this->payload([
            'name'           => 'Sertifikasi ISO 9001',
            'employee_id'    => '2021072301',
            'employee_name'  => 'Siti Rahayu',
            'division'       => 'HR',             // ignored
            'department'     => 'Learning & Development',
            'status'         => CertStatus::ACTIVE->value,
        ]))->assertOk();

        $fresh = $cert->fresh();
        $this->assertSame('2021072301', $fresh->employee_id);
        $this->assertSame('Siti Rahayu', $fresh->employee_name);
        $this->assertSame('Quality Control', $fresh->division);
        $this->assertSame('Quality Management System', $fresh->department);

        // View JSON + list render the stored employee.
        $this->getJson('/hr/certifications/' . $cert->id . '/json')
            ->assertOk()
            ->assertJsonPath('certification.employee_name', 'Siti Rahayu')
            ->assertJsonPath('certification.division', 'Quality Control');

        $this->get('/hr/certifications')
            ->assertOk()
            ->assertSee('Siti Rahayu')
            ->assertSee('2021072301');
    }
}
