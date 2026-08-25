<?php

namespace Tests\Feature;

use App\DTOs\MprData;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Services\MprPdfService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class MprFlowTest extends TestCase
{
    /**
     * Build a session user array that mirrors what AuthService puts in session('hr_user').
     *
     * @param  string        $role
     * @param  string|null   $email
     * @param  string|null   $name
     * @param  array<string> $entities  Entity codes assigned to this user (e.g. ['MSI','SPI'])
     * @param  string        $branch    Branch/lokasi requestor
     */
    private function makeSessionUser(
        string $role,
        ?string $email = null,
        ?string $name = null,
        array $entities = ['MSI'],
        string $branch = 'Head Office (HO)'
    ): array {
        $permissions = config('hris.auth.role_permissions')[$role] ?? [];
        $isManager   = ($role === 'Manager');

        return [
            'email'        => $email ?? (strtolower(str_replace(' ', '.', $role)) . '@mito.id'),
            'fullName'     => $name  ?? ($role . ' User'),
            'role'         => $role,
            'permissions'  => $permissions,
            // Manager sessions come from mpr_requestor; all others from Users
            'auth_domain'  => $isManager ? 'mpr_requestor' : 'users',
            'entities'     => $isManager ? $entities : [],
            'branch'       => $isManager ? $branch   : '',
            'requestor_id' => $isManager ? 'MPR-REQ-TEST' : '',
        ];
    }

    private function actingAsRole(
        string $role,
        ?string $email = null,
        ?string $name = null,
        array $entities = ['MSI'],
        string $branch = 'Head Office (HO)'
    ): static {
        Session::put('hr_user', $this->makeSessionUser($role, $email, $name, $entities, $branch));
        return $this;
    }

    // =========================================================================
    // Helper: build MprData with new field names
    // =========================================================================
    private function makeMprData(array $overrides = []): MprData
    {
        return new MprData(
            mprNumber:       $overrides['mprNumber']       ?? 'MPR-20260824-0001',
            requestDate:     $overrides['requestDate']     ?? '2026-08-24',
            requestorName:   $overrides['requestorName']   ?? 'Manager User',
            requestorEmail:  $overrides['requestorEmail']  ?? 'manager@mito.id',
            entity:          $overrides['entity']          ?? 'MSI',
            branch:          $overrides['branch']          ?? 'Head Office (HO)',
            department:      $overrides['department']      ?? 'IT',
            division:        $overrides['division']        ?? 'IT',
            position:        $overrides['position']        ?? 'Backend Developer',
            jobLevel:        $overrides['jobLevel']        ?? 'Staff',
            workLocation:    $overrides['workLocation']    ?? 'Head Office (HO)',
            employmentType:  $overrides['employmentType']  ?? 'Permanent (PKWTT)',
            quantity:        $overrides['quantity']        ?? 2,
            expectedJoinDate: $overrides['expectedJoinDate'] ?? '2026-09-01',
            reason:          $overrides['reason']          ?? 'Penambahan Karyawan Baru',
            status:          $overrides['status']          ?? 'Submitted',
            createdBy:       $overrides['createdBy']       ?? 'manager@mito.id',
        );
    }

    /** @test */
    public function unauthenticated_user_is_redirected_from_mpr_routes(): void
    {
        Session::forget('hr_user');

        $response = $this->get('/hr/mpr');
        $response->assertRedirect('/login');

        $postResponse = $this->post('/hr/mpr', []);
        $postResponse->assertRedirect('/login');
    }

    /** @test */
    public function manager_role_has_correct_permissions_and_restrictions(): void
    {
        $this->actingAsRole('Manager');

        $this->assertTrue(Gate::allows('view_mpr'), 'Manager should have view_mpr');
        $this->assertTrue(Gate::allows('create_mpr'), 'Manager should have create_mpr');
        $this->assertTrue(Gate::allows('export_mpr'), 'Manager should have export_mpr');

        $this->assertFalse(Gate::allows('view_recruitment'), 'Manager should not have view_recruitment');
        $this->assertFalse(Gate::allows('view_employees'), 'Manager should not have view_employees');
        $this->assertFalse(Gate::allows('manage_settings'), 'Manager should not have manage_settings');
        $this->assertFalse(Gate::allows('manage_probation'), 'Manager should not have manage_probation');
    }

    /** @test */
    public function manager_is_redirected_from_dashboard_and_forbidden_from_hr_modules(): void
    {
        $this->actingAsRole('Manager');

        // Manager accessing HR Dashboard -> automatically redirected to their allowed MPR page
        $this->get('/hr/dashboard')->assertRedirect(route('hr.mpr.index'));

        // Manager visiting login page when authenticated -> redirected to MPR page
        $this->get('/login')->assertRedirect(route('hr.mpr.index'));

        // Manager accessing Recruitment -> 403
        $this->get('/hr/recruitment')->assertStatus(403);

        // Manager accessing Employees -> 403
        $this->get('/hr/employees')->assertStatus(403);

        // Manager accessing Probation -> 403
        $this->get('/hr/probation')->assertStatus(403);

        // Manager accessing Outsource -> 403
        $this->get('/hr/outsource')->assertStatus(403);

        // Manager accessing Settings -> 403
        $this->get('/hr/settings')->assertStatus(403);

        // Manager accessing Users -> 403
        $this->get('/hr/users')->assertStatus(403);
    }

    /** @test */
    public function manager_can_view_mpr_index_and_sees_own_records(): void
    {
        $managerEmail = 'manager1@mito.id';
        $this->actingAsRole('Manager', $managerEmail, 'Budi Manager', ['MSI', 'SPI'], 'Bandung');

        $mockMpr = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0001',
            'requestorName' => 'Budi Manager',
            'requestorEmail' => $managerEmail,
            'entity'        => 'MSI',
            'branch'        => 'Bandung',
            'position'      => 'Backend Developer',
            'createdBy'     => $managerEmail,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAllForManager')
            ->once()
            ->with($managerEmail, Mockery::any())
            ->andReturn(collect([$mockMpr]));

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $response = $this->get('/hr/mpr');
        $response->assertStatus(200);
        $response->assertSee('MPR-20260824-0001');
        $response->assertSee('Backend Developer');
        $response->assertSee('Role: Manager');
    }

    /** @test */
    public function manager_can_submit_mpr_and_identity_is_resolved_server_side(): void
    {
        $managerEmail   = 'manager.it@mito.id';
        $managerName    = 'Sari Manager IT';
        $managerBranch  = 'Bandung';
        $allowedEntities = ['MSI', 'SPI', 'MEP'];

        $this->actingAsRole('Manager', $managerEmail, $managerName, $allowedEntities, $managerBranch);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (MprData $data) use ($managerEmail, $managerName, $managerBranch) {
                // Identity MUST come from session, not from payload
                return $data->requestorEmail === $managerEmail
                    && $data->requestorName  === $managerName
                    && $data->branch         === $managerBranch
                    && $data->entity         === 'SPI'      // entity yang dipilih dari form
                    && $data->createdBy      === $managerEmail
                    && $data->position       === 'Senior Laravel Engineer'
                    && $data->quantity       === 3;
            })
            ->andReturnUsing(function (MprData $data) {
                $data->mprNumber = 'MPR-20260824-9999';
                return $data;
            });

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // Attempting to send forged manager_email and forged entity in payload — must be rejected
        $payload = [
            'position'           => 'Senior Laravel Engineer',
            'department'         => 'IT',
            'division'           => 'IT',
            'job_level'          => 'Senior Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 3,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'requirements'       => 'Pendidikan S1, Pengalaman 3 tahun',
            'job_description'    => 'Membangun backend microservice',
            'entity'             => 'SPI', // valid: ada di $allowedEntities
            // These forged fields should be ignored for Manager role
            'manager_name'       => 'Forged Name',
            'manager_email'      => 'forged@other.com',
        ];

        $response = $this->postJson('/hr/mpr', $payload);
        $response->assertStatus(201);
        $response->assertJson([
            'success'    => true,
            'mpr_number' => 'MPR-20260824-9999',
            'pdf_url'    => route('hr.mpr.pdf', 'MPR-20260824-9999'),
        ]);
    }

    /** @test */
    public function manager_cannot_submit_mpr_with_entity_not_in_assignment(): void
    {
        $managerEmail    = 'manager.it@mito.id';
        $allowedEntities = ['MSI', 'SPI'];

        $this->actingAsRole('Manager', $managerEmail, 'Sari Manager IT', $allowedEntities, 'Bandung');

        // PII is NOT in allowed entities
        $payload = [
            'position'           => 'Staff Finance',
            'department'         => 'Finance',
            'division'           => 'FAT & GA',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'entity'             => 'PII', // TIDAK diizinkan
        ];

        $response = $this->postJson('/hr/mpr', $payload);
        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function manager_without_entity_assignment_cannot_submit_mpr(): void
    {
        $this->actingAsRole('Manager', 'no-entity@mito.id', 'No Entity Manager', [], 'Bandung');

        $payload = [
            'position'           => 'Staff IT',
            'department'         => 'IT',
            'division'           => 'IT',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'entity'             => 'MSI',
        ];

        $response = $this->postJson('/hr/mpr', $payload);
        $response->assertStatus(403);
    }

    /** @test */
    public function idor_protection_manager_cannot_view_or_export_other_manager_mpr(): void
    {
        $managerA = 'manager.a@mito.id';
        $managerB = 'manager.b@mito.id';

        $this->actingAsRole('Manager', $managerA, 'Manager A', ['MSI'], 'Jakarta');

        $mprB = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0002',
            'requestorName' => 'Manager B',
            'requestorEmail' => $managerB,
            'entity'        => 'MSI',
            'department'    => 'Finance',
            'division'      => 'FAT & GA',
            'position'      => 'Accounting Staff',
            'quantity'      => 1,
            'createdBy'     => $managerB,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('findByMprNumber')
            ->with('MPR-20260824-0002')
            ->andReturn($mprB);

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // Manager A attempting to view Manager B's MPR JSON -> 403 Forbidden
        $responseJson = $this->getJson('/hr/mpr/MPR-20260824-0002/json');
        $responseJson->assertStatus(403);

        // Manager A attempting to export Manager B's MPR PDF -> 403 Forbidden
        $responsePdf = $this->get('/hr/mpr/MPR-20260824-0002/pdf');
        $responsePdf->assertStatus(403);
    }

    /** @test */
    public function hr_manager_can_view_all_mprs_and_create_mpr(): void
    {
        // HR Manager tidak punya entity assignment (bukan Manager role), tapi bisa buat MPR atas nama siapapun
        $this->actingAsRole('HR Manager', 'hrmanager@mito.id', 'HR Manager User', [], '');

        $mockMpr1 = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0001',
            'requestorName' => 'Manager A',
            'requestorEmail' => 'manager.a@mito.id',
            'entity'        => 'MSI',
            'position'      => 'Frontend Developer',
            'employmentType' => 'Contract (PKWT)',
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAll')
            ->andReturn(collect([$mockMpr1]));

        $mockRepo->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (MprData $data) {
                $data->mprNumber = 'MPR-20260824-8888';
                return $data;
            });

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // View index
        $response = $this->get('/hr/mpr');
        $response->assertStatus(200);
        $response->assertSee('Daftar Manpower Request (MPR)');
        $response->assertSee('Buat MPR Baru');

        // Create MPR (HR Manager boleh pilih entity bebas)
        $payload = [
            'position'           => 'HR Recruiter',
            'department'         => 'Human Resources',
            'division'           => 'HR & Legal',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-10',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'manager_name'       => 'HR Manager',
            'manager_email'      => 'hrmanager@mito.id',
            'entity'             => 'MSI',
        ];

        $postResponse = $this->postJson('/hr/mpr', $payload);
        $postResponse->assertStatus(201);
        $postResponse->assertJson([
            'success'    => true,
            'mpr_number' => 'MPR-20260824-8888',
        ]);
    }

    /** @test */
    public function hr_staff_can_view_and_export_mpr_but_cannot_create(): void
    {
        $this->actingAsRole('HR Staff', 'hrstaff@mito.id', 'HR Staff User', [], '');

        $mockMpr = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0001',
            'requestorName' => 'Manager A',
            'requestorEmail' => 'manager.a@mito.id',
            'entity'        => 'MSI',
            'position'      => 'Frontend Developer',
            'employmentType' => 'Contract (PKWT)',
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAll')
            ->andReturn(collect([$mockMpr]));
        $mockRepo->shouldReceive('findByMprNumber')
            ->with('MPR-20260824-0001')
            ->andReturn($mockMpr);

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // HR Staff can view MPR index
        $this->get('/hr/mpr')->assertStatus(200);

        // HR Staff can view detail JSON
        $this->getJson('/hr/mpr/MPR-20260824-0001/json')->assertStatus(200);

        // HR Staff can export PDF
        $this->get('/hr/mpr/MPR-20260824-0001/pdf')->assertStatus(200);

        // HR Staff CANNOT create MPR -> 403 Forbidden
        $payload = [
            'position'           => 'Staff Admin',
            'department'         => 'GA',
            'division'           => 'Operations',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Contract (PKWT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-10',
            'reason'             => 'Penambahan Karyawan Baru',
            'entity'             => 'MSI',
        ];
        $this->post('/hr/mpr', $payload)->assertStatus(403);
    }

    /** @test */
    public function super_admin_has_full_wildcard_access_to_mpr(): void
    {
        $this->actingAsRole('Super Admin', 'admin@mito.id', 'Super Admin', [], '');

        $this->assertTrue(Gate::allows('view_mpr'));
        $this->assertTrue(Gate::allows('create_mpr'));
        $this->assertTrue(Gate::allows('export_mpr'));

        $mockMpr = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-7777',
            'requestorName' => 'Manager X',
            'requestorEmail' => 'manager.x@mito.id',
            'entity'        => 'MSI',
            'department'    => 'Sales',
            'division'      => 'Sales',
            'position'      => 'Sales Executive',
            'quantity'      => 5,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAll')
            ->andReturn(collect([$mockMpr]));
        $mockRepo->shouldReceive('findByMprNumber')
            ->with('MPR-20260824-7777')
            ->andReturn($mockMpr);

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // Super Admin can view
        $this->get('/hr/mpr')->assertStatus(200);

        // Super Admin can view detail
        $this->getJson('/hr/mpr/MPR-20260824-7777/json')->assertStatus(200);

        // Super Admin can export PDF
        $this->get('/hr/mpr/MPR-20260824-7777/pdf')->assertStatus(200);
    }

    /** @test */
    public function validation_rejects_missing_required_fields(): void
    {
        $this->actingAsRole('Manager', null, null, ['MSI'], 'Jakarta');

        $response = $this->postJson('/hr/mpr', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'position',
            'department',
            'division',
            'job_level',
            'work_location',
            'employment_type',
            'quantity',
            'expected_join_date',
            'reason',
            'entity',
        ]);
    }

    /** @test */
    public function mpr_pdf_service_generates_valid_dompdf_document(): void
    {
        $mpr = $this->makeMprData([
            'mprNumber'       => 'MPR-20260824-TEST',
            'requestorName'   => 'Hendra Manager',
            'requestorEmail'  => 'hendra@mito.id',
            'entity'          => 'MSI',
            'branch'          => 'Tangerang',
            'position'        => 'Fullstack Developer',
            'quantity'        => 2,
            'reason'          => 'Penambahan Karyawan Baru (Business Expansion)',
            'jobDescription'  => 'Mengembangkan aplikasi Laravel',
            'requirements'    => 'Pendidikan min S1, Mahir PHP & JS',
            'notes'           => 'Budget sudah diapprove',
            'createdBy'       => 'hendra@mito.id',
        ]);

        $service = app(MprPdfService::class);
        $pdf = $service->generate($mpr);

        $this->assertNotNull($pdf);
        $output = $pdf->output();
        $this->assertNotEmpty($output);
        // PDF header check (%PDF-)
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
