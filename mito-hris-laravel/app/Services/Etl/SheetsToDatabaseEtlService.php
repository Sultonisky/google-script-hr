<?php

namespace App\Services\Etl;

use App\DTOs\CandidateData;
use App\DTOs\EmployeeData;
use App\DTOs\MprData;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\MprRequest;
use App\Models\MprRequestor;
use App\Models\Permission;
use App\Models\ProbationEvaluation;
use App\Models\UserPermission;
use App\Services\Google\GoogleSheetsService;
use App\Support\CandidateAttributeMap;
use App\Support\EmployeeAttributeMap;
use App\Support\ProbationAttributeMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * One-way ETL: Google Sheets → relational DB.
 * Sheets are READ-ONLY (getRowsAsAssoc only). Never append/update/clear Spreadsheet.
 */
class SheetsToDatabaseEtlService
{
    public const DOMAINS = [
        'users',
        'permissions',
        'user_permissions',
        'employees',
        'documents',
        'candidates',
        'probation',
        'audit',
        'mpr',
        'mpr_requestors',
    ];

    public function __construct(
        private GoogleSheetsService $sheets
    ) {}

    /**
     * @param  list<string>  $domains
     * @return array<string, array{read:int, written:int, skipped:int, errors:list<string>}>
     */
    public function run(array $domains, bool $dryRun = false, bool $truncateDb = false): array
    {
        $domains = $this->normalizeDomains($domains);
        $summary = [];

        if ($truncateDb && ! $dryRun) {
            $this->truncateDomains($domains);
        }

        foreach ($domains as $domain) {
            $summary[$domain] = match ($domain) {
                'users' => $this->importUsers($dryRun),
                'permissions' => $this->importPermissions($dryRun),
                'user_permissions' => $this->importUserPermissions($dryRun),
                'employees' => $this->importEmployees($dryRun),
                'documents' => $this->importDocuments($dryRun),
                'candidates' => $this->importCandidates($dryRun),
                'probation' => $this->importProbation($dryRun),
                'audit' => $this->importAudit($dryRun),
                'mpr' => $this->importMpr($dryRun),
                'mpr_requestors' => $this->importMprRequestors($dryRun),
                default => throw new RuntimeException("Domain ETL tidak dikenal: {$domain}"),
            };
        }

        return $summary;
    }

