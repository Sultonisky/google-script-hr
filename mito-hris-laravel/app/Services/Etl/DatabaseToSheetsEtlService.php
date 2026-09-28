<?php

namespace App\Services\Etl;

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
use App\Support\HrisDataDriver;
use App\Support\ProbationAttributeMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * One-way mirror: relational DB → Google Sheets (archive/visibility).
 *
 * SoT remains the database when HRIS_DATA_DRIVER=pgsql.
 * This never reads Sheets back into DB. Manual Sheet edits are overwritten on sync.
 */
class DatabaseToSheetsEtlService
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
    public function run(array $domains = ['all'], bool $dryRun = false, bool $force = false): array
    {
        if (! $force && ! HrisDataDriver::usesPgsql()) {
            throw new RuntimeException(
                'Mirror DB→Sheets ditolak: HRIS_DATA_DRIVER harus pgsql (SoT). '
                .'Gunakan --force hanya jika sadar Sheets masih SoT dan bisa tertimpa.'
            );
        }

        $selected = $this->normalizeDomains($domains);
        $summary = [];

        foreach ($selected as $domain) {
            $summary[$domain] = match ($domain) {
                'users' => $this->exportUsers($dryRun),
                'permissions' => $this->exportPermissions($dryRun),
                'user_permissions' => $this->exportUserPermissions($dryRun),
                'employees' => $this->exportEmployees($dryRun),
                'documents' => $this->exportDocuments($dryRun),
                'candidates' => $this->exportCandidates($dryRun),
                'probation' => $this->exportProbation($dryRun),
                'audit' => $this->exportAudit($dryRun),
                'mpr' => $this->exportMpr($dryRun),
                'mpr_requestors' => $this->exportMprRequestors($dryRun),
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

        $selected = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            if (! in_array($domain, self::DOMAINS, true)) {
                throw new RuntimeException("Domain ETL tidak dikenal: {$domain}");
            }
            $selected[] = $domain;
        }

        return array_values(array_unique($selected));
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportUsers(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.users', 'Users');
        $headers = config('hris.schemas.Users', []);
        $rows = [];

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $rows[] = $this->valuesForHeaders($headers, [
                'Email' => (string) $user->email,
                'Username' => (string) $user->email,
                'Full Name' => (string) ($user->name ?? ''),
                'Role' => (string) ($user->role ?? 'User'),
                'Status' => (string) ($user->status ?? 'Active'),
                'Password Hash' => (string) ($user->password ?? ''),
                'Last Login' => '',
                'Created At' => (string) ($user->created_at ?? ''),
                'Updated At' => (string) ($user->updated_at ?? ''),
                'Created By' => 'system',
            ]);
        }

        return $this->writeDomain('users', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportPermissions(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.permissions', 'Permissions');
        $headers = config('hris.schemas.Permissions', []);
        $rows = [];

        foreach (Permission::query()->orderBy('id')->get() as $row) {
            $rows[] = $this->valuesForHeaders($headers, [
                'Permission Key' => (string) $row->permission_key,
                'Name' => (string) ($row->name ?? ''),
                'Description' => (string) ($row->description ?? ''),
                'Group' => (string) ($row->group ?? ''),
                'Status' => (string) ($row->status ?? 'active'),
                'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            ]);
        }

        return $this->writeDomain('permissions', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportUserPermissions(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.user_permissions', 'User_Permissions');
        $headers = config('hris.schemas.User_Permissions', []);
        $rows = [];

        foreach (UserPermission::query()->orderBy('id')->get() as $row) {
            $rows[] = $this->valuesForHeaders($headers, [
                'User Email' => (string) $row->user_email,
                'Permission Key' => (string) $row->permission_key,
                'Granted' => $row->granted ? 'TRUE' : 'FALSE',
                'Granted By' => (string) ($row->granted_by ?? ''),
                'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            ]);
        }

        return $this->writeDomain('user_permissions', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportEmployees(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.employees', 'Employee');
        $headers = config('hris.schemas.Employee', []);
        $rows = [];

        foreach (Employee::query()->orderBy('id')->get() as $model) {
            $pos = EmployeeAttributeMap::toData($model)->toSheetRow();
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = (string) ($pos[$i] ?? '');
            }
            $rows[] = array_values($assoc);
        }

        return $this->writeDomain('employees', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportDocuments(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.employee_documents', 'Employee_Documents');
        $headers = config('hris.schemas.Employee_Documents', []);
        $rows = [];

        foreach (EmployeeDocument::query()->orderBy('id')->get() as $row) {
            $rows[] = $this->valuesForHeaders($headers, [
                'Document ID' => (string) $row->document_id,
                'Employee ID' => (string) $row->employee_id,
                'Sequence' => (string) $row->sequence,
                'Doc Type' => (string) ($row->doc_type ?? ''),
                'Doc Code' => (string) ($row->doc_code ?? ''),
                'Nomor' => (string) ($row->nomor ?? ''),
                'Entity' => (string) ($row->entity ?? ''),
                'Issued At' => (string) ($row->issued_at ?? ''),
                'Issued By' => (string) ($row->issued_by ?? ''),
                'Reference' => (string) ($row->reference ?? ''),
                'Notes' => (string) ($row->notes ?? ''),
                'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            ]);
        }

        return $this->writeDomain('documents', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportCandidates(bool $dryRun): array
    {
        // kandidat_probation is owned by probation evaluations — skip that bucket here.
        $buckets = [
            'candidates' => CandidateAttributeMap::LIFECYCLE_PENDING,
            'candidates_hold' => CandidateAttributeMap::LIFECYCLE_HOLD,
            'candidates_blacklist' => CandidateAttributeMap::LIFECYCLE_BLACKLIST,
            'candidates_accepted' => CandidateAttributeMap::LIFECYCLE_ACCEPTED,
        ];

        $read = 0;
        $written = 0;
        $errors = [];

        foreach ($buckets as $configKey => $lifecycle) {
            $sheetName = (string) config("google.sheets.{$configKey}");
            if ($sheetName === '') {
                continue;
            }

            $schemaKey = $sheetName;
            $headers = config("hris.schemas.{$schemaKey}", config('hris.schemas.data_kandidat', []));
            $models = Candidate::query()
                ->where('lifecycle_status', $lifecycle)
                ->orderBy('id')
                ->get();
            $read += $models->count();

            $rows = [];
            foreach ($models as $model) {
                $rows[] = $this->valuesForHeaders($headers, CandidateAttributeMap::toSheetAssoc($model));
            }

            if ($dryRun) {
                $written += count($rows);
                continue;
            }

            try {
                $this->sheets->replaceSheetData($sheetName, $headers, $rows);
                $written += count($rows);
            } catch (\Throwable $e) {
                Log::warning("ETL DB→Sheets candidates/{$sheetName}: ".$e->getMessage());
                $errors[] = "candidates:{$sheetName}: {$e->getMessage()}";
            }
        }

        return $this->result($read, $written, 0, $errors);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportProbation(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.candidates_probation', 'kandidat_probation');
        $headers = config('hris.schemas.kandidat_probation', []);
        $rows = [];

        foreach (ProbationEvaluation::query()->orderBy('id')->get() as $model) {
            $assoc = ProbationAttributeMap::toSheetRow($model);
            $rows[] = $this->valuesForHeaders($headers, $assoc);
        }

        return $this->writeDomain('probation', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportAudit(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.audit_log', 'Audit_Log');
        $headers = config('hris.schemas.Audit_Log', []);
        $rows = [];

        foreach (AuditLog::query()->orderBy('id')->get() as $row) {
            $rows[] = $this->valuesForHeaders($headers, [
                'Audit ID' => (string) $row->audit_id,
                'Entity Type' => (string) ($row->entity_type ?? ''),
                'Entity ID' => (string) ($row->entity_id ?? ''),
                'Action' => (string) ($row->action ?? ''),
                'Field' => (string) ($row->field ?? ''),
                'Old Value' => (string) ($row->old_value ?? ''),
                'New Value' => (string) ($row->new_value ?? ''),
                'User' => (string) ($row->user ?? ''),
                'Source' => (string) ($row->source ?? ''),
                'Timestamp' => optional($row->logged_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    ?? optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    ?? '',
            ]);
        }

        return $this->writeDomain('audit', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportMpr(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.mpr', 'MPR');
        $headers = config('hris.schemas.MPR', []);
        $rows = [];

        foreach (MprRequest::query()->orderBy('id')->get() as $model) {
            $data = new MprData(
                mprNumber: $model->mpr_number,
                requestDate: $model->request_date,
                requestorName: $model->requestor_name,
                requestorEmail: $model->requestor_email,
                entity: $model->entity,
                department: $model->department,
                division: $model->division,
                approvalDivision: $model->approval_division,
                position: $model->position,
                jobLevel: $model->job_level,
                workLocation: $model->work_location,
                employmentType: $model->employment_type,
                quantity: $model->quantity,
                expectedJoinDate: $model->expected_join_date,
                reason: $model->reason,
                replacementFor: $model->replacement_for,
                jobDescription: $model->job_description,
                requirements: $model->requirements,
                status: $model->status,
                createdBy: $model->created_by,
                createdAt: optional($model->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                updatedAt: optional($model->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                requestorPosition: $model->requestor_position,
                workingDays: $model->working_days,
                workingHours: $model->working_hours,
                shiftDetail: $model->shift_detail,
                benefits: $model->benefits,
                educationBackground: $model->education_background,
                workExperience: $model->work_experience,
                skillsCompetencies: $model->skills,
                languages: $model->languages,
                industryReference: $model->industry_reference,
                specialNotes: $model->special_notes,
                keyResultsTargets: $model->key_results,
            );
            $rows[] = $this->valuesForHeaders($headers, $data->toSheetRow());
        }

        return $this->writeDomain('mpr', $sheet, $headers, $rows, $dryRun);
    }

    /** @return array{read:int, written:int, skipped:int, errors:list<string>} */
    private function exportMprRequestors(bool $dryRun): array
    {
        $sheet = (string) config('google.sheets.mpr_requestor', 'mpr_requestor');
        $headers = config('hris.schemas.mpr_requestor', []);
        $rows = [];

        foreach (MprRequestor::query()->orderBy('id')->get() as $row) {
            $rows[] = $this->valuesForHeaders($headers, [
                'Requestor ID' => (string) $row->requestor_id,
                'Email' => (string) $row->email,
                'Username' => (string) ($row->username ?? ''),
                'Full Name' => (string) ($row->full_name ?? ''),
                'Job Position' => (string) ($row->job_position ?? ''),
                'Role' => (string) ($row->role ?? ''),
                'Status' => (string) ($row->status ?? ''),
                'Password Hash' => (string) ($row->password_hash ?? ''),
                'Last Login' => (string) ($row->last_login ?? ''),
                'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                'Created By' => (string) ($row->created_by ?? ''),
            ]);
        }

        return $this->writeDomain('mpr_requestors', $sheet, $headers, $rows, $dryRun);
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $assoc
     * @return list<string>
     */
    private function valuesForHeaders(array $headers, array $assoc): array
    {
        $values = [];
        foreach ($headers as $header) {
            $values[] = (string) ($assoc[$header] ?? '');
        }

        return $values;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @return array{read:int, written:int, skipped:int, errors:list<string>}
     */
    private function writeDomain(string $domain, string $sheet, array $headers, array $rows, bool $dryRun): array
    {
        $read = count($rows);
        if ($dryRun) {
            return $this->result($read, $read, 0, []);
        }

        try {
            if (! is_array($headers) || $headers === []) {
                throw new RuntimeException("Schema headers kosong untuk domain {$domain} / sheet {$sheet}");
            }
            $this->sheets->replaceSheetData($sheet, $headers, $rows);

            return $this->result($read, $read, 0, []);
        } catch (\Throwable $e) {
            Log::warning("ETL DB→Sheets {$domain}: ".$e->getMessage());

            return $this->result($read, 0, 0, ["{$domain}: {$e->getMessage()}"]);
        }
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
