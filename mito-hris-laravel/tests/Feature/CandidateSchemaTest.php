<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\DTOs\CandidateData;

/**
 * CandidateSchemaTest
 *
 * Verifies the exact final schema for all four candidate Google Sheets.
 * Tests enforce:
 *   1. Exact header arrays (order + count + field names)   — SCHEMA_*
 *   2. Row length matches header count                     — ROWLEN_*
 *   3. Key field header/row index alignment                — ALIGN_*
 *
 * Any deviation here means a column shift is occurring in production.
 *
 * Final schemas (per spec):
 *
 *  data_kandidat      — 24 cols. No CV Link. No pipeline cols.
 *  kandidat_hold      — 31 cols. No CV Link. No Employee ID. Has Hold/Blacklist + Processed.
 *  kandidat_blacklist — 31 cols. No CV Link. No Employee ID. Has Hold/Blacklist + Processed.
 *  kandidat_accepted  — 57 cols. No CV Link. HAS Employee ID. All Offering/Onboarding fields.
 */
class CandidateSchemaTest extends TestCase
{
    // =========================================================================
    // Expected header arrays — single source of truth for these tests
    // =========================================================================

    /** @return string[] */
    private function dataKandidatHeaders(): array
    {
        return [
            'Recruitment ID',
            'Created Date',
            'Full Name',
            'NIK',
            'Birth Date',
            'Age',
            'Gender',
            'Marital Status',
            'Email',
            'Phone',
            'Address',
            'City',
            'Position Applied',
            'Education',
            'Work Experience',
            'Last Company',
            'Current Employment Status',
            'Available to Join',
            'Expected Salary',
            'Recruitment Source',
            'Status',
            'HR Notes',
            'Created By',
            'Updated At',
        ];
    }

    /** @return string[] */
    private function kandidatHoldHeaders(): array
    {
        return [
            'Recruitment ID',
            'Created Date',
            'Full Name',
            'NIK',
            'Birth Date',
            'Age',
            'Gender',
            'Marital Status',
            'Email',
            'Phone',
            'Address',
            'City',
            'Position Applied',
            'Education',
            'Work Experience',
            'Last Company',
            'Current Employment Status',
            'Available to Join',
            'Expected Salary',
            'Recruitment Source',
            'Status',
            'HR Notes',
            'Created By',
            'Updated At',
            'Hold Reason',
            'Hold Follow Up Date',
            'Blacklist Reason',
            'Blacklist Date',
            'Blacklist Updated By',
            'Processed Date',
            'Processed By',
        ];
    }

    /** @return string[] */
    private function kandidatBlacklistHeaders(): array
    {
        // Identical structure to kandidat_hold
        return $this->kandidatHoldHeaders();
    }

    /** @return string[] */
    private function kandidatAcceptedHeaders(): array
    {
        return [
            'Recruitment ID',
            'Created Date',
            'Full Name',
            'NIK',
            'Birth Date',
            'Age',
            'Gender',
            'Marital Status',
            'Email',
            'Phone',
            'Address',
            'City',
            'Position Applied',
            'Education',
            'Work Experience',
            'Last Company',
            'Current Employment Status',
            'Available to Join',
            'Expected Salary',
            'Recruitment Source',
            'Status',
            'HR Notes',
            'Created By',
            'Updated At',
            'Hold Reason',
            'Hold Follow Up Date',
            'Blacklist Reason',
            'Blacklist Date',
            'Blacklist Updated By',
            'Employee ID',
            'Processed Date',
            'Processed By',
            'Offering Created',
            'Offering Updated',
            'Offering Created By',
            'Offering Updated By',
            'Offering Company Entity',
            'Offering Position',
            'Offering Salary',
            'Offering Join Date',
            'Offering Notes',
            'Offering Response',
            'Offering Response Notes',
            'Offering Response Date',
            'Offering Response By',
            'Onboarding Status',
            'Onboarding Date',
            'Onboarding By',
            'Offering Division',
            'Offering Job Level',
            'Offering Lokasi Kerja',
            'Offering Salary Basic',
            'Offering Allow Pulsa',
            'Offering Allow Transport',
            'Offering Employment Status',
            'Offering Contract Duration',
            'Offering Working Hours',
        ];
    }

