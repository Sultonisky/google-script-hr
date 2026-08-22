<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeIdGenerator;
use Illuminate\Support\Collection;
use RuntimeException;

class EmployeeService
{
    protected EmployeeRepositoryInterface $employeeRepo;
    protected AuditLogRepositoryInterface $auditRepo;
    protected EmployeeIdGenerator $idGenerator;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepo,
        AuditLogRepositoryInterface $auditRepo,
        EmployeeIdGenerator $idGenerator
    ) {
        $this->employeeRepo = $employeeRepo;
        $this->auditRepo = $auditRepo;
        $this->idGenerator = $idGenerator;
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
                jobPositionLocation: trim($row['jobPositionLocation'] ?? $row['positionCurrent'] ?? $row['posisi_lokasi'] ?? ''),
                jobPosition: trim($row['jobPosition'] ?? $row['position'] ?? $row['jabatan'] ?? ''),
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
                costCenter: trim($row['costCenter'] ?? ''),
                jobPositionFormer: trim($row['positionFormer'] ?? ''),
                typeOfRotation: trim($row['typeOfRotation'] ?? $row['jenisRotasi'] ?? ''),
                rotationDate: trim($row['rotationDate'] ?? $row['mutasiDate'] ?? ''),
                nomorSk: trim($row['nomorSk'] ?? ''),
                resignDate: trim($row['resignDate'] ?? $row['tanggalResign'] ?? ''),
                hrNotes: trim($row['hrNotes'] ?? $row['notes'] ?? $row['catatan'] ?? ''),
                offboardingType: trim($row['offboardingType'] ?? ''),
                offboardingReason: trim($row['offboardingReason'] ?? ''),
                offboardingApprovedBy: trim($row['offboardingApprovedBy'] ?? ''),
                outsourceVendor: trim($row['outsourceVendor'] ?? $row['vendor'] ?? ''),
                createdBy: $user,
                createdAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                updatedAt: now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            );

            $this->employeeRepo->create($employee);
            $imported++;

            $this->auditRepo->log(
                recruitmentId: $empId,
                action: 'Import',
                field: 'Status Employee',
                oldValue: '-',
                newValue: $employee->statusEmployee ?? 'Active',
                user: $user
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

        $skNumber = trim($data['sk_number'] ?? '');
        if ($skNumber === '') {
            $branchPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $newBranch ?: $oldBranch ?: 'MITO'), 0, 6));
            $romanMonth   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
            $datePart     = $now->format('Ym');
            $cacheKey     = "SK_ROT_COUNTER_{$datePart}";
            $lock         = \Illuminate\Support\Facades\Cache::lock("lock_{$cacheKey}", 10);
            try {
                $lock->block(10);
                $seq = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0) + 1;
                \Illuminate\Support\Facades\Cache::put($cacheKey, $seq, $now->endOfMonth());
            } finally {
                $lock->release();
            }
            $skNumber = sprintf('%03d/HRD-SK/%s/%s/%d', $seq, $branchPrefix, $romanMonth[$now->month - 1], $now->year);
        }

        $noteLine = sprintf('[Rotasi %s] %s → %s', $rotationType, $oldPosition, $newPosition);
        if (($newDepartment !== $oldDepartment) && $oldDepartment && $newDepartment) {
            $noteLine .= sprintf(' | Dept: %s → %s', $oldDepartment, $newDepartment);
        }
        $noteLine .= sprintf(' | Efektif: %s', $effectiveDate);
        if (trim($notesRaw) !== '') {
            $noteLine .= sprintf(' | %s', trim($notesRaw));
        }
        $hrNotes = ($employee->hrNotes ? $employee->hrNotes . "\n" : '') . "[$nowStr] $noteLine";

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
            'HR Notes'                     => $hrNotes,
            'Updated At'                   => $nowStr,
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                recruitmentId: $employeeId,
                action: 'ROTATION_' . strtoupper($rotationType),
                field: 'Job Position / Dept',
                oldValue: "{$oldPosition} ({$oldDepartment})",
                newValue: "{$newPosition} ({$newDepartment}) | SK: {$skNumber}",
                user: $user
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
     */
    public function processOffboarding(string $employeeId, array $data, ?string $user = null): bool
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan dengan ID {$employeeId} tidak ditemukan.");
        }

        $user = $user ?: 'HR Administrator';
        $nowStr = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $offboardingType = $data['offboarding_type'] ?? 'Resigned';

        $attributes = [
            'Status Employee'          => $offboardingType,
            'Resign Date'              => $data['effective_date'] ?? now()->timezone('Asia/Jakarta')->format('Y-m-d'),
            'Offboarding Type'         => $offboardingType,
            'Offboarding Reason'       => $data['reason'] ?? '',
            'Offboarding Approved By'  => $user,
            'HR Notes'                 => $data['notes'] ?? $employee->hrNotes,
            'Updated At'               => $nowStr,
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                recruitmentId: $employeeId,
                action: 'OFFBOARDING_' . strtoupper($offboardingType),
                field: 'Status Employee',
                oldValue: $employee->statusEmployee,
                newValue: $offboardingType,
                user: $user
            );
        }

        return $success;
    }

    /**
     * Process Off Contract for Contract/PKWT employees (1:1 with GAS backend/Employee.gs).
     */
    public function processOffContract(string $employeeId, array $data, ?string $user = null): bool
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            throw new RuntimeException("Karyawan dengan ID {$employeeId} tidak ditemukan.");
        }

        $user = $user ?: 'HR Administrator';
        $nowStr = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $lastDate = $data['last_working_date'] ?? ($employee->endDateContract ?: now()->timezone('Asia/Jakarta')->format('Y-m-d'));

        $attributes = [
            'Status Employee'          => 'Contract Finished',
            'End Date (Contract)'      => $lastDate,
            'Offboarding Type'         => 'Contract Finished',
            'Offboarding Reason'       => $data['reason'] ?? 'Kontrak PKWT berakhir dan tidak diperpanjang',
            'Offboarding Approved By'  => $data['approved_by'] ?? $user,
            'BPJS Ketenagakerjaan'     => $data['bpjs_tk'] ?? $employee->bpjsKetenagakerjaan,
            'BPJS Kesehatan'          => $data['bpjs_kes'] ?? $employee->bpjsKesehatan,
            'HR Notes'                 => $data['notes'] ?? $employee->hrNotes,
            'Updated At'               => $nowStr,
        ];

        $success = $this->employeeRepo->update($employeeId, $attributes);

        if ($success) {
            $this->auditRepo->log(
                recruitmentId: $employeeId,
                action: 'OFF_CONTRACT',
                field: 'Status Employee',
                oldValue: $employee->statusEmployee,
                newValue: 'Contract Finished',
                user: $user
            );
        }

        return $success;
    }
}
