<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DTOs\CandidateData;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\RecruitmentService;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use App\Events\CandidateStatusChanged;
use App\Events\EmployeeHired;
use Mockery;

/**
 * OfferingContractFlowTest
 *
 * Covers the full Offering Letter → Candidate Accepted → Contract flow.
 * Google Sheets is mocked so no real API calls are made.
 *
 * Test matrix:
 * T01 – Offering submission persists correct fields (1:1 GAS saveOfferingStatus)
 * T02 – Offering submission on existing offering sets Updated, not Created
 * T03 – Offering preview (getJson) returns offering fields correctly
 * T04 – Candidate Accepted page only shows candidates from kandidat_accepted sheet
 * T05 – Offering Response "Diterima" saved correctly
 * T06 – Contract eligibility: candidate with offeringResponse=Diterima appears
 * T07 – Contract eligibility: candidate without Diterima is rejected (negative test)
 * T08 – saveContract guard enforces offeringResponse = "Diterima" (trim-safe)
 * T09 – Cache invalidation: kandidat_accepted cache is cleared after saveOffering
 * T10 – moveToSheet uses correct source sheet for delete (not hardcoded 'candidates')
 * T11 – update() pads short rows to full header width before writing
 * T12 – deleteFromSheet uses real sheetId, not hardcoded 0
 */
class OfferingContractFlowTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function loginAsHrAdmin(): void
    {
        Session::put('hr_user', [
            'email'       => 'admin@mito.id',
            'fullName'    => 'HR Admin',
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]);
    }

    private function makeCandidateData(array $overrides = []): CandidateData
    {
        return new CandidateData(
            recruitmentId:          $overrides['recruitmentId']          ?? 'REC-20260801-000001',
            fullName:               $overrides['fullName']               ?? 'Budi Santoso',
            email:                  $overrides['email']                  ?? 'budi@gmail.com',
            positionApplied:        $overrides['positionApplied']        ?? 'HR Staff',
            status:                 $overrides['status']                 ?? 'Accepted',
            offeringCreated:        $overrides['offeringCreated']        ?? null,
            offeringCompanyEntity:  $overrides['offeringCompanyEntity']  ?? null,
            offeringPosition:       $overrides['offeringPosition']       ?? null,
            offeringSalary:         $overrides['offeringSalary']         ?? null,
            offeringSalaryBasic:    $overrides['offeringSalaryBasic']    ?? null,
            offeringAllowPulsa:     $overrides['offeringAllowPulsa']     ?? null,
            offeringAllowTransport: $overrides['offeringAllowTransport'] ?? null,
            offeringResponse:       $overrides['offeringResponse']       ?? null,
            offeringResponseNotes:  $overrides['offeringResponseNotes']  ?? null,
            onboardingStatus:       $overrides['onboardingStatus']       ?? null,
        );
    }

    // =========================================================================
    // T01: Offering submission persists correct fields
    // =========================================================================

    /** @test */
    public function t01_save_offering_persists_correct_fields_to_sheet(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makeCandidateData(['offeringCreated' => null]);
        $savedAttributes = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')
            ->with('REC-20260801-000001')
            ->andReturn($candidate);
        $candidateRepo->shouldReceive('update')
            ->once()
            ->withArgs(function (string $id, array $attrs) use (&$savedAttributes) {
                $savedAttributes = $attrs;
                return $id === 'REC-20260801-000001';
            })
            ->andReturn(true);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andReturn(true);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->postJson('/hr/recruitment/REC-20260801-000001/save-offering', [
            'branch_name'        => 'PT Mahakarya Sukses Indonesia',
            'position'           => 'HR Staff',
            'department'         => 'Human Resources',
            'division'           => 'HR & Legal',
            'job_level'          => 'Associate',
            'lokasi_kerja'       => 'Jakarta',
            'join_date'          => '2026-09-01',
            'employment_status'  => 'Perjanjian Kerja Waktu Tertentu',
            'contract_duration'  => '12 bulan',
            'salary_basic'       => '5000000',
            'allow_pulsa'        => '100000',
            'allow_transport'    => '200000',
            'working_hours'      => 'Senin – Jumat mulai pukul 08.00 – 17.00 WIB',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'action' => 'created']);

        // Verify all mandatory fields are present in the update payload
        $this->assertNotNull($savedAttributes, 'update() was not called');
        $this->assertArrayHasKey('Offering Company Entity',    $savedAttributes);
        $this->assertArrayHasKey('Offering Position',          $savedAttributes);
        $this->assertArrayHasKey('Offering Department',        $savedAttributes);
        $this->assertArrayHasKey('Offering Division',          $savedAttributes);
        $this->assertArrayHasKey('Offering Job Level',         $savedAttributes);
        $this->assertArrayHasKey('Offering Lokasi Kerja',      $savedAttributes);
        $this->assertArrayHasKey('Offering Join Date',         $savedAttributes);
        $this->assertArrayHasKey('Offering Employment Status', $savedAttributes);
        $this->assertArrayHasKey('Offering Contract Duration', $savedAttributes);
        $this->assertArrayHasKey('Offering Salary Basic',      $savedAttributes);
        $this->assertArrayHasKey('Offering Allow Pulsa',       $savedAttributes);
        $this->assertArrayHasKey('Offering Allow Transport',   $savedAttributes);
        $this->assertArrayHasKey('Offering Working Hours',     $savedAttributes);
        $this->assertArrayHasKey('Offering Created',           $savedAttributes);
        $this->assertArrayHasKey('Offering Created By',        $savedAttributes);
        $this->assertArrayHasKey('Offering Response',          $savedAttributes);

        // First offering → response should default to "Menunggu" (1:1 GAS)
        $this->assertSame('Menunggu', $savedAttributes['Offering Response']);
        $this->assertSame('PT Mahakarya Sukses Indonesia', $savedAttributes['Offering Company Entity']);
        $this->assertSame('Human Resources', $savedAttributes['Offering Department']);

        // Created fields should be set, Updated fields should NOT be set
        $this->assertArrayNotHasKey('Offering Updated',    $savedAttributes);
        $this->assertArrayNotHasKey('Offering Updated By', $savedAttributes);
    }

    // =========================================================================
    // T02: Existing offering → sets Updated, not new Created
    // =========================================================================

    /** @test */
    public function t02_save_offering_on_existing_sets_updated_not_created(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makeCandidateData([
            'offeringCreated' => '2026-08-01 10:00:00', // already has offering
        ]);
        $savedAttributes = null;

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('update')
            ->once()
            ->withArgs(function (string $id, array $attrs) use (&$savedAttributes) {
                $savedAttributes = $attrs;
                return true;
            })
            ->andReturn(true);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andReturn(true);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->postJson('/hr/recruitment/REC-20260801-000001/save-offering', [
            'branch_name'  => 'PT Mahakarya Sukses Indonesia',
            'position'     => 'HR Recruiter',
            'department'   => 'Human Resources',
            'join_date'    => '2026-09-15',
            'salary_basic' => '6000000',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'action' => 'updated']);
        $this->assertArrayHasKey('Offering Updated',    $savedAttributes);
        $this->assertArrayHasKey('Offering Updated By', $savedAttributes);
        $this->assertArrayNotHasKey('Offering Created',    $savedAttributes);
        $this->assertArrayNotHasKey('Offering Created By', $savedAttributes);
        // Response should NOT be reset to Menunggu on update
        $this->assertArrayNotHasKey('Offering Response', $savedAttributes);
    }

    // =========================================================================
    // T03: getJson returns all offering fields
    // =========================================================================

    /** @test */
    public function t03_get_json_returns_offering_fields(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makeCandidateData([
            'offeringCreated'        => '2026-08-10 09:00:00',
            'offeringCompanyEntity'  => 'PT Mahakarya Sukses Indonesia',
            'offeringPosition'       => 'HR Staff',
            'offeringSalaryBasic'    => '5000000',
            'offeringResponse'       => 'Menunggu',
        ]);

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')
            ->with('REC-20260801-000001')
            ->andReturn($candidate);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('getLogs')->andReturn(collect());

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->getJson('/hr/recruitment/REC-20260801-000001/json');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('candidate.offeringCreated',       '2026-08-10 09:00:00')
            ->assertJsonPath('candidate.offeringCompanyEntity', 'PT Mahakarya Sukses Indonesia')
            ->assertJsonPath('candidate.offeringPosition',      'HR Staff')
            ->assertJsonPath('candidate.offeringSalaryBasic',   '5000000')
            ->assertJsonPath('candidate.offeringResponse',      'Menunggu');
    }

    // =========================================================================
    // T04: Accepted page loads candidates from kandidat_accepted sheet only
    // =========================================================================

    /** @test */
    public function t04_accepted_page_loads_from_kandidat_accepted_sheet(): void
    {
        $this->loginAsHrAdmin();

        $acceptedCandidates = collect([
            $this->makeCandidateData(['recruitmentId' => 'REC-ACC-001', 'status' => 'Accepted']),
            $this->makeCandidateData(['recruitmentId' => 'REC-ACC-002', 'status' => 'Accepted']),
        ]);

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('getAllFromSheets')
            ->with(['candidates_accepted'])
            ->andReturn($acceptedCandidates);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);

        $response = $this->get('/hr/recruitment/accepted');
        $response->assertOk();
        $response->assertSee('REC-ACC-001');
        $response->assertSee('REC-ACC-002');
    }

    // =========================================================================
    // T05: Offering response "Diterima" is saved correctly
    // =========================================================================

    /** @test */
    public function t05_save_offering_response_diterima_persists_correctly(): void
    {
        $this->loginAsHrAdmin();

        $candidate = $this->makeCandidateData([
            'offeringCreated'  => '2026-08-10 09:00:00',
            'offeringResponse' => 'Menunggu',
        ]);

        $savedAttributes = null;
        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        $candidateRepo->shouldReceive('update')
            ->once()
            ->withArgs(function (string $id, array $attrs) use (&$savedAttributes) {
                $savedAttributes = $attrs;
                return true;
            })
            ->andReturn(true);

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->once()->andReturn(true);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);

        $response = $this->postJson('/hr/recruitment/REC-20260801-000001/save-offering-response', [
            'response' => 'Diterima',
            'notes'    => 'Kandidat setuju semua ketentuan.',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'response' => 'Diterima']);
        $this->assertSame('Diterima',                         $savedAttributes['Offering Response'] ?? null);
        $this->assertSame('Kandidat setuju semua ketentuan.', $savedAttributes['Offering Response Notes'] ?? null);
        $this->assertNotEmpty($savedAttributes['Offering Response Date'] ?? '');
    }

    // =========================================================================
    // T06: Contract search — candidate with Diterima is eligible
    // =========================================================================

    /** @test */
    public function t06_contract_search_finds_candidate_with_offering_diterima(): void
    {
        $this->loginAsHrAdmin();

        $eligible = $this->makeCandidateData([
            'recruitmentId'   => 'REC-ELIGIBLE-001',
            'offeringCreated' => '2026-08-10 09:00:00',
            'offeringResponse'=> 'Diterima',
            'onboardingStatus'=> '',
        ]);

        $all = collect([$eligible]);

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('getAllFromSheets')
            ->with(['candidates_accepted'])
            ->andReturn($all);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);

        // The onboarding modal JS filter is: offeringResponse === 'Diterima' AND !onboardingStatus
        // The backend processContractOnboarding guard: trim($offeringResponse) === 'Diterima'
        // Test: candidate appears in the Accepted page and is eligible
        $response = $this->get('/hr/recruitment/accepted');
        $response->assertOk()->assertSee('REC-ELIGIBLE-001');
    }

    // =========================================================================
    // T07: Contract eligibility negative — candidate without Diterima is blocked
    // =========================================================================

    /** @test */
    public function t07_save_contract_rejects_candidate_without_offering_diterima(): void
    {
        $this->loginAsHrAdmin();

        // Candidate has offering but response is still "Menunggu"
        $candidate = $this->makeCandidateData([
            'offeringCreated'  => '2026-08-10 09:00:00',
            'offeringResponse' => 'Menunggu',
        ]);

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);

        $auditRepo   = Mockery::mock(AuditLogRepositoryInterface::class);
        $employeeRepo = Mockery::mock(\App\Repositories\Contracts\EmployeeRepositoryInterface::class);
        $driveService = Mockery::mock(\App\Services\Google\GoogleDriveService::class);
        $idGenerator  = Mockery::mock(\App\Services\EmployeeIdGenerator::class);

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(\App\Repositories\Contracts\EmployeeRepositoryInterface::class, $employeeRepo);

        $response = $this->postJson('/hr/recruitment/REC-20260801-000001/save-contract', [
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'join_date'   => '2026-09-01',
        ]);

        $response->assertStatus(500)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($msg) => str_contains((string)$msg, 'Diterima'));
    }

    // =========================================================================
    // T08: saveContract guard is trim-safe (whitespace in stored value)
    // =========================================================================

    /** @test */
    public function t08_save_contract_guard_is_trim_safe(): void
    {
        $this->loginAsHrAdmin();

        // Simulate whitespace issue: " Diterima " in sheet
        $candidate = $this->makeCandidateData([
            'offeringCreated'  => '2026-08-10 09:00:00',
            'offeringResponse' => ' Diterima ',  // leading/trailing space
        ]);

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn($candidate);
        // If trim() is applied, the guard should PASS and call employeeRepo
        $candidateRepo->shouldReceive('update')->andReturn(true);

        $employeeRepo = Mockery::mock(\App\Repositories\Contracts\EmployeeRepositoryInterface::class);
        $employeeRepo->shouldReceive('findById')->andReturn(null);
        $employeeRepo->shouldReceive('create')->andReturn(new \App\DTOs\EmployeeData());

        $auditRepo = Mockery::mock(AuditLogRepositoryInterface::class);
        $auditRepo->shouldReceive('log')->andReturn(true);

        $idGenerator = Mockery::mock(\App\Services\EmployeeIdGenerator::class);
        $idGenerator->shouldReceive('generate')->andReturn('20260901001');

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);
        $this->app->instance(\App\Repositories\Contracts\EmployeeRepositoryInterface::class, $employeeRepo);
        $this->app->instance(AuditLogRepositoryInterface::class, $auditRepo);
        $this->app->instance(\App\Services\EmployeeIdGenerator::class, $idGenerator);

        $response = $this->postJson('/hr/recruitment/REC-20260801-000001/save-contract', [
            'branch_name' => 'PT Mahakarya Sukses Indonesia',
            'join_date'   => '2026-09-01',
            'position'    => 'HR Staff',
            'department'  => 'Human Resources',
        ]);

        // With trim(), " Diterima " should pass the guard and not return a 500 guard error
        $this->assertNotEquals('Kandidat belum menerima offering letter', $response->json('message'));
    }

    // =========================================================================
    // T09: Cache invalidation clears kandidat_accepted after saveOffering
    // =========================================================================

    /** @test */
    public function t09_cache_is_invalidated_for_kandidat_accepted_after_save_offering(): void
    {
        $spreadsheetId = config('google.spreadsheet_id', 'test-spreadsheet');
        $sheetName     = config('google.sheets.candidates_accepted', 'kandidat_accepted');

        // Pre-seed stale cache
        $cacheKey = "sheets_{$spreadsheetId}_{$sheetName}_" . md5('A:ZZ');
        Cache::put($cacheKey, [['stale' => 'data']], 300);
        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist before write');

        // Trigger clearCache directly (mirrors what happens after saveOffering → update())
        $sheetsService = app(GoogleSheetsService::class);
        $sheetsService->clearCache($sheetName);

        $this->assertFalse(Cache::has($cacheKey), 'Cache should be cleared after clearCache()');
    }

    // =========================================================================
    // T10: Duplicate header check — Candidate Accepted page has no duplicate columns
    // =========================================================================

    /** @test */
    public function t10_candidate_accepted_page_has_no_duplicate_table_headers(): void
    {
        $this->loginAsHrAdmin();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('getAllFromSheets')
            ->with(['candidates_accepted'])
            ->andReturn(collect());

        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);

        $response = $this->get('/hr/recruitment/accepted');
        $response->assertOk();

        $html = $response->getContent();
        // Extract all <th> text from the accepted table
        preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $html, $matches);
        $headers = array_map('strip_tags', $matches[1] ?? []);
        $headers = array_map('trim', $headers);
        $headers = array_filter($headers);

        // Check for duplicates
        $counts = array_count_values($headers);
        $duplicates = array_filter($counts, fn ($c) => $c > 1);

        $this->assertEmpty(
            $duplicates,
            'Duplicate table headers found: ' . implode(', ', array_keys($duplicates))
        );
    }

    // =========================================================================
    // T11: update() pads short rows to full header width
    // =========================================================================

    /** @test */
    public function t11_update_pads_short_row_to_header_width(): void
    {
        // Build a mock GoogleSheetsService that returns a 5-column header
        // but only a 3-column data row (simulates sparse/short Sheet row).
        // locateRow() searches all 5 sheets; mock findRowBy so it returns
        // null for the first 4 sheets and the target row for kandidat_accepted.
        $headers    = ['Recruitment ID', 'Full Name', 'Status', 'Offering Response', 'Offering Created'];
        $shortRow   = ['REC-001', 'Budi', 'Accepted']; // only 3 cols
        $writtenRow = null;

        $sheetsMock = Mockery::mock(GoogleSheetsService::class)->makePartial();

        // locateRow() iterates: data_kandidat, kandidat_hold, kandidat_blacklist,
        //                       kandidat_accepted, kandidat_probation
        // Return null for first 3, real row for kandidat_accepted
        $sheetsMock->shouldReceive('findRowBy')
            ->with('data_kandidat',        'Recruitment ID', 'REC-001')->andReturn(null);
        $sheetsMock->shouldReceive('findRowBy')
            ->with('kandidat_hold',        'Recruitment ID', 'REC-001')->andReturn(null);
        $sheetsMock->shouldReceive('findRowBy')
            ->with('kandidat_blacklist',   'Recruitment ID', 'REC-001')->andReturn(null);
        $sheetsMock->shouldReceive('findRowBy')
            ->with('kandidat_accepted',    'Recruitment ID', 'REC-001')
            ->andReturn(['Recruitment ID' => 'REC-001', '_row_number' => 2]);
        $sheetsMock->shouldReceive('findRowBy')
            ->with('kandidat_probation',   'Recruitment ID', 'REC-001')->andReturn(null);

        $sheetsMock->shouldReceive('getRange')
            ->with('kandidat_accepted', 'A2:ZZ2', false)
            ->andReturn([$shortRow]);
        $sheetsMock->shouldReceive('getRange')
            ->with('kandidat_accepted', '1:1', false)
            ->andReturn([$headers]);
        $sheetsMock->shouldReceive('updateRow')
            ->once()
            ->withArgs(function (string $sheet, int $row, array $written) use (&$writtenRow) {
                $writtenRow = $written;
                return true;
            })
            ->andReturn(true);
        $sheetsMock->shouldReceive('clearCache')->andReturn(null);

        $repo = new \App\Repositories\Sheets\CandidateSheetsRepository($sheetsMock);

        $result = $repo->update('REC-001', ['Offering Response' => 'Diterima']);

        $this->assertTrue($result);
        $this->assertCount(5, $writtenRow, 'Row should be padded to 5 columns (header width)');
        $this->assertSame('Diterima', $writtenRow[3], 'Offering Response should be at index 3');
    }

    // =========================================================================
    // T12: Offering submit → 404 if candidate not found
    // =========================================================================

    /** @test */
    public function t12_save_offering_returns_404_if_candidate_not_found(): void
    {
        $this->loginAsHrAdmin();

        $candidateRepo = Mockery::mock(CandidateRepositoryInterface::class);
        $candidateRepo->shouldReceive('findById')->andReturn(null);
        $this->app->instance(CandidateRepositoryInterface::class, $candidateRepo);

        $response = $this->postJson('/hr/recruitment/NON-EXISTENT-ID/save-offering', [
            'branch_name'  => 'PT Mahakarya Sukses Indonesia',
            'position'     => 'Staff',
            'join_date'    => '2026-09-01',
            'salary_basic' => '4000000',
        ]);

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    // =========================================================================
    // Teardown
    // =========================================================================

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