    // =========================================================================
    // Helper: build a canonical CandidateData for row-building tests
    // =========================================================================

    private function makeCandidate(array $overrides = []): CandidateData
    {
        return new CandidateData(
            recruitmentId:           $overrides['recruitmentId']           ?? 'REC-SCHEMA-TEST-001',
            createdDate:             $overrides['createdDate']             ?? '2026-09-02 10:00:00',
            fullName:                $overrides['fullName']                ?? 'Andi Wijaya',
            nik:                     $overrides['nik']                     ?? '3201010101900001',
            birthDate:               $overrides['birthDate']               ?? '1990-01-01',
            age:                     $overrides['age']                     ?? '36',
            gender:                  $overrides['gender']                  ?? 'Laki-laki',
            maritalStatus:           $overrides['maritalStatus']           ?? 'Belum Menikah',
            email:                   $overrides['email']                   ?? 'andi@example.com',
            phone:                   $overrides['phone']                   ?? '+6281234567890',
            address:                 $overrides['address']                 ?? 'Jl. Merdeka No. 1',
            city:                    $overrides['city']                    ?? 'Jakarta',
            positionApplied:         $overrides['positionApplied']         ?? 'HR Staff',
            education:               $overrides['education']               ?? 'S1',
            workExperience:          $overrides['workExperience']          ?? '2 Tahun',
            lastCompany:             $overrides['lastCompany']             ?? 'PT Contoh',
            currentEmploymentStatus: $overrides['currentEmploymentStatus'] ?? 'Employed',
            availableToJoin:         $overrides['availableToJoin']         ?? '1 Bulan',
            expectedSalary:          $overrides['expectedSalary']          ?? '8000000',
            recruitmentSource:       $overrides['recruitmentSource']       ?? 'LinkedIn',
            status:                  $overrides['status']                  ?? 'Pending',
            hrNotes:                 $overrides['hrNotes']                 ?? '',
            createdBy:               $overrides['createdBy']               ?? 'Candidate',
            updatedAt:               $overrides['updatedAt']               ?? '2026-09-02 10:00:00',
        );
    }

    // =========================================================================
    // SCHEMA_01 — data_kandidat: exact header array
    // =========================================================================

    public function test_schema01_data_kandidat_exact_headers(): void
    {
        $configured = config('hris.schemas.data_kandidat');
        $expected   = $this->dataKandidatHeaders();

        $this->assertSame($expected, $configured,
            'data_kandidat headers in config/hris.php do not match the final schema');
    }

    // =========================================================================
    // SCHEMA_02 — data_kandidat: no CV Link, no pipeline cols
    // =========================================================================

    public function test_schema02_data_kandidat_excludes_removed_fields(): void
    {
        $headers = config('hris.schemas.data_kandidat');

        $this->assertNotContains('CV Link',              $headers, 'data_kandidat must NOT contain CV Link');
        $this->assertNotContains('Hold Reason',          $headers, 'data_kandidat must NOT contain Hold Reason');
        $this->assertNotContains('Hold Follow Up Date',  $headers, 'data_kandidat must NOT contain Hold Follow Up Date');
        $this->assertNotContains('Blacklist Reason',     $headers, 'data_kandidat must NOT contain Blacklist Reason');
        $this->assertNotContains('Blacklist Date',       $headers, 'data_kandidat must NOT contain Blacklist Date');
        $this->assertNotContains('Blacklist Updated By', $headers, 'data_kandidat must NOT contain Blacklist Updated By');
        $this->assertNotContains('Employee ID',          $headers, 'data_kandidat must NOT contain Employee ID');
    }