    /**
     * @param  list<string>  $domains
     * @return list<string>
     */
    private function normalizeDomains(array $domains): array
    {
        if ($domains === [] || in_array('all', $domains, true)) {
            return self::DOMAINS;
        }

        $normalized = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            if (! in_array($domain, self::DOMAINS, true)) {
                throw new RuntimeException("Domain tidak valid: {$domain}. Pilih: " . implode(', ', self::DOMAINS));
            }
            $normalized[] = $domain;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  list<string>  $domains
     */
    private function truncateDomains(array $domains): void
    {
        // DB only — never Spreadsheet.
        $map = [
            'users' => 'users',
            'permissions' => 'permissions',
            'user_permissions' => 'user_permissions',
            'employees' => 'employees',
            'documents' => 'employee_documents',
            'candidates' => 'candidates',
            'probation' => 'probation_evaluations',
            'audit' => 'audit_logs',
            'mpr' => 'mpr_requests',
            'mpr_requestors' => 'mpr_requestors',
        ];

        \Schema::disableForeignKeyConstraints();
        try {
            foreach ($domains as $domain) {
                $table = $map[$domain] ?? null;
                if ($table && \Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            \Schema::enableForeignKeyConstraints();
        }
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importUsers(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.users', 'Users'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['Email'] ?? '')));
            if ($email === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                // Password Hash from Sheets is already hashed — write via query
                // builder to avoid User model's 'hashed' cast double-hashing.
                $exists = DB::table('users')->where('email', $email)->exists();
                $payload = [
                    'name' => (string) ($row['Full Name'] ?? $row['Username'] ?? $email),
                    'password' => (string) ($row['Password Hash'] ?? ''),
                    'role' => (string) ($row['Role'] ?? 'User'),
                    'status' => (string) ($row['Status'] ?? 'Active'),
                    'updated_at' => now(),
                ];
                if ($exists) {
                    DB::table('users')->where('email', $email)->update($payload);
                } else {
                    DB::table('users')->insert(array_merge($payload, [
                        'email' => $email,
                        'created_at' => now(),
                    ]));
                }
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "users:{$email}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importPermissions(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.permissions', 'Permissions'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $key = trim((string) ($row['Permission Key'] ?? ''));
            if ($key === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                Permission::query()->updateOrCreate(
                    ['permission_key' => $key],
                    [
                        'name' => (string) ($row['Name'] ?? ''),
                        'description' => (string) ($row['Description'] ?? ''),
                        'group' => (string) ($row['Group'] ?? ''),
                        'status' => strtolower(trim((string) ($row['Status'] ?? 'active'))) ?: 'active',
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "permissions:{$key}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importUserPermissions(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.user_permissions', 'User_Permissions'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['User Email'] ?? '')));
            $key = trim((string) ($row['Permission Key'] ?? ''));
            if ($email === '' || $key === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                $granted = $this->parseBool($row['Granted'] ?? false);
                UserPermission::query()->updateOrCreate(
                    ['user_email' => $email, 'permission_key' => $key],
                    [
                        'granted' => $granted,
                        'granted_by' => (string) ($row['Granted By'] ?? ''),
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "user_permissions:{$email}/{$key}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importEmployees(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.employees', 'Employee'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $data = EmployeeData::fromSheetRow($row);
            $id = trim((string) ($data->employeeId ?? ''));
            if ($id === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                $payload = EmployeeAttributeMap::toFillable($data);
                unset($payload['created_at'], $payload['updated_at']);
                Employee::query()->updateOrCreate(['employee_id' => $id], $payload);
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "employees:{$id}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importDocuments(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.employee_documents', 'Employee_Documents'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $docId = trim((string) ($row['Document ID'] ?? ''));
            $empId = ltrim(trim((string) ($row['Employee ID'] ?? '')), "'");
            if ($docId === '' && $empId === '') {
                $skipped++;
                continue;
            }
            if ($docId === '') {
                $docId = 'DOC-' . $empId . '-' . md5(json_encode($row));
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                EmployeeDocument::query()->updateOrCreate(
                    ['document_id' => $docId],
                    [
                        'employee_id' => $empId,
                        'sequence' => (int) ($row['Sequence'] ?? 0),
                        'doc_type' => (string) ($row['Doc Type'] ?? ''),
                        'doc_code' => strtoupper(trim((string) ($row['Doc Code'] ?? ''))),
                        'nomor' => (string) ($row['Nomor'] ?? ''),
                        'entity' => (string) ($row['Entity'] ?? ''),
                        'issued_at' => (string) ($row['Issued At'] ?? ''),
                        'issued_by' => (string) ($row['Issued By'] ?? ''),
                        'reference' => (string) ($row['Reference'] ?? ''),
                        'notes' => (string) ($row['Notes'] ?? ''),
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "documents:{$docId}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importCandidates(bool $dryRun): array
    {
        $written = 0;
        $skipped = 0;
        $errors = [];
        $read = 0;

        foreach (CandidateAttributeMap::BUCKET_TO_LIFECYCLE as $bucket => $lifecycle) {
            $sheetName = config("google.sheets.{$bucket}");
            if (! is_string($sheetName) || $sheetName === '') {
                continue;
            }
            $rows = $this->readSheet($sheetName);
            $read += count($rows);

            foreach ($rows as $row) {
                $data = CandidateData::fromSheetRow($row);
                $id = trim((string) ($data->recruitmentId ?? ''));
                if ($id === '') {
                    $skipped++;
                    continue;
                }
                if ($dryRun) {
                    $written++;
                    continue;
                }
                try {
                    $payload = CandidateAttributeMap::toFillable($data, $lifecycle);
                    Candidate::query()->updateOrCreate(['recruitment_id' => $id], $payload);
                    $written++;
                } catch (\Throwable $e) {
                    $errors[] = "candidates:{$id}: {$e->getMessage()}";
                }
            }
        }

        return $this->result($read, $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importProbation(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.candidates_probation', 'kandidat_probation'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $evalId = trim((string) ($row['Eval ID'] ?? ''));
            $empId = trim((string) ($row['Employee ID'] ?? ''));
            if ($empId === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                $payload = ProbationAttributeMap::toFillable($row);
                if ($evalId !== '') {
                    ProbationEvaluation::query()->updateOrCreate(['eval_id' => $evalId], $payload);
                } else {
                    ProbationEvaluation::query()->create($payload);
                }
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "probation:#{$i}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importAudit(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.audit_log', 'Audit_Log'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $auditId = trim((string) ($row['Audit ID'] ?? ''));
            if ($auditId === '') {
                $auditId = sprintf('AUD-ETL-%06d', $i + 1);
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                $timestamp = trim((string) ($row['Timestamp'] ?? ''));
                AuditLog::query()->updateOrCreate(
                    ['audit_id' => $auditId],
                    [
                        'entity_type' => (string) ($row['Entity Type'] ?? 'Unknown'),
                        'entity_id' => (string) ($row['Entity ID'] ?? $row['Recruitment ID'] ?? ''),
                        'action' => (string) ($row['Action'] ?? ''),
                        'field' => (string) ($row['Field'] ?? ''),
                        'old_value' => (string) ($row['Old Value'] ?? ''),
                        'new_value' => (string) ($row['New Value'] ?? ''),
                        'user' => (string) ($row['User'] ?? ''),
                        'source' => (string) ($row['Source'] ?? 'ETL'),
                        'logged_at' => $timestamp !== '' ? $timestamp : now(),
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "audit:{$auditId}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importMpr(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.mpr', 'MPR'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            $data = MprData::fromSheetRow($row);
            $number = trim((string) ($data->mprNumber ?? ''));
            if ($number === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                MprRequest::query()->updateOrCreate(
                    ['mpr_number' => $number],
                    [
                        'request_date' => $data->requestDate,
                        'requestor_name' => $data->requestorName,
                        'requestor_email' => $data->requestorEmail,
                        'entity' => $data->entity,
                        'department' => $data->department,
                        'division' => $data->division,
                        'approval_division' => $data->approvalDivision,
                        'position' => $data->position,
                        'job_level' => $data->jobLevel,
                        'work_location' => $data->workLocation,
                        'employment_type' => $data->employmentType,
                        'quantity' => $data->quantity ?? 1,
                        'expected_join_date' => $data->expectedJoinDate,
                        'reason' => $data->reason,
                        'replacement_for' => $data->replacementFor,
                        'job_description' => $data->jobDescription,
                        'requirements' => $data->requirements,
                        'requestor_position' => $data->requestorPosition,
                        'working_days' => $data->workingDays,
                        'working_hours' => $data->workingHours,
                        'shift_detail' => $data->shiftDetail,
                        'benefits' => $data->benefits,
                        'education_background' => $data->educationBackground,
                        'work_experience' => $data->workExperience,
                        'skills' => $data->skillsCompetencies,
                        'languages' => $data->languages,
                        'industry_reference' => $data->industryReference,
                        'special_notes' => $data->specialNotes ?? $data->notes,
                        'key_results' => $data->keyResultsTargets,
                        'status' => $data->status ?? 'Submitted',
                        'created_by' => $data->createdBy,
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "mpr:{$number}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function importMprRequestors(bool $dryRun): array
    {
        $rows = $this->readSheet(config('google.sheets.mpr_requestor', 'mpr_requestor'));
        $written = 0;
        $skipped = 0;
        $errors = [];

        // Sheets often seed many rows with the same Requestor ID (e.g. MPR-REQ-001).
        // Allocate unique IDs in DB without writing back to Spreadsheet.
        /** @var array<string, string> $claimedIdToEmail uppercase id => lowercase email */
        $claimedIdToEmail = [];
        if (! $dryRun) {
            foreach (MprRequestor::query()->get(['requestor_id', 'email']) as $existing) {
                $id = strtoupper(trim((string) $existing->requestor_id));
                if ($id !== '') {
                    $claimedIdToEmail[$id] = strtolower(trim((string) $existing->email));
                }
            }
        }

        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['Email'] ?? '')));
            if ($email === '') {
                $skipped++;
                continue;
            }
            if ($dryRun) {
                $written++;
                continue;
            }
            try {
                $preferredId = trim((string) ($row['Requestor ID'] ?? ''));
                $requestorId = $this->allocateUniqueRequestorId($preferredId, $email, $claimedIdToEmail);

                MprRequestor::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'requestor_id' => $requestorId,
                        'username' => (string) ($row['Username'] ?? ''),
                        'full_name' => (string) ($row['Full Name'] ?? ''),
                        'job_position' => (string) ($row['Job Position'] ?? ''),
                        'role' => (string) ($row['Role'] ?? 'Manager'),
                        'status' => (string) ($row['Status'] ?? 'Active'),
                        'password_hash' => (string) ($row['Password Hash'] ?? ''),
                        'last_login' => (string) ($row['Last Login'] ?? ''),
                        'created_by' => (string) ($row['Created By'] ?? ''),
                    ]
                );
                $written++;
            } catch (\Throwable $e) {
                $errors[] = "mpr_requestors:{$email}: {$e->getMessage()}";
            }
        }

        return $this->result(count($rows), $written, $skipped, $errors);
    }

    /**
     * Prefer sheet Requestor ID when free; otherwise keep existing owner's id
     * or generate the next MPR-REQ-NNN. Mutates $claimedIdToEmail.
     *
     * @param  array<string, string>  $claimedIdToEmail
     */
    private function allocateUniqueRequestorId(string $preferred, string $email, array &$claimedIdToEmail): string
    {
        $email = strtolower(trim($email));
        $preferred = trim($preferred);

        if ($preferred !== '') {
            $key = strtoupper($preferred);
            $owner = $claimedIdToEmail[$key] ?? null;
            if ($owner === null || $owner === $email) {
                $claimedIdToEmail[$key] = $email;

                return $preferred;
            }
        }

        // Prefer an ID already stored for this email (re-import / truncate race).
        foreach ($claimedIdToEmail as $id => $owner) {
            if ($owner === $email) {
                return $id;
            }
        }

        $max = 0;
        foreach (array_keys($claimedIdToEmail) as $id) {
            if (preg_match('/^MPR-REQ-(\d+)$/i', $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        do {
            $max++;
            $next = 'MPR-REQ-'.str_pad((string) $max, 3, '0', STR_PAD_LEFT);
            $key = strtoupper($next);
        } while (isset($claimedIdToEmail[$key]));

        $claimedIdToEmail[$key] = $email;

        return $next;
    }

    /**
     * READ-ONLY sheet access.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readSheet(string $sheetName): array
    {
        try {
            return $this->sheets->getRowsAsAssoc($sheetName, false);
        } catch (\Throwable $e) {
            Log::warning("ETL read failed for sheet {$sheetName}: " . $e->getMessage());
            throw new RuntimeException("Gagal membaca sheet '{$sheetName}' (read-only): " . $e->getMessage(), 0, $e);
        }
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    /**
     * @param  list<string>  $errors
     * @return array{read:int, written:int, skipped:int, errors:list<string>}
     */
    private function result(int $read, int $written, int $skipped, array $errors): array
    {
        return compact('read', 'written', 'skipped', 'errors');
    }
}
