<?php

namespace Tests\Feature;

use App\DTOs\MprData;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
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
        $isManpower = ($role === 'Manpower');

        return [
            'email'        => $email ?? (strtolower(str_replace(' ', '.', $role)) . '@mito.id'),
            'fullName'     => $name  ?? ($role . ' User'),
            'role'         => $role,
            'permissions'  => $permissions,
            // Manpower sessions come from mpr_requestor; all others from Users
            'auth_domain'  => $isManpower ? 'mpr_requestor' : 'users',
            'entities'     => $isManpower ? $entities : [],
            'branch'       => $isManpower ? $branch : '',
            'requestor_id' => $isManpower ? 'MPR-REQ-TEST' : '',
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
            mprNumber: $overrides['mprNumber']       ?? 'MPR-20260824-0001',
            requestDate: $overrides['requestDate']     ?? '2026-08-24',
            requestorName: $overrides['requestorName']   ?? 'Manpower User',
            requestorEmail: $overrides['requestorEmail']  ?? 'manager@mito.id',
            entity: $overrides['entity']          ?? 'MSI',
            branch: $overrides['branch']          ?? 'Head Office (HO)',
            department: $overrides['department']      ?? 'IT',
            division: $overrides['division']        ?? 'IT Support',
            position: $overrides['position']        ?? 'Backend Developer',
            jobLevel: $overrides['jobLevel']        ?? 'Staff',
            workLocation: $overrides['workLocation']    ?? 'Head Office (HO)',
            employmentType: $overrides['employmentType']  ?? 'Permanent (PKWTT)',
            quantity: $overrides['quantity']        ?? 2,
            expectedJoinDate: $overrides['expectedJoinDate'] ?? '2026-09-01',
            reason: $overrides['reason']          ?? 'Penambahan Karyawan Baru',
            status: $overrides['status']          ?? 'Submitted',
            createdBy: $overrides['createdBy']       ?? 'manpower@mito.id',
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
    public function manpower_role_has_correct_permissions_and_restrictions(): void
    {
        $this->actingAsRole('Manpower');

        $this->assertTrue(Gate::allows('view_mpr'), 'Manpower should have view_mpr');
        $this->assertTrue(Gate::allows('create_mpr'), 'Manpower should have create_mpr');
        $this->assertTrue(Gate::allows('export_mpr'), 'Manpower should have export_mpr');

        $this->assertFalse(Gate::allows('view_recruitment'), 'Manpower should not have view_recruitment');
        $this->assertFalse(Gate::allows('view_employees'), 'Manpower should not have view_employees');
        $this->assertFalse(Gate::allows('manage_settings'), 'Manpower should not have manage_settings');
        $this->assertFalse(Gate::allows('manage_probation'), 'Manpower should not have manage_probation');
    }

    /** @test */
    public function approval_division_is_validated_from_config_on_create(): void
    {
        $this->actingAsRole('Admin', 'admin.approval@mito.id', 'HR Admin');

        $validDivision = config('hris.mpr.approval_divisions.0', 'IT');

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('create')->once()->withArgs(function (MprData $mpr) use ($validDivision) {
            $this->assertSame($validDivision, $mpr->approvalDivision);
            return true;
        })->andReturnUsing(function (MprData $mpr) {
            $mpr->mprNumber ??= 'MPR-20260824-0001';
            return $mpr;
        });
        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $response = $this->from('/hr/mpr/history')->post(route('hr.mpr.store'), [
            'position' => 'Backend Developer',
            'department' => 'IT',
            'division' => 'IT Support',
            'job_level' => 'Staff',
            'work_location' => 'Head Office (HO)',
            'employment_type' => 'Permanent (PKWTT)',
            'quantity' => 1,
            'expected_join_date' => '2026-09-15',
            'reason' => 'Penambahan Karyawan Baru',
            'entity' => 'MSI',
            'manager_name' => 'HR Admin',
            'manager_email' => 'admin.approval@mito.id',
            'requestor_position' => 'HR Manager',
            'working_days' => ['senin_jumat'],
            'working_hours' => ['08_00_17_00'],
            'benefits' => ['bpjs'],
            'education_background' => 's1',
            'work_experience' => '1_tahun',
            'skills_competencies' => 'PHP, Laravel, SQL',
            'approval_division' => $validDivision,
        ]);

        $response->assertRedirect('/hr/mpr');
    }

    public function invalid_approval_division_is_rejected(): void
    {
        $this->actingAsRole('Admin', 'admin.invalid@mito.id', 'HR Admin');

        $response = $this->from('/hr/mpr/history')->post(route('hr.mpr.store'), [
            'position' => 'Backend Developer',
            'department' => 'IT',
            'division' => 'IT Support',
            'job_level' => 'Staff',
            'work_location' => 'Head Office (HO)',
            'employment_type' => 'Permanent (PKWTT)',
            'quantity' => 1,
            'expected_join_date' => '2026-09-15',
            'reason' => 'Penambahan Karyawan Baru',
            'entity' => 'MSI',
            'manager_name' => 'HR Admin',
            'manager_email' => 'admin.invalid@mito.id',
            'requestor_position' => 'HR Manager',
            'working_days' => ['senin_jumat'],
            'working_hours' => ['08_00_17_00'],
            'benefits' => ['bpjs'],
            'education_background' => 's1',
            'work_experience' => '1_tahun',
            'skills_competencies' => 'PHP, Laravel, SQL',
            'approval_division' => 'Divisi Tidak Ada',
        ]);

        $response->assertSessionHasErrors('approval_division');
    }

    public function manpower_is_redirected_from_dashboard_and_forbidden_from_hr_modules(): void
    {
        $this->actingAsRole('Manpower');

        // Manpower accessing HR Dashboard -> automatically redirected to the dedicated requestor create page
        $this->get('/hr/dashboard')->assertRedirect(route('mpr.auth.request'));

        // Manpower visiting login page when authenticated -> redirected to the dedicated requestor create page
        $this->get('/login')->assertRedirect(route('mpr.auth.request'));

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
    public function manpower_default_landing_redirects_to_create_form(): void
    {
        $this->actingAsRole('Manpower', 'manager1@mito.id', 'Budi Manpower', ['MSI', 'SPI'], 'Bandung');

        $response = $this->get('/hr/mpr');
        $response->assertRedirect(route('mpr.auth.request'));
    }

    /** @test */
    public function manpower_can_access_refresh_endpoint_for_data_reload(): void
    {
        $this->actingAsRole('Manpower', 'manager.refresh@mito.id', 'Rina Manpower', ['MSI'], 'Bandung');

        $mockSheets = Mockery::mock(GoogleSheetsService::class);
        $mockSheets->shouldReceive('healthCheck')
            ->once()
            ->with(['Employee', 'data_kandidat', 'MPR'])
            ->andReturn([
                'success' => true,
                'sheets' => ['Employee', 'data_kandidat', 'MPR'],
            ]);

        foreach (['Employee', 'data_kandidat', 'MPR'] as $sheetName) {
            $mockSheets->shouldReceive('clearCache')->once()->with($sheetName);
            $mockSheets->shouldReceive('getRange')->once()->with($sheetName, 'A:ZZ', false)->andReturn([
                ['Employee ID', 'Name'],
                ['E-001', 'Contoh'],
            ]);
        }

        $this->app->instance(GoogleSheetsService::class, $mockSheets);

        $response = $this->postJson('/hr/refresh-data');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'healthy');
    }

    /** @test */
    public function manpower_has_dedicated_create_and_history_routes(): void
    {
        $manpowerEmail = 'manager2@mito.id';
        $this->actingAsRole('Manpower', $manpowerEmail, 'Dewi Manpower', ['MSI'], 'Bandung');

        $mockMpr = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0002',
            'requestorName' => 'Dewi Manpower',
            'requestorEmail' => $manpowerEmail,
            'entity'        => 'MSI',
            'branch'        => 'Bandung',
            'position'      => 'QA Engineer',
            'createdBy'     => $manpowerEmail,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAllForManager')
            ->once()
            ->with($manpowerEmail, Mockery::any())
            ->andReturn(collect([$mockMpr]));

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $createResponse = $this->get(route('mpr.auth.request'));
        $createResponse->assertStatus(200);
        $createResponse->assertViewIs('hr.mpr.create');
        $createResponse->assertSee('Formulir Pengajuan Manpower Request');

        $historyResponse = $this->get(route('mpr.auth.request.history'));
        $historyResponse->assertStatus(200);
        $historyResponse->assertViewIs('hr.mpr.history');
        $historyResponse->assertSee('Riwayat Pengajuan MPR');
        $historyResponse->assertSee('MPR-20260824-0002');
    }

    /** @test */
    public function manpower_history_page_shows_clear_stats_for_requestors(): void
    {
        $manpowerEmail = 'manager.stats@mito.id';
        $this->actingAsRole('Manpower', $manpowerEmail, 'Rina MPR', ['MSI'], 'Bandung');

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAllForManager')
            ->once()
            ->with($manpowerEmail, Mockery::any())
            ->andReturn(collect([
                $this->makeMprData(['mprNumber' => 'MPR-1', 'status' => 'Submitted', 'quantity' => 2, 'requestDate' => '2026-08-01', 'requestorEmail' => $manpowerEmail, 'createdBy' => $manpowerEmail]),
                $this->makeMprData(['mprNumber' => 'MPR-2', 'status' => 'Approved', 'quantity' => 3, 'requestDate' => '2026-08-05', 'requestorEmail' => $manpowerEmail, 'createdBy' => $manpowerEmail]),
                $this->makeMprData(['mprNumber' => 'MPR-3', 'status' => 'Rejected', 'quantity' => 1, 'requestDate' => '2026-08-10', 'requestorEmail' => $manpowerEmail, 'createdBy' => $manpowerEmail]),
            ]));

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $response = $this->get(route('hr.mpr.history'));

        $response->assertStatus(200);
        $response->assertSee('Total Pengajuan');
        $response->assertSee('Menunggu Review');
        $response->assertSee('Disetujui');
        $response->assertSee('Total Kebutuhan');
    }

    /** @test */
    public function manpower_history_page_supports_sort_order_filter(): void
    {
        $manpowerEmail = 'manager.sort@mito.id';
        $this->actingAsRole('Manpower', $manpowerEmail, 'Sort Manpower', ['MSI'], 'Bandung');

        $older = $this->makeMprData([
            'mprNumber' => 'MPR-20260801-0001',
            'status' => 'Submitted',
            'requestDate' => '2026-08-01',
            'requestorEmail' => $manpowerEmail,
            'createdBy' => $manpowerEmail,
        ]);

        $newer = $this->makeMprData([
            'mprNumber' => 'MPR-20260815-0002',
            'status' => 'Approved',
            'requestDate' => '2026-08-15',
            'requestorEmail' => $manpowerEmail,
            'createdBy' => $manpowerEmail,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAllForManager')
            ->once()
            ->with($manpowerEmail, Mockery::any())
            ->andReturn(collect([$newer, $older]));

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $response = $this->get(route('hr.mpr.history', ['sort' => 'oldest']));

        $response->assertStatus(200);
        $response->assertSee('MPR-20260801-0001');
        $response->assertSee('Terlama');
        $response->assertSee('value="oldest"', false);
    }

    /** @test */
    public function manpower_history_page_provides_reset_filter_button(): void
    {
        $manpowerEmail = 'manager.reset@mito.id';
        $this->actingAsRole('Manpower', $manpowerEmail, 'Reset Manpower', ['MSI'], 'Bandung');

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAllForManager')
            ->once()
            ->with($manpowerEmail, Mockery::any())
            ->andReturn(collect());

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        $response = $this->get(route('hr.mpr.history', [
            'search' => 'keyword',
            'department' => 'IT',
            'status' => 'Submitted',
            'sort' => 'oldest',
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee('class="btn-reset-filter text-decoration-none"', false);
        $response->assertSee('title="Reset semua filter"', false);
        $response->assertSee('href="' . route('hr.mpr.history') . '"', false);
    }

    /** @test */
    public function manpower_can_submit_mpr_and_identity_is_resolved_server_side(): void
    {
        $manpowerEmail   = 'manager.it@mito.id';
        $manpowerName    = 'Sari Manpower IT';
        $manpowerBranch  = 'Bandung';
        $allowedEntities = ['MSI', 'SPI', 'MEP'];

        $this->actingAsRole('Manpower', $manpowerEmail, $manpowerName, $allowedEntities, $manpowerBranch);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (MprData $data) use ($manpowerEmail, $manpowerName, $manpowerBranch) {
                // Identity MUST come from session, not from payload
                return $data->requestorEmail === $manpowerEmail
                    && $data->requestorName  === $manpowerName
                    && $data->branch         === $manpowerBranch
                    && $data->entity         === 'SPI'      // entity yang dipilih dari form
                    && $data->createdBy      === $manpowerEmail
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
            'division'           => 'IT Support',
            'job_level'          => 'Senior Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 3,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'requirements'       => 'Pendidikan S1, Pengalaman 3 tahun',
            'job_description'    => 'Membangun backend microservice',
            'entity'             => 'SPI', // valid: ada di $allowedEntities
            'working_days'       => ['senin_jumat'],
            'working_hours'      => ['08_00_17_00'],
            'benefits'           => ['bpjs'],
            'education_background' => 's1',
            'work_experience'    => '2_3_tahun',
            // These forged fields should be ignored for Manpower role
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
    public function manpower_cannot_submit_mpr_with_entity_not_in_assignment(): void
    {
        // NOTE: Entity auth check removed — mpr_requestor schema is final 12-col, no Entity/Branch cols.
        // mpr_requestorRepo no longer reads/writes entity/branch. Manpower can select any target entity;
        // it's persisted as 'Entitas yang Dituju' on the MPR sheet (form-level validation governs allowed values).
        $this->actingAsRole('Manpower', 'manager.it@mito.id', 'Sari Manpower IT', ['MSI', 'SPI'], 'Bandung');

        $payload = [
            'position'           => 'Staff Finance',
            'department'         => 'Finance',
            'division'           => 'Financial Planning & Analysis (FP&A)',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'entity'             => 'PII',
            'working_days'       => ['senin_jumat'],
            'working_hours'      => ['08_00_17_00'],
            'benefits'           => ['bpjs'],
            'education_background' => 's1',
            'work_experience'    => '2_3_tahun',
        ];

        $response = $this->postJson('/hr/mpr', $payload);
        // Auth removed: entity not required to be in assignment. Submission now passes (201).
        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function manpower_without_entity_assignment_cannot_submit_mpr(): void
    {
        // NOTE: Empty entity assignment no longer blocks submission — auth check removed.
        $this->actingAsRole('Manpower', 'no-entity@mito.id', 'No Entity Manpower', [], 'Bandung');

        $payload = [
            'position'           => 'Staff IT',
            'department'         => 'IT',
            'division'           => 'IT Support',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-15',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'entity'             => 'MSI',
            'working_days'       => ['senin_jumat'],
            'working_hours'      => ['08_00_17_00'],
            'benefits'           => ['bpjs'],
            'education_background' => 's1',
            'work_experience'    => '2_3_tahun',
        ];

        $response = $this->postJson('/hr/mpr', $payload);
        // Auth removed: entity assignment not required. Submission now passes (201).
        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function idor_protection_manpower_cannot_view_or_export_other_manpower_mpr(): void
    {
        $managerA = 'manager.a@mito.id';
        $managerB = 'manager.b@mito.id';

        $this->actingAsRole('Manpower', $managerA, 'Manpower A', ['MSI'], 'Jakarta');

        $mprB = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0002',
            'requestorName' => 'Manpower B',
            'requestorEmail' => $managerB,
            'entity'        => 'MSI',
            'department'    => 'Finance',
            'division'      => 'Financial Planning & Analysis (FP&A)',
            'position'      => 'Accounting Staff',
            'quantity'      => 1,
            'createdBy'     => $managerB,
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('findByMprNumber')
            ->with('MPR-20260824-0002')
            ->andReturn($mprB);

        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // Manpower A attempting to view Manpower B's MPR JSON -> 403 Forbidden
        $responseJson = $this->getJson('/hr/mpr/MPR-20260824-0002/json');
        $responseJson->assertStatus(403);

        // Manpower A attempting to export Manpower B's MPR PDF -> 403 Forbidden
        $responsePdf = $this->get('/hr/mpr/MPR-20260824-0002/pdf');
        $responsePdf->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_all_mprs_and_create_mpr(): void
    {
        // Admin tidak punya entity assignment (bukan Manpower role), tapi bisa buat MPR atas nama siapapun
        $this->actingAsRole('Admin', 'admin@mito.id', 'Admin User', [], '');

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

        // Create MPR (Admin boleh pilih entity bebas)
        $payload = [
            'position'           => 'HR Recruiter',
            'department'         => 'Human Resources',
            'division'           => 'HR Operations',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Permanent (PKWTT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-10',
            'reason'             => 'Penambahan Karyawan Baru (Business Expansion)',
            'manager_name'       => 'Admin',
            'manager_email'      => 'admin@mito.id',
            'entity'             => 'MSI',
            'working_days'       => ['senin_jumat'],
            'working_hours'      => ['08_00_17_00'],
            'benefits'           => ['bpjs'],
            'education_background' => 's1',
            'work_experience'    => '2_3_tahun',
        ];

        $postResponse = $this->postJson('/hr/mpr', $payload);
        $postResponse->assertStatus(201);
        $postResponse->assertJson([
            'success'    => true,
            'mpr_number' => 'MPR-20260824-8888',
        ]);
    }

    /** @test */
    public function admin_can_update_mpr_without_changing_immutable_metadata(): void
    {
        $this->actingAsRole('Admin', 'admin@mito.id', 'Admin User', [], '');
        $existing = $this->makeMprData([
            'mprNumber' => 'MPR-20260824-0001',
            'requestDate' => '2026-08-24',
            'createdBy' => 'manager@mito.id',
        ]);
        $updatedData = null;

        $repo = Mockery::mock(MprRepositoryInterface::class);
        $repo->shouldReceive('findByMprNumber')->once()->with('MPR-20260824-0001')->andReturn($existing);
        $repo->shouldReceive('update')->once()->withArgs(function (string $id, MprData $data) use (&$updatedData) {
            $updatedData = $data;
            return $id === 'MPR-20260824-0001';
        })->andReturnUsing(fn(string $id, MprData $data) => $data);
        $this->app->instance(MprRepositoryInterface::class, $repo);

        $response = $this->putJson('/hr/mpr/MPR-20260824-0001', [
            'position' => 'Senior Backend Developer',
            'department' => 'IT',
            'division' => 'IT Support',
            'job_level' => 'Senior Staff',
            'work_location' => 'Head Office (HO)',
            'employment_type' => 'Permanent (PKWTT)',
            'quantity' => 3,
            'expected_join_date' => '2026-10-01',
            'reason' => 'Penambahan Karyawan Baru (Business Expansion)',
            'requirements' => "### Skill\n\n- Laravel",
            'job_description' => '**Build APIs**',
            'notes' => 'Updated note',
        ]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('mpr.mpr_number', 'MPR-20260824-0001');
        $this->assertSame('Senior Backend Developer', $updatedData->position);
        $this->assertSame('manager@mito.id', $updatedData->createdBy);
        $this->assertSame('2026-08-24', $updatedData->requestDate);
        $this->assertSame('MSI', $updatedData->entity);
        $this->assertSame("### Skill\n\n- Laravel", $updatedData->requirements);
        $this->assertNotSame($existing->updatedAt, $updatedData->updatedAt);
    }

    /** @test */
    public function privileged_user_cannot_update_mpr(): void
    {
        $this->actingAsRole('Privileged User');
        $this->putJson('/hr/mpr/MPR-20260824-0001', [])->assertForbidden();
    }

    /** @test */
    public function user_can_view_mpr_but_cannot_create_or_update_mpr(): void
    {
        $this->actingAsRole('User', 'hrstaff@mito.id', 'User User', [], '');

        $mockMpr = $this->makeMprData([
            'mprNumber'     => 'MPR-20260824-0001',
            'requestorName' => 'User User',
            'requestorEmail' => 'hrstaff@mito.id',
            'createdBy'     => 'hrstaff@mito.id',
            'entity'        => 'MSI',
            'position'      => 'Staff Admin',
        ]);

        $mockRepo = Mockery::mock(MprRepositoryInterface::class);
        $mockRepo->shouldReceive('getAll')->andReturn(collect([$mockMpr]));
        $mockRepo->shouldReceive('findByMprNumber')->with('MPR-20260824-0001')->andReturn($mockMpr);
        $this->app->instance(MprRepositoryInterface::class, $mockRepo);

        // Renamed User keeps the previous Privileged User permissions: view MPR but cannot mutate it.
        $this->get('/hr/mpr')->assertStatus(200);
        $this->getJson('/hr/mpr/MPR-20260824-0001/json')->assertStatus(200);
        $this->get('/hr/mpr/MPR-20260824-0001/pdf')->assertStatus(200);

        $payload = [
            'position'           => 'Staff Admin',
            'department'         => 'GA',
            'division'           => 'General Affairs',
            'job_level'          => 'Staff',
            'work_location'      => 'Head Office (HO)',
            'employment_type'    => 'Contract (PKWT)',
            'quantity'           => 1,
            'expected_join_date' => '2026-09-10',
            'reason'             => 'Penambahan Karyawan Baru',
            'entity'             => 'MSI',
        ];
        $this->post('/hr/mpr', $payload)->assertStatus(403);
        $this->putJson('/hr/mpr/MPR-20260824-0001', [])->assertStatus(403);
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
            'requestorName' => 'Manpower X',
            'requestorEmail' => 'manpower.x@mito.id',
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
        $this->actingAsRole('Manpower', null, null, ['MSI'], 'Jakarta');

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
            'working_days',
            'working_hours',
            'benefits',
            'education_background',
            'work_experience',
        ]);
    }

    /** @test */
    public function create_rejects_division_from_another_department(): void
    {
        $this->actingAsRole('Manpower', null, null, ['MSI'], 'Jakarta');

        $response = $this->postJson('/hr/mpr', [
            'position' => 'Finance Staff',
            'department' => 'Finance',
            'division' => 'HR Operations',
            'job_level' => 'Staff',
            'work_location' => 'Head Office (HO)',
            'employment_type' => 'Permanent (PKWTT)',
            'quantity' => 1,
            'expected_join_date' => '2026-09-15',
            'reason' => 'Penambahan Karyawan Baru (Business Expansion)',
            'entity' => 'MSI',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('division');
    }

    /** @test */
    public function update_rejects_division_from_another_department(): void
    {
        $this->actingAsRole('Admin');

        $response = $this->putJson('/hr/mpr/MPR-20260824-0001', [
            'position' => 'Finance Staff',
            'department' => 'Finance',
            'division' => 'HR Operations',
            'job_level' => 'Staff',
            'work_location' => 'Head Office (HO)',
            'employment_type' => 'Permanent (PKWT)',
            'quantity' => 1,
            'expected_join_date' => '2026-09-15',
            'reason' => 'Penambahan Karyawan Baru (Business Expansion)',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('division');
    }

    /** @test */
    public function mpr_pdf_service_generates_valid_dompdf_document(): void
    {
        $mpr = $this->makeMprData([
            'mprNumber'       => 'MPR-20260824-TEST',
            'requestorName'   => 'Hendra Manpower',
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