    // =========================================================================
    // SCHEMA_03 — kandidat_hold: exact header array
    // =========================================================================

    public function test_schema03_kandidat_hold_exact_headers(): void
    {
        $configured = config('hris.schemas.kandidat_hold');
        $expected   = $this->kandidatHoldHeaders();

        $this->assertSame($expected, $configured,
            'kandidat_hold headers in config/hris.php do not match the final schema');
    }

    // =========================================================================
    // SCHEMA_04 — kandidat_hold: no CV Link, no Employee ID, keeps Hold/Blacklist cols
    // =========================================================================

    public function test_schema04_kandidat_hold_excludes_cv_and_employee_keeps_hold_cols(): void
    {
        $headers = config('hris.schemas.kandidat_hold');

        $this->assertNotContains('CV Link',    $headers, 'kandidat_hold must NOT contain CV Link');
        $this->assertNotContains('Employee ID', $headers, 'kandidat_hold must NOT contain Employee ID');

        $this->assertContains('Hold Reason',          $headers, 'kandidat_hold MUST contain Hold Reason');
        $this->assertContains('Hold Follow Up Date',  $headers, 'kandidat_hold MUST contain Hold Follow Up Date');
        $this->assertContains('Blacklist Reason',     $headers, 'kandidat_hold MUST contain Blacklist Reason');
        $this->assertContains('Blacklist Date',       $headers, 'kandidat_hold MUST contain Blacklist Date');
        $this->assertContains('Blacklist Updated By', $headers, 'kandidat_hold MUST contain Blacklist Updated By');
        $this->assertContains('Processed Date',       $headers, 'kandidat_hold MUST contain Processed Date');
        $this->assertContains('Processed By',         $headers, 'kandidat_hold MUST contain Processed By');
    }

    // =========================================================================
    // SCHEMA_05 — kandidat_blacklist: exact header array
    // =========================================================================

    public function test_schema05_kandidat_blacklist_exact_headers(): void
    {
        $configured = config('hris.schemas.kandidat_blacklist');
        $expected   = $this->kandidatBlacklistHeaders();

        $this->assertSame($expected, $configured,
            'kandidat_blacklist headers in config/hris.php do not match the final schema');
    }

    // =========================================================================
    // SCHEMA_06 — kandidat_blacklist: no CV Link, no Employee ID
    // =========================================================================

    public function test_schema06_kandidat_blacklist_excludes_cv_and_employee(): void
    {
        $headers = config('hris.schemas.kandidat_blacklist');

        $this->assertNotContains('CV Link',    $headers, 'kandidat_blacklist must NOT contain CV Link');
        $this->assertNotContains('Employee ID', $headers, 'kandidat_blacklist must NOT contain Employee ID');

        $this->assertContains('Blacklist Reason',     $headers, 'kandidat_blacklist MUST contain Blacklist Reason');
        $this->assertContains('Blacklist Date',       $headers, 'kandidat_blacklist MUST contain Blacklist Date');
        $this->assertContains('Blacklist Updated By', $headers, 'kandidat_blacklist MUST contain Blacklist Updated By');
        $this->assertContains('Processed Date',       $headers, 'kandidat_blacklist MUST contain Processed Date');
        $this->assertContains('Processed By',         $headers, 'kandidat_blacklist MUST contain Processed By');
    }

    // =========================================================================
    // SCHEMA_07 — kandidat_accepted: exact header array
    // =========================================================================

    public function test_schema07_kandidat_accepted_exact_headers(): void
    {
        $configured = config('hris.schemas.kandidat_accepted');
        $expected   = $this->kandidatAcceptedHeaders();

        $this->assertSame($expected, $configured,
            'kandidat_accepted headers in config/hris.php do not match the final schema');
    }

    // =========================================================================
    // SCHEMA_08 — kandidat_accepted: no CV Link, has Employee ID + all critical fields
    // =========================================================================

