<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use App\Services\Google\GoogleDriveService;
use App\Services\Google\GoogleSheetsService;
use App\Services\PdfGeneratorService;
use App\Services\ProbationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use RuntimeException;

class EmployeeService
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;
    protected EmployeeIdGenerator $idGenerator;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo,
        EmployeeIdGenerator $idGenerator,
        private GoogleDriveService $driveService,
        private PdfGeneratorService $pdfService,
        private GoogleSheetsService $sheets
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo    = $auditRepo;
        $this->idGenerator  = $idGenerator;
    }

    /**
     * Resolve the canonical ProbationService from the container.
     * Resolved lazily (not constructor-injected) to avoid a hard coupling
     * and to preserve the EmployeeService constructor signature for
     * existing callers / tests that instantiate it directly.
     */
    protected function probationService(): ProbationService
    {
        return App::make(ProbationService::class);
    }

    /**
     * Create a single new employee manually (1:1 dengan GAS acceptCandidateToEmployee flow,
     * tapi dipanggil langsung dari form manual — bukan dari rekrutmen).
     *
     * @param  array       $data  Field input dari form (camelCase atau flat array)
     * @param  string|null $user  Nama user yang membuat (default: 'HR Administrator')
     * @return array{success:bool, message:string, employeeId:string|null}
     */
    public function createEmployee(array $data, ?string $user = null): array
    {
        $user = $user ?: 'HR Administrator';
        $now  = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');

        // --- Validasi wajib ---
        $fullName = trim($data['fullName'] ?? '');
        if (empty($fullName)) {
            return ['success' => false, 'message' => 'Nama lengkap wajib diisi.', 'employeeId' => null];
        }

        // --- Generate Employee ID unik ---
        $existingIds = $this->employeeRepo->getAll()
            ->pluck('employeeId')
            ->filter()
            ->map(fn($id) => strtoupper(trim($id)))
            ->toArray();

        $empId   = $this->idGenerator->generate($data['joinDate'] ?? null, $existingIds);
        $attempts = 0;
        while (in_array(strtoupper($empId), $existingIds, true) && $attempts < 10) {
            $empId = $this->idGenerator->generate($data['joinDate'] ?? null, $existingIds);
            $attempts++;
        }
        if (in_array(strtoupper($empId), $existingIds, true)) {
            return ['success' => false, 'message' => 'Gagal menghasilkan Employee ID unik. Silakan coba kembali.', 'employeeId' => null];
        }

        // --- Bangun EmployeeData DTO ---
        $employee = new EmployeeData(
            employeeId: $empId,
            fullName: $fullName,
            branchName: trim($data['branchName'] ?? ''),
            division: trim($data['division'] ?? ''),
            department: trim($data['department'] ?? ''),
            jobPositionLocation: trim($data['jobPositionLocation'] ?? $data['jobPosition'] ?? ''),
            jobPosition: trim($data['jobPosition'] ?? $data['jobPositionLocation'] ?? ''),
            areaKerja: trim($data['areaKerja'] ?? ''),
            lokasiKerja: trim($data['lokasiKerja'] ?? ''),
            jobLevel: trim($data['jobLevel'] ?? ''),
            grade: trim($data['grade'] ?? ''),
            joinDate: trim($data['joinDate'] ?? ''),
            statusEmployee: trim($data['statusEmployee'] ?? 'Contract'),
            directSuperior: trim($data['directSuperior'] ?? ''),
            indirectSuperior: trim($data['indirectSuperior'] ?? ''),
            personalEmail: trim($data['personalEmail'] ?? ''),
            workingEmail: trim($data['workingEmail'] ?? ''),
            endDateContract: trim($data['endDateContract'] ?? ''),
            birthPlace: trim($data['birthPlace'] ?? ''),
            birthDate: trim($data['birthDate'] ?? ''),
            citizenIdAddress: trim($data['citizenIdAddress'] ?? ''),
            residentialAddress: trim($data['residentialAddress'] ?? ''),
            nikNpwp: ltrim(trim($data['nikNpwp'] ?? $data['nik'] ?? ''), "'"),
            npwp: ltrim(trim($data['npwp'] ?? ''), "'"),
            ptkpStatus: trim($data['ptkpStatus'] ?? ''),
            bankName: trim($data['bankName'] ?? 'BCA'),
            bankAccount: ltrim(trim($data['bankAccount'] ?? ''), "'"),
            bankAccountHolder: trim($data['bankAccountHolder'] ?? $fullName),
            bpjsKetenagakerjaan: ltrim(trim($data['bpjsKetenagakerjaan'] ?? ''), "'"),
            bpjsKesehatan: ltrim(trim($data['bpjsKesehatan'] ?? ''), "'"),
            mobilePhone: ltrim(trim($data['mobilePhone'] ?? ''), "'"),
            religion: trim($data['religion'] ?? ''),
            gender: trim($data['gender'] ?? ''),
            maritalStatus: trim($data['maritalStatus'] ?? ''),
            bloodType: trim($data['bloodType'] ?? ''),
            costCenter: trim($data['costCenter'] ?? ''),
            jobPositionFormer: '',
            typeOfRotation: '',
            rotationDate: '',
            nomorSk: '',
            resignDate: '',
            hrNotes: trim($data['hrNotes'] ?? ''),
            offboardingType: '',
            offboardingReason: '',
            offboardingApprovedBy: '',
            offboardingDocsFolder: '',
            offboardingDocLinks: '',
            outsourceVendor: trim($data['outsourceVendor'] ?? ''),
            outsourceContractSeq: 0,
            createdBy: $user,
            createdAt: $nowStr,
            updatedAt: $nowStr,
        );

        // --- Tulis ke Google Sheets ---
        $sheetName = config('google.sheets.employees', 'Employee');
        $wrote = $this->sheets->appendRow($sheetName, $employee->toSheetRow());

        if (!$wrote) {
            return [
                'success'    => false,
                'message'    => 'Gagal menyimpan data karyawan ke Google Sheets. Silakan coba kembali.',
                'employeeId' => null,
            ];
        }

        // --- Audit log ---
        $this->auditRepo->log(
            entityType: 'Employee',
            entityId: $empId,
            action: 'CREATE',
            field: 'Status Employee',
            oldValue: '-',
            newValue: ($employee->statusEmployee ?? 'Contract') . ' — dibuat manual oleh HR',
            user: $user,
            source: 'Dashboard'
        );

        return [
            'success'    => true,
            'message'    => "Karyawan {$fullName} berhasil ditambahkan dengan Employee ID {$empId}.",
            'employeeId' => $empId,
        ];
    }

    /**
     * Preview import — validate rows and check duplicates WITHOUT writing to Google Sheets.
     * 1:1 with GAS importEmployees() validation logic, minus the batch write.
     *
     * Returns per-row classification: new | duplicate_existing | duplicate_internal | invalid
     * plus summary counts for the modal Step 2 display.
     */
    public function previewImport(array $rows): array
    {
        if (empty($rows)) {
            return [
                'success'  => false,
                'message'  => 'Tidak ada data untuk dipreview.',
                'total'    => 0,
                'new'      => 0,
                'existing' => 0,
                'invalid'  => 0,
                'duplicate_internal' => 0,
                'rows'     => [],
            ];
        }

        // Read all existing Employee IDs from the sheet (UPPERCASE for comparison)
        $existingEmployees = $this->employeeRepo->getAll();
        $existingIds = $existingEmployees
            ->pluck('employeeId')
            ->filter()
            ->map(fn($id) => strtoupper(trim($id)))
            ->values()
            ->toArray();

        $seenInFile  = [];   // track Employee IDs encountered within this file (uppercase)
        $resultRows  = [];
        $countNew    = 0;
        $countExist  = 0;
        $countInvalid = 0;
        $countDupInternal = 0;

        foreach ($rows as $index => $row) {
            $rowNum   = $index + 1;
            $issues   = [];
            $status   = 'new';   // new | duplicate_existing | duplicate_internal | invalid

            // --- Required field: Full Name (1:1 GAS importEmployees) ---
            $fullName = trim($row['fullName'] ?? $row['name'] ?? $row['nama'] ?? '');
            if ($fullName === '') {
                $issues[] = 'Nama lengkap wajib diisi';
                $status   = 'invalid';
            }

            // --- Employee ID duplicate checks ---
            $rawId = trim($row['employeeId'] ?? $row['empId'] ?? $row['idKaryawan'] ?? '');
            if ($rawId !== '') {
                $rawIdUpper = strtoupper($rawId);

                // Check against existing sheet data
                if (in_array($rawIdUpper, $existingIds, true)) {
                    $issues[] = "Employee ID '{$rawId}' sudah ada di sheet";
                    $status   = 'duplicate_existing';
                }
                // Check for internal duplicate within this file
                elseif (in_array($rawIdUpper, $seenInFile, true)) {
                    $issues[] = "Employee ID '{$rawId}' duplikat di dalam file";
                    $status   = 'duplicate_internal';
                } else {
                    $seenInFile[] = $rawIdUpper;
                }
            } else {
                // No explicit ID — will be auto-generated; track by full name as proxy
                // (GAS does NOT deduplicate no-ID rows by name; we flag a warning only)
                $nameLower = strtolower($fullName);
                if ($nameLower !== '' && in_array($nameLower, $seenInFile, true)) {
                    $issues[] = "Nama '{$fullName}' muncul lebih dari sekali dalam file";
                    if ($status === 'new') {
                        $status = 'duplicate_internal';
                    }
                } elseif ($nameLower !== '') {
                    $seenInFile[] = $nameLower;
                }
            }

            // --- Date format validation (loose: just check if non-empty dates are parseable) ---
            foreach (['joinDate', 'birthDate', 'endDateContract', 'resignDate'] as $dateField) {
                $val = trim($row[$dateField] ?? $row[lcfirst($dateField)] ?? '');
                if ($val !== '' && strtotime($val) === false) {
                    $issues[] = "Format tanggal {$dateField} tidak valid: '{$val}'";
                    if ($status === 'new') {
                        $status = 'invalid';
                    }
                }
            }

            // --- Status Employee validation (1:1 GAS allowed statuses) ---
            $allowedStatuses = ['Permanent', 'Contract', 'Probation', 'Outsource', 'PKWTT', 'PKWT'];
            $empStatus = trim(
                $row['statusEmployee'] ?? $row['employmentStatus'] ?? $row['status'] ?? ''
            );
            if ($empStatus !== '' && !in_array($empStatus, $allowedStatuses, true)) {
                // Warn but don't hard-reject — GAS uses default 'Contract' for unknown status
                $issues[] = "Status Employee '{$empStatus}' tidak dikenali (akan digunakan nilai default)";
            }

            // Tally
            if ($status === 'new' && empty(array_filter($issues, fn($i) => str_contains($i, 'wajib') || str_contains($i, 'tidak valid')))) {
                $countNew++;
            } elseif ($status === 'duplicate_existing') {
                $countExist++;
            } elseif ($status === 'duplicate_internal') {
                $countDupInternal++;
            } elseif ($status === 'invalid') {
                $countInvalid++;
            }

            $resultRows[] = [
                'row'          => $rowNum,
                'employeeId'   => $rawId ?: '(auto)',
                'fullName'     => $fullName ?: '-',
                'department'   => trim($row['department'] ?? $row['dept'] ?? $row['departemen'] ?? ''),
                'jobPosition'  => trim($row['positionCurrent'] ?? $row['jobPositionLocation'] ?? $row['positionNoLocCurrent'] ?? $row['jobPosition'] ?? $row['position'] ?? $row['jabatan'] ?? ''),
                'statusEmployee' => $empStatus ?: 'Contract',
                'joinDate'     => trim($row['joinDate'] ?? $row['tanggalMasuk'] ?? ''),
                'status'       => $status,   // new | duplicate_existing | duplicate_internal | invalid
                'issues'       => $issues,
            ];
        }

        $total     = count($rows);
        $canImport = $countNew > 0;

        return [
            'success'            => true,
            'total'              => $total,
            'new'                => $countNew,
            'existing'           => $countExist,
            'invalid'            => $countInvalid,
            'duplicate_internal' => $countDupInternal,
            'can_import'         => $canImport,
            'message'            => $canImport
                ? "{$countNew} baris baru siap diimport."
                : 'Tidak ada baris baru yang dapat diimport.',
            'rows'               => $resultRows,
        ];
    }

    /**
     * Bulk import employees from array of row data (1:1 with Import.gs).
     */
    public function importEmployees(array $rows, ?string $user = null): array
    {
        if (empty($rows)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Tidak ada data untuk diimport.'],
                'warnings' => [],
                'message' => 'Tidak ada data untuk diimport.'
            ];
        }

        $user = $user ?: 'HR Administrator';
        $errors = [];
        $warnings = [];
        $imported = 0;
        $sheetRows = [];

        $existingEmployees = $this->employeeRepo->getAll();
        $existingIds = $existingEmployees->pluck('employeeId')->filter()->map('strtoupper')->toArray();

        // Batch generate IDs for rows without explicit employeeId
        $rowsNeedingId = [];
        foreach ($rows as $index => $row) {
            $rawId = trim($row['employeeId'] ?? $row['empId'] ?? $row['idKaryawan'] ?? '');
            if (empty($rawId)) {
                $rowsNeedingId[] = $index;
            }
        }
        $batchIds = [];
        if (count($rowsNeedingId) > 0) {
            $batchIds = $this->idGenerator->generateBatch(count($rowsNeedingId));
        }

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;

            $fullName = trim($row['fullName'] ?? $row['name'] ?? $row['nama'] ?? '');
            if (empty($fullName)) {
                $errors[] = "Baris {$rowNum}: Nama lengkap wajib diisi.";
                continue;
            }

            $rawId = trim($row['employeeId'] ?? $row['empId'] ?? $row['idKaryawan'] ?? '');
            if ($rawId) {
                if (in_array(strtoupper($rawId), $existingIds)) {
                    $errors[] = "Baris {$rowNum}: Employee ID '{$rawId}' sudah ada di sheet.";
                    continue;
                }
                $empId = $rawId;
            } else {
                $empId = array_shift($batchIds);
                // Fallback if batch empty or conflict
                $attempts = 0;
                while (in_array(strtoupper($empId), $existingIds) && $attempts < 10) {
                    $empId = $this->idGenerator->generate();
                    $attempts++;
                }
                if (in_array(strtoupper($empId), $existingIds)) {
                    $errors[] = "Baris {$rowNum}: Gagal menghasilkan Employee ID unik setelah beberapa percobaan.";
                    continue;
                }
            }
            $existingIds[] = strtoupper($empId);

            $employee = new EmployeeData(
                employeeId: $empId,
                fullName: $fullName,
                branchName: trim($row['branchName'] ?? $row['branch'] ?? $row['cabang'] ?? ''),
                division: trim($row['division'] ?? $row['divisi'] ?? ''),
                department: trim($row['department'] ?? $row['dept'] ?? $row['departemen'] ?? ''),
                jobPositionLocation: trim($row['jobPositionLocation'] ?? $row['positionCurrent'] ?? $row['posisi_lokasi'] ?? $row['positionNoLocCurrent'] ?? $row['jobPosition'] ?? $row['position'] ?? $row['jabatan'] ?? ''),
                jobPosition: trim($row['jobPosition'] ?? $row['positionNoLocCurrent'] ?? $row['position'] ?? $row['jabatan'] ?? $row['positionCurrent'] ?? $row['jobPositionLocation'] ?? $row['posisi_lokasi'] ?? ''),
                areaKerja: trim($row['areaKerja'] ?? $row['district'] ?? ''),
                lokasiKerja: trim($row['lokasiKerja'] ?? $row['city'] ?? ''),
                jobLevel: trim($row['jobLevel'] ?? $row['level'] ?? ''),
                grade: trim($row['grade'] ?? ''),
                joinDate: trim($row['joinDate'] ?? $row['tanggalMasuk'] ?? ''),
                statusEmployee: trim($row['statusEmployee'] ?? $row['employmentStatus'] ?? $row['status'] ?? 'PKWT'),
                directSuperior: trim($row['directSuperior'] ?? $row['atasanLangsung'] ?? ''),
                indirectSuperior: trim($row['indirectSuperior'] ?? $row['atasanTidakLangsung'] ?? ''),
                personalEmail: trim($row['personalEmail'] ?? $row['email'] ?? ''),
                workingEmail: trim($row['workingEmail'] ?? $row['emailKantor'] ?? ''),
                endDateContract: trim($row['endDateContract'] ?? $row['contractEnd'] ?? $row['akhirKontrak'] ?? ''),
                birthPlace: trim($row['birthPlace'] ?? $row['tempatLahir'] ?? ''),
                birthDate: trim($row['birthDate'] ?? $row['tanggalLahir'] ?? ''),
                citizenIdAddress: trim($row['citizenIdAddress'] ?? $row['alamatKtp'] ?? $row['address'] ?? ''),
                residentialAddress: trim($row['residentialAddress'] ?? $row['alamatDomisili'] ?? $row['address'] ?? ''),
                nikNpwp: ltrim($row['nik'] ?? $row['NIK - NPWP 16 digit'] ?? '', "'"),
                npwp: ltrim($row['npwp'] ?? '', "'"),
                ptkpStatus: trim($row['ptkpStatus'] ?? $row['ptkp'] ?? ''),
                bankName: trim($row['bankName'] ?? $row['namaBank'] ?? 'BCA'),
                bankAccount: ltrim($row['bankAccount'] ?? $row['nomorRekening'] ?? $row['rekening'] ?? '', "'"),
                bankAccountHolder: trim($row['bankAccountHolder'] ?? $row['atasNama'] ?? $fullName),
                bpjsKetenagakerjaan: ltrim($row['bpjsKetenagakerjaan'] ?? $row['bpjsTk'] ?? '', "'"),
                bpjsKesehatan: ltrim($row['bpjsKesehatan'] ?? $row['bpjsKes'] ?? '', "'"),
                mobilePhone: ltrim($row['mobilePhone'] ?? $row['phone'] ?? $row['hp'] ?? '', "'"),
                religion: trim($row['religion'] ?? $row['agama'] ?? ''),
                gender: trim($row['gender'] ?? $row['jenisKelamin'] ?? ''),
                maritalStatus: trim($row['maritalStatus'] ?? $row['statusPernikahan'] ?? ''),
                bloodType: trim($row['bloodType'] ?? $row['golonganDarah'] ?? ''),
                costCenter: trim($row['costCenter'] ?? $row['costcenter'] ?? $row['pusatbiaya'] ?? ''),
                jobPositionFormer: trim($row['positionFormer'] ?? $row['jobPositionFormer'] ?? ''),
                typeOfRotation: trim($row['typeOfRotation'] ?? $row['jenisRotasi'] ?? ''),
                rotationDate: trim($row['rotationDate'] ?? $row['mutasiDate'] ?? ''),
                nomorSk: trim($row['nomorSk'] ?? ''),
                resignDate: trim($row['resignDate'] ?? $row['tanggalResign'] ?? ''),
                hrNotes: trim($row['hrNotes'] ?? $row['notes'] ?? $row['catatan'] ?? ''),
                offboardingType: trim($row['offboardingType'] ?? ''),
                offboardingReason: trim($row['offboardingReason'] ?? ''),
                offboardingApprovedBy: trim($row['offboardingApprovedBy'] ?? ''),
                outsourceVendor: trim($row['outsourceVendor'] ?? $row['vendor'] ?? ''),
                outsourceContractSeq: 0,
                createdBy: $user,
                createdAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                updatedAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            );

            $sheetRows[] = $employee->toSheetRow();
            $imported++;
        }

        if (!empty($sheetRows)) {
            $sheetName = config('google.sheets.employees', 'Employee');
            $wrote = $this->sheets->appendRows($sheetName, $sheetRows);

            if (!$wrote) {
                return [
                    'success' => false,
                    'imported' => 0,
                    'errors' => ['Gagal menulis data karyawan ke Google Sheets.'],
                    'warnings' => $warnings,
                    'message' => 'Gagal menulis data karyawan ke Google Sheets.'
                ];
            }
        }

        if ($imported > 0) {
            $this->auditRepo->log(
                entityType: 'Employee',
                entityId: 'IMPORT-' . now()->format('YmdHis'),
                action: 'Import',
                field: 'Status Employee',
                oldValue: '-',
                newValue: $imported . ' karyawan ditambahkan via import massal',
                user: $user,
                source: 'Dashboard'
            );
        }

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'errors' => $errors,
            'warnings' => $warnings,
            'message' => $imported . ' karyawan berhasil diimport.' . (!empty($errors) ? ' ' . count($errors) . ' baris gagal.' : '')
        ];
    }

    /**
     * Process Employee Rotation / Mutation / Promotion / Demotion (1:1 with backend/Employee.gs).
     *
     * @return array{success:bool, message:string, skNumber:string, employeeId:string, oldPosition:string, newPosition:string, oldDepartment:string, newDepartment:string, oldBranch:string, newBranch:string, rotationType:string, effectiveDate:string, notes:string}
     * @throws RuntimeException
     */
    public function processRotation(string $employeeId, array $data, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan dengan ID {$employeeId} tidak ditemukan.");
        }

        // STEP 16/17/22: active-probation employees are excluded from Rotation.
        // Canonical active-probation determination — see ProbationService.
        $this->throwIfOnProbation($employeeId, 'Rotasi/Mutasi');

        $user = $user ?: 'HR Administrator';
        $now = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');

        $oldPosition   = $employee->jobPosition ?? '';
        $oldDepartment = $employee->department ?? '';
        $oldBranch     = $employee->branchName ?? '';

        $newPosition   = $data['new_job_position'] ?? $oldPosition;
        $newDepartment = $data['new_department'] ?? $oldDepartment;
        $newBranch     = $data['new_branch_name'] ?? $oldBranch;
        $newDivision   = $data['new_division'] ?? $employee->division ?? '';
        $rotationType  = $data['rotation_type'] ?? 'Mutasi';
        $effectiveDate = $data['effective_date'] ?? $now->format('Y-m-d');
        $notesRaw      = $data['notes'] ?? '';

        // Nomor SK selalu di-generate server-side — tidak boleh menerima dari input request
        $skNumber = ''; {
            // Resolusi entity abbreviation dari branch name (1:1 dengan kop-surat.blade.php entity resolver)
            $branchForEntity = strtolower($newBranch ?: $oldBranch ?: '');
            if (str_contains($branchForEntity, 'stein')) {
                $entityCode = 'SPI';
            } elseif (str_contains($branchForEntity, 'injeksi')) {
                $entityCode = 'PII';
            } elseif (str_contains($branchForEntity, 'mitra') || str_contains($branchForEntity, 'elektro')) {
                $entityCode = 'MEP';
            } else {
                $entityCode = 'MSI';
            }

            $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $datePart   = $now->format('Ym');
            $cacheKey   = "SK_ROT_COUNTER_{$datePart}";
            $lock       = \Illuminate\Support\Facades\Cache::lock("lock_{$cacheKey}", 10);
            try {
                $lock->block(10);
                $seq = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0) + 1;
                \Illuminate\Support\Facades\Cache::put($cacheKey, $seq, $now->endOfMonth());
            } finally {
                $lock->release();
            }
            $skNumber = sprintf('%03d/HRD-PK/%s/%s/%d', $seq, $entityCode, $romanMonth[$now->month - 1], $now->year);
        }

        $attributes = [
            'Job Position (Former)'        => $oldPosition,
            'Job Position'                 => $newPosition,
            'Job Position (Location)'      => $newPosition,
            'Branch Name'                  => $newBranch,
            'Division'                     => $newDivision,
            'Department'                   => $newDepartment,
            'Type of Rotation'             => $rotationType,
            'Tanggal Mutasi/Demosi/Promosi' => $effectiveDate,
            'Nomor SK'                     => $skNumber,
            'Updated At'                   => $nowStr,
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                entityType: 'Employee',
                entityId: $employeeId,
                action: 'ROTATION_' . strtoupper($rotationType),
                field: 'Job Position / Dept',
                oldValue: "{$oldPosition} ({$oldDepartment})",
                newValue: "{$newPosition} ({$newDepartment}) | Efektif: {$effectiveDate} | SK: {$skNumber}" . ($notesRaw ? " | {$notesRaw}" : ''),
                user: $user,
                source: 'Dashboard'
            );
        }

        return [
            'success'       => $success,
            'message'       => $success
                ? "Rotasi/Mutasi karyawan {$employeeId} berhasil diproses. SK: {$skNumber}"
                : "Gagal memproses rotasi karyawan {$employeeId}.",
            'skNumber'      => $skNumber,
            'employeeId'    => $employeeId,
            'oldPosition'   => $oldPosition,
            'newPosition'   => $newPosition,
            'oldDepartment' => $oldDepartment,
            'newDepartment' => $newDepartment,
            'oldBranch'     => $oldBranch,
            'newBranch'     => $newBranch,
            'rotationType'  => $rotationType,
            'effectiveDate' => $effectiveDate,
            'notes'         => trim($notesRaw),
        ];
    }

    /**
     * Process Employee Offboarding (Resign, Terminated, Retired, Deceased) (1:1 with backend/Employee.gs).
     *
     * Offboarding types (1:1 GAS statusMap):
     *   Resignation  → Resigned
     *   Termination  → Terminated
     *   Retirement   → Retired
     *   Death        → Deceased
     *
     * Attachment requirement (1:1 GAS _hasRequiredDeathDocument_):
     *   Death → requires 'attachment_Death' (Surat Kematian)
     *   Others → optional documents
     *
     * @throws RuntimeException when employee not found
     */
    public function processOffboarding(string $employeeId, array $data, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan dengan ID {$employeeId} tidak ditemukan.");
        }

        $user = $user ?: 'HR Administrator';
        $now    = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');

        $offboardingType = trim($data['offboarding_type'] ?? 'Resignation');
        $effectiveDate   = trim($data['effective_date'] ?? $data['last_working_date'] ?? $now->format('Y-m-d'));
        $reason          = trim($data['reason'] ?? '');
        $approvedBy      = trim($data['approved_by'] ?? $user);
        $notes           = trim($data['notes'] ?? '');

        // Map offboarding type → new status (1:1 GAS statusMap)
        $statusMap = [
            'Resignation'       => 'Resigned',
            'Termination'       => 'Terminated',
            'Retirement'        => 'Retired',
            'Death'             => 'Deceased',
        ];
        $newStatus = $statusMap[$offboardingType] ?? 'Resigned';
        $oldStatus = $employee->statusEmployee ?? 'Active';

        // Generate SK number server-side (1:1 GAS generateSkOffNumber_)
        $branchForEntity = strtolower($employee->branchName ?? '');
        if (str_contains($branchForEntity, 'stein')) {
            $entityCode = 'SPI';
        } elseif (str_contains($branchForEntity, 'injeksi')) {
            $entityCode = 'PII';
        } elseif (str_contains($branchForEntity, 'mitra') || str_contains($branchForEntity, 'elektro')) {
            $entityCode = 'MEP';
        } else {
            $entityCode = 'MSI';
        }
        $romanMonth = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $datePart   = $now->format('Ym');
        $cacheKey   = "SK_OFF_COUNTER_{$datePart}";
        $lock       = \Illuminate\Support\Facades\Cache::lock("lock_{$cacheKey}", 10);
        try {
            $lock->block(10);
            $seq = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0) + 1;
            \Illuminate\Support\Facades\Cache::put($cacheKey, $seq, $now->endOfMonth());
        } finally {
            $lock->release();
        }
        $skNumber = sprintf('%03d/HRD-SKK/%s/%s/%d', $seq, $entityCode, $romanMonth[$now->month - 1], $now->year);

        // Preserve last position to Job Position (Former) if not already set
        $currentPosition = $employee->jobPositionLocation ?? $employee->jobPosition ?? '';
        $formerPosition  = $employee->jobPositionFormer ?? '';

        // Step 1: Update Employee Sheet
        $attributes = [
            'Status Employee'          => $newStatus,
            'Resign Date'              => $effectiveDate,
            'Offboarding Type'         => $offboardingType,
            'Offboarding Reason'       => $reason,
            'Offboarding Approved By'  => $approvedBy,
            'Nomor SK'                 => $skNumber,
            'Updated At'               => $nowStr,
        ];
        if ($currentPosition && !$formerPosition) {
            $attributes['Job Position (Former)'] = $currentPosition;
        }
        if (!empty($data['bpjs_tk'])) {
            $attributes['BPJS Ketenagakerjaan'] = $data['bpjs_tk'];
        }
        if (!empty($data['bpjs_kes'])) {
            $attributes['BPJS Kesehatan'] = $data['bpjs_kes'];
        }

        $success = $this->employeeRepo->update($employeeId, $attributes);
        if (!$success) {
            return [
                'success' => false,
                'message' => "Gagal memperbarui data karyawan {$employeeId} di Google Sheets.",
            ];
        }

        $this->auditRepo->log(
            entityType: 'Employee',
            entityId: $employeeId,
            action: 'OFFBOARDING_' . strtoupper($offboardingType),
            field: 'Status Employee',
            oldValue: $oldStatus,
            newValue: "{$newStatus} — {$offboardingType} (SK: {$skNumber})" . ($notes ? " | {$notes}" : ''),
            user: $user,
            source: 'Dashboard'
        );

        // Step 2: Upload attachment documents to Google Drive
        $driveResult = ['success' => true, 'folder_url' => null, 'links_string' => ''];
        $attachments  = $data['_attachments'] ?? []; // UploadedFile[] keyed by doc type
        if (!empty($attachments)) {
            $existingFolder = $employee->offboardingDocsFolder ?? null;
            $driveResult = $this->driveService->uploadOffboardingDocuments(
                $employeeId,
                $employee->fullName ?? $employeeId,
                $attachments,
                $existingFolder
            );

            if ($driveResult['success'] && ($driveResult['folder_url'] || $driveResult['links_string'])) {
                $sheetAttrs = [];
                if ($driveResult['folder_url']) {
                    $sheetAttrs['Offboarding Documents Folder'] = $driveResult['folder_url'];
                }
                if ($driveResult['links_string']) {
                    $existingLinks = $employee->offboardingDocLinks ?? '';
                    $sheetAttrs['Offboarding Document Links'] = $existingLinks
                        ? $existingLinks . "\n" . $driveResult['links_string']
                        : $driveResult['links_string'];
                }
                if (!empty($sheetAttrs)) {
                    $this->employeeRepo->update($employeeId, $sheetAttrs);
                }

                if (!empty($driveResult['uploaded'])) {
                    $docSummary = implode('; ', array_map(
                        fn($d) => ($d['type'] ?? '') . ': ' . ($d['fileName'] ?? ''),
                        $driveResult['uploaded']
                    ));
                    $this->auditRepo->log(
                        entityType: 'Employee',
                        entityId: $employeeId,
                        action: 'OFFBOARDING_DOCUMENT',
                        field: 'Offboarding Document Links',
                        oldValue: '-',
                        newValue: $docSummary,
                        user: $user,
                        source: 'Dashboard'
                    );
                }
            }
        }

        // Step 3: Build PDF download URLs (same pattern as processOffContract)
        // PDFs are NOT generated server-side here — they are downloaded via existing export routes
        // using the employee data that was just written to the sheet.
        $extraQ = http_build_query([
            'effective_date'    => $effectiveDate,
            'last_working_date' => $effectiveDate,
            'sk_number'         => $skNumber,
            'offboarding_type'  => $offboardingType,
            'notes'             => $notes,
            'approved_by'       => $approvedBy,
        ]);
        $pdfUrls = [
            'sk_off'     => route('hr.export.sk-off',     ['id' => $employeeId]) . '?' . $extraQ,
            'surat_bpjs' => route('hr.export.surat-bpjs', ['id' => $employeeId]) . '?' . $extraQ,
            'paklaring'  => route('hr.export.paklaring',  ['id' => $employeeId]) . '?' . $extraQ,
            'bundle'     => route('hr.export.offboarding-bundle', ['id' => $employeeId]) . '?' . $extraQ,
        ];

        return [
            'success'           => true,
            'message'           => "Offboarding karyawan {$employeeId} berhasil diproses. Status diubah ke \"{$newStatus}\".",
            'employeeId'        => $employeeId,
            'newStatus'         => $newStatus,
            'skNumber'          => $skNumber,
            'pdf_urls'          => $pdfUrls,
            'drive_folder_url'  => $driveResult['folder_url'] ?? null,
            'docs_uploaded'     => count($driveResult['uploaded'] ?? []),
            'drive_upload_success' => $driveResult['success'] ?? true,
            'drive_upload_error'   => $driveResult['message'] ?? null,
        ];
    }

    /**
     * Process Off Contract for Contract/PKWT employees (1:1 with GAS backend/Employee.gs).
     */
    public function processOffContract(string $employeeId, array $data, ?string $user = null): array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan dengan ID {$employeeId} tidak ditemukan.");
        }

        // STEP 16/18/22: active-probation employees are excluded from Off Contract.
        // Canonical active-probation determination — see ProbationService.
        $this->throwIfOnProbation($employeeId, 'Off Contract');

        $user = $user ?: 'HR Administrator';
        $now = now()->timezone('Asia/Jakarta');
        $nowStr = $now->format('Y-m-d H:i:s');
        $lastDate = $data['last_working_date'] ?? ($employee->endDateContract ?: $now->format('Y-m-d'));

        $attributes = [
            'Status Employee'          => 'Contract Finished',
            'End Date (Contract)'      => $lastDate,
            'Offboarding Type'         => 'Contract Finished',
            'Offboarding Reason'       => $data['reason'] ?? 'Kontrak PKWT berakhir dan tidak diperpanjang',
            'Offboarding Approved By'  => $data['approved_by'] ?? $user,
            'BPJS Ketenagakerjaan'     => $data['bpjs_tk'] ?? $employee->bpjsKetenagakerjaan,
            'BPJS Kesehatan'          => $data['bpjs_kes'] ?? $employee->bpjsKesehatan,
            'Updated At'               => $nowStr,
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                entityType: 'Employee',
                entityId: $employeeId,
                action: 'OFF_CONTRACT',
                field: 'Status Employee',
                oldValue: $employee->statusEmployee,
                newValue: 'Contract Finished' . (($data['notes'] ?? '') ? ' | ' . trim($data['notes']) : ''),
                user: $user,
                source: 'Dashboard'
            );
        }

        $extraData = [
            'effective_date' => $lastDate,
            'last_working_date' => $lastDate,
            'sk_number' => $employee->nomorSk ?? '',
            'notes' => $data['notes'] ?? '',
            'approved_by' => $data['approved_by'] ?? $user,
        ];

        return [
            'success' => $success,
            'message' => $success ? "Off Contract karyawan {$employeeId} berhasil diproses." : "Gagal memproses Off Contract.",
            'employeeId' => $employeeId,
        ];
    }

    // ==========================================================
    // Probation status helpers + restrictions
    //
    // Canonical source of truth for "is this employee currently in an
    // active probation process" lives in ProbationService::isActiveProbation().
    // Employee.Status determines the current Contract population; the
    // kandidat_probation sheet contributes evaluation history only.
    // ==========================================================

    /**
        * Server-side source of truth: whether the employee is in an ACTIVE
        * probation process. Delegates to ProbationService so all callers share
        * one canonical rule based on Employee.Status plus evaluation history.
     */
    public function isOnProbation(string $employeeId): bool
    {
        return $this->probationService()->isActiveProbation($employeeId);
    }

    /**
     * Reject with a RuntimeException when the employee is on active
     * probation. Central helper used by processRotation / processOffContract.
     */
    private function throwIfOnProbation(string $employeeId, string $action): void
    {
        if ($this->probationService()->isActiveProbation($employeeId)) {
            throw new \RuntimeException(
                "Employee ini sedang dalam proses probation. Selesaikan evaluasi probation terlebih dahulu sebelum melakukan {$action}."
            );
        }
    }

    // ── Contract-duration derivation (STEP 5 / STEP 13) ──────────────────

    /**
     * Derive the contract duration in whole calendar months from
     * Join Date → End Date (Contract). This is employee-specific — it must
     * NOT be a hardcoded universal value.
     *
     * Canonical PKWT end-date convention (matches the contract PDF
     * `kontrak-pkwt.blade.php` line 354):
     *
     *     End = JoinDate + N months − 1 day
     *
     * So End stored as the last day of the N-th calendar month. Examples:
     *     01 Jan 2026  →  30 Jun 2026   =  6 Bulan
     *     01 Feb 2026  →  31 Jul 2026   =  6 Bulan
     *     01 May 2026  →  31 Oct 2026   =  6 Bulan
     *     01 May 2026  →  01 Nov 2026   =  6 Bulan  (first day of next month)
     *
     * Algorithm: round-trip count. Find the largest N such that
     *     start.addMonthsNoOverflow(N) <= end + 1 day
     * i.e. the contract is N full calendar months long.
     */
    private function deriveContractDurationMonths(?string $joinDate, ?string $endContract): int
    {
        if (!$joinDate || !$endContract) {
            return 0;
        }
        try {
            $start = Carbon::parse($joinDate)->startOfDay();
            $end   = Carbon::parse($endContract)->startOfDay();
        } catch (\Throwable) {
            return 0;
        }
        if ($end->lessThan($start)) {
            return 0;
        }

        // Per the PKWT convention, End = Start + N months − 1 day, which is
        // equivalent to "End + 1 day = Start + N months".
        $anchor = $end->copy()->addDay();
        $n = ($anchor->year - $start->year) * 12 + ($anchor->month - $start->month);
        // Adjust for any day-of-month drift (start.day != 1) so we round-trip
        // through addMonthsNoOverflow and find the largest N that does not
        // overshoot the anchor.
        while ($n > 0 && $start->copy()->addMonthsNoOverflow($n)->greaterThan($anchor)) {
            $n--;
        }
        return $n > 0 ? $n : 0;
    }

    /**
     * Convert a whole-month count into the human label stored in the sheet,
     * matching the existing 1:1 GAS label conventions (3 Bulan / 6 Bulan /
     * 12 Bulan, plus any other value).
     */
    private function durationToLabel(int $months): string
    {
        return match ($months) {
            1  => '1 Bulan',
            3  => '3 Bulan',
            6  => '6 Bulan',
            12 => '12 Bulan',
            default => $months . ' Bulan',
        };
    }

    /**
     * Add a whole-month duration to a start date (GMT+7) and return YYYY-MM-DD.
     * Mirrors the JS calcExtendEnd() used in the evaluation modal so server
     * and client agree on the resulting end date.
     */
    private function addMonthsDate(string $startDate, int $months): string
    {
        try {
            return Carbon::parse($startDate)->addMonthsNoOverflow($months)
                ->timezone('Asia/Jakarta')->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