    public function test_schema08_kandidat_accepted_excludes_cv_keeps_employee_id_and_offering(): void
    {
        $headers = config('hris.schemas.kandidat_accepted');

        $this->assertNotContains('CV Link', $headers, 'kandidat_accepted must NOT contain CV Link');

        // Critical fields that MUST remain
        foreach ([
            'Employee ID',
            'Hold Reason',
            'Hold Follow Up Date',
            'Blacklist Reason',
            'Blacklist Date',
            'Blacklist Updated By',
            'Processed Date',
            'Processed By',
            'Offering Created',
            'Offering Updated',
            'Offering Created By',
            'Offering Updated By',
            'Offering Company Entity',
            'Offering Position',
            'Offering Salary',
            'Offering Join Date',
            'Offering Notes',
            'Offering Response',
            'Offering Response Notes',
            'Offering Response Date',
            'Offering Response By',
            'Onboarding Status',
            'Onboarding Date',
            'Onboarding By',
            'Offering Division',
            'Offering Job Level',
            'Offering Lokasi Kerja',
            'Offering Salary Basic',
            'Offering Allow Pulsa',
            'Offering Allow Transport',
            'Offering Employment Status',
            'Offering Contract Duration',
            'Offering Working Hours',
        ] as $required) {
            $this->assertContains($required, $headers,
                "kandidat_accepted MUST contain '{$required}'");
        }
    }

    // =========================================================================
    // SCHEMA_09 — column counts
    // =========================================================================

    public function test_schema09_column_counts(): void
    {
        $this->assertCount(24, config('hris.schemas.data_kandidat'),
            'data_kandidat must have exactly 24 columns');
        $this->assertCount(31, config('hris.schemas.kandidat_hold'),
            'kandidat_hold must have exactly 31 columns');
        $this->assertCount(31, config('hris.schemas.kandidat_blacklist'),
            'kandidat_blacklist must have exactly 31 columns');
        $this->assertCount(57, config('hris.schemas.kandidat_accepted'),
            'kandidat_accepted must have exactly 57 columns');
    }

    // =========================================================================
    // ROWLEN_01 — toSheetRow() count matches data_kandidat header count
    // =========================================================================

    public function test_rowlen01_toSheetRow_length_matches_data_kandidat_header_count(): void
    {
        $candidate  = $this->makeCandidate();
        $row        = $candidate->toSheetRow();
        $headerCount = count(config('hris.schemas.data_kandidat'));

        $this->assertCount($headerCount, $row,
            "toSheetRow() must produce exactly {$headerCount} elements to match data_kandidat headers");
    }

    // =========================================================================
    // ALIGN_01 — data_kandidat: key field header/row index alignment
    // =========================================================================

    public function test_align01_data_kandidat_key_field_positions(): void
    {
        $candidate = $this->makeCandidate([
            'recruitmentId'   => 'REC-ALIGN-001',
            'fullName'        => 'Siti Rahayu',
            'email'           => 'siti@example.com',
            'phone'           => '+6281111111111',
            'city'            => 'Surabaya',
            'positionApplied' => 'Finance Staff',
            'status'          => 'Pending',
        ]);

        $headers = config('hris.schemas.data_kandidat');
        $row     = $candidate->toSheetRow();

        // Verify header count == row count before alignment checks
        $this->assertCount(count($headers), $row);

        $idxById       = array_search('Recruitment ID',   $headers, true);
        $idxByName     = array_search('Full Name',        $headers, true);
        $idxByEmail    = array_search('Email',            $headers, true);
        $idxByPhone    = array_search('Phone',            $headers, true);
        $idxByCity     = array_search('City',             $headers, true);
        $idxByPosition = array_search('Position Applied', $headers, true);
        $idxByStatus   = array_search('Status',           $headers, true);

        $this->assertSame('REC-ALIGN-001',    $row[$idxById],       'Recruitment ID at correct position');
        $this->assertSame('Siti Rahayu',      $row[$idxByName],     'Full Name at correct position');
        $this->assertSame('siti@example.com', $row[$idxByEmail],    'Email at correct position');
        $this->assertSame('Surabaya',         $row[$idxByCity],     'City at correct position');
        $this->assertSame('Finance Staff',    $row[$idxByPosition], 'Position Applied at correct position');
        $this->assertSame('Pending',          $row[$idxByStatus],   'Status at correct position');

        // Explicit numeric positions for the columns most likely to shift
        $this->assertSame(20, $idxByStatus,  'Status must be at index 20 (not shifted by CV Link removal)');
        $this->assertSame(21, array_search('HR Notes', $headers, true),   'HR Notes at index 21');
        $this->assertSame(22, array_search('Created By', $headers, true), 'Created By at index 22');
        $this->assertSame(23, array_search('Updated At', $headers, true), 'Updated At at index 23');

        // These fields must NOT be present in data_kandidat
        $this->assertFalse(array_search('CV Link',    $headers, true), 'CV Link must not exist in data_kandidat');
        $this->assertFalse(array_search('Employee ID', $headers, true), 'Employee ID must not exist in data_kandidat');
    }

    // =========================================================================
    // ALIGN_02 — kandidat_accepted: Employee ID and offering fields at correct positions
    // =========================================================================

    public function test_align02_kandidat_accepted_key_field_positions(): void
    {
        $headers = config('hris.schemas.kandidat_accepted');

        // Employee ID must exist and be at position 29 (0-indexed)
        $idxEmployeeId = array_search('Employee ID', $headers, true);
        $this->assertNotFalse($idxEmployeeId,
            'Employee ID must exist in kandidat_accepted');
        $this->assertSame(29, $idxEmployeeId,
            'Employee ID must be at index 29 in kandidat_accepted');

        // Hold Reason must be at position 24
        $this->assertSame(24, array_search('Hold Reason', $headers, true),
            'Hold Reason must be at index 24 in kandidat_accepted');

        // Blacklist Reason at 26
        $this->assertSame(26, array_search('Blacklist Reason', $headers, true),
            'Blacklist Reason must be at index 26 in kandidat_accepted');

        // Processed Date at 30
        $this->assertSame(30, array_search('Processed Date', $headers, true),
            'Processed Date must be at index 30 in kandidat_accepted');

        // Offering Created at 32
        $this->assertSame(32, array_search('Offering Created', $headers, true),
            'Offering Created must be at index 32 in kandidat_accepted');

        // Onboarding Status at 45
        $this->assertSame(45, array_search('Onboarding Status', $headers, true),
            'Onboarding Status must be at index 45 in kandidat_accepted');

        // Offering Working Hours must be last (index 56)
        $this->assertSame(56, array_search('Offering Working Hours', $headers, true),
            'Offering Working Hours must be the last column (index 56) in kandidat_accepted');

        // CV Link must NOT be present
        $this->assertFalse(array_search('CV Link', $headers, true),
            'CV Link must NOT exist in kandidat_accepted');
    }

    // =========================================================================
    // ALIGN_03 — kandidat_hold / kandidat_blacklist: Status at correct index
    // =========================================================================

    public function test_align03_hold_blacklist_status_position(): void
    {
        foreach (['kandidat_hold', 'kandidat_blacklist'] as $sheet) {
            $headers = config("hris.schemas.{$sheet}");

            $idxStatus = array_search('Status', $headers, true);
            $this->assertSame(20, $idxStatus,
                "Status must be at index 20 in {$sheet} (not shifted by CV Link removal)");

            $this->assertFalse(array_search('CV Link',    $headers, true),
                "CV Link must NOT exist in {$sheet}");
            $this->assertFalse(array_search('Employee ID', $headers, true),
                "Employee ID must NOT exist in {$sheet}");

            // Processed By must be last (index 30)
            $this->assertSame(30, array_search('Processed By', $headers, true),
                "Processed By must be at index 30 (last) in {$sheet}");
        }
    }
}
