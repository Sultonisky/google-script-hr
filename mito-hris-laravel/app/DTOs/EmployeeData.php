<?php

namespace App\DTOs;

class EmployeeData
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $fullName = null,
        public ?string $branchName = null,
        public ?string $division = null,
        public ?string $department = null,
        public ?string $jobPositionLocation = null,
        public ?string $jobPosition = null,
        public ?string $areaKerja = null,
        public ?string $lokasiKerja = null,
        public ?string $jobLevel = null,
        public ?string $grade = null,
        public ?string $joinDate = null,
        public ?string $statusEmployee = 'Contract',
        public ?string $directSuperior = null,
        public ?string $indirectSuperior = null,
        public ?string $personalEmail = null,
        public ?string $workingEmail = null,
        public ?string $endDateContract = null,
        public ?string $birthPlace = null,
        public ?string $birthDate = null,
        public ?string $citizenIdAddress = null,
        public ?string $residentialAddress = null,
        public ?string $nikNpwp = null,
        public ?string $npwp = null,
        public ?string $ptkpStatus = null,
        public ?string $bankName = null,
        public ?string $bankAccount = null,
        public ?string $bankAccountHolder = null,
        public ?string $bpjsKetenagakerjaan = null,
        public ?string $bpjsKesehatan = null,
        public ?string $mobilePhone = null,
        public ?string $religion = null,
        public ?string $gender = null,
        public ?string $maritalStatus = null,
        public ?string $bloodType = null,
        public ?string $costCenter = null,
        public ?string $jobPositionFormer = null,
        public ?string $typeOfRotation = null,
        public ?string $rotationDate = null,
        public ?string $nomorSk = null,
        public ?string $resignDate = null,
        public ?string $hrNotes = null,
        public ?string $offboardingType = null,
        public ?string $offboardingReason = null,
        public ?string $offboardingApprovedBy = null,
        public ?string $offboardingDocsFolder = null,
        public ?string $offboardingDocLinks = null,
        public ?string $outsourceVendor = null,
        public ?int $outsourceContractSeq = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $rowNumber = null,
        // -- Enrichment (tidak disimpan di sheet Employee) — diisi runtime dari
        //    kandidat_probation untuk kolom "Score / Kategori" & status evaluasi di tabel probation.
        // Performance Review 2026 (indicator-based)
        public ?string $lastCategory = null,
        public ?string $lastOverallTotal = null,
        // Shared
        public ?string $lastDecision = null,
        public ?string $lastEvalDate = null,
        public ?string $lastEvaluator = null
    ) {}

    public static function fromSheetRow(array $row): self
    {
        return new self(
            employeeId: $row['Employee ID'] ?? null,
            fullName: $row['Full Name'] ?? null,
            branchName: $row['Branch Name'] ?? null,
            division: $row['Division'] ?? null,
            department: $row['Department'] ?? null,
            jobPositionLocation: $row['Job Position (Location)'] ?? $row['Job Position (Locaction)'] ?? null,
            jobPosition: $row['Job Position'] ?? null,
            areaKerja: $row['Area Kerja'] ?? null,
            lokasiKerja: $row['Lokasi Kerja'] ?? null,
            jobLevel: $row['Job Level'] ?? null,
            grade: $row['Grade'] ?? null,
            joinDate: $row['Join Date'] ?? null,
            statusEmployee: $row['Status Employee'] ?? 'PKWT',
            directSuperior: $row['Direct Superior'] ?? null,
            indirectSuperior: $row['Indirect Superior'] ?? null,
            personalEmail: $row['Personal Email'] ?? null,
            workingEmail: $row['Working Email'] ?? null,
            endDateContract: $row['End Date (Contract)'] ?? null,
            birthPlace: $row['Birth Place'] ?? null,
            birthDate: $row['Birth Date'] ?? null,
            citizenIdAddress: $row['Citizen ID Address'] ?? null,
            residentialAddress: $row['Residential Address'] ?? null,
            nikNpwp: self::sanitizeNumberText($row['NIK - NPWP 16 digit'] ?? ''),
            npwp: self::sanitizeNumberText($row['NPWP'] ?? ''),
            ptkpStatus: $row['PTKP Status'] ?? null,
            bankName: $row['Bank Name'] ?? null,
            bankAccount: self::sanitizeNumberText($row['Bank Account'] ?? ''),
            bankAccountHolder: $row['Bank Account Holder'] ?? null,
            bpjsKetenagakerjaan: self::sanitizeNumberText($row['BPJS Ketenagakerjaan'] ?? ''),
            bpjsKesehatan: self::sanitizeNumberText($row['BPJS Kesehatan'] ?? ''),
            mobilePhone: self::sanitizeNumberText($row['Mobile Phone'] ?? ''),
            religion: $row['Religion'] ?? null,
            gender: $row['Gender'] ?? null,
            maritalStatus: $row['Marital Status'] ?? null,
            bloodType: $row['Blood Type'] ?? null,
            costCenter: $row['Cost Center'] ?? null,
            jobPositionFormer: $row['Job Position (Former)'] ?? null,
            typeOfRotation: $row['Type of Rotation'] ?? null,
            rotationDate: $row['Tanggal Mutasi/Demosi/Promosi'] ?? null,
            nomorSk: $row['Nomor SK'] ?? null,
            resignDate: $row['Resign Date'] ?? null,
            hrNotes: $row['HR Notes'] ?? null,
            offboardingType: $row['Offboarding Type'] ?? null,
            offboardingReason: $row['Offboarding Reason'] ?? null,
            offboardingApprovedBy: $row['Offboarding Approved By'] ?? null,
            offboardingDocsFolder: $row['Offboarding Documents Folder'] ?? null,
            offboardingDocLinks: $row['Offboarding Document Links'] ?? null,
            outsourceVendor: $row['Outsource Vendor'] ?? null,
            outsourceContractSeq: isset($row['Outsource Contract Seq']) && $row['Outsource Contract Seq'] !== ''
                ? (int) $row['Outsource Contract Seq']
                : null,
            createdBy: $row['Created By'] ?? null,
            createdAt: $row['Created At'] ?? null,
            updatedAt: $row['Updated At'] ?? null,
            rowNumber: $row['_row_number'] ?? null
        );
    }

    public function toSheetRow(): array
    {
        return [
            $this->employeeId ?? '',
            $this->fullName ?? '',
            $this->branchName ?? '',
            $this->division ?? '',
            $this->department ?? '',
            $this->jobPositionLocation ?? '',
            $this->jobPosition ?? '',
            $this->areaKerja ?? '',
            $this->lokasiKerja ?? '',
            $this->jobLevel ?? '',
            $this->grade ?? '',
            $this->joinDate ?? '',
            $this->statusEmployee ?? 'Contract',
            $this->directSuperior ?? '',
            $this->indirectSuperior ?? '',
            $this->personalEmail ?? '',
            $this->workingEmail ?? '',
            $this->endDateContract ?? '',
            $this->birthPlace ?? '',
            $this->birthDate ?? '',
            $this->citizenIdAddress ?? '',
            $this->residentialAddress ?? '',
            "'" . ($this->nikNpwp ?? ''),
            "'" . ($this->npwp ?? ''),
            $this->ptkpStatus ?? '',
            $this->bankName ?? '',
            "'" . ($this->bankAccount ?? ''),
            $this->bankAccountHolder ?? '',
            "'" . ($this->bpjsKetenagakerjaan ?? ''),
            "'" . ($this->bpjsKesehatan ?? ''),
            "'" . ($this->mobilePhone ?? ''),
            $this->religion ?? '',
            $this->gender ?? '',
            $this->maritalStatus ?? '',
            $this->bloodType ?? '',
            $this->costCenter ?? '',
            $this->jobPositionFormer ?? '',
            $this->typeOfRotation ?? '',
            $this->rotationDate ?? '',
            $this->nomorSk ?? '',
            $this->resignDate ?? '',
            $this->hrNotes ?? '',
            $this->offboardingType ?? '',
            $this->offboardingReason ?? '',
            $this->offboardingApprovedBy ?? '',
            $this->offboardingDocsFolder ?? '',
            $this->offboardingDocLinks ?? '',
            $this->outsourceVendor ?? '',
            $this->createdBy ?? 'HR Administrator',
            $this->createdAt ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $this->updatedAt ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $this->outsourceContractSeq !== null ? (string) $this->outsourceContractSeq : '',
        ];
    }

    /**
     * Sanitize number-as-text fields (NIK, Phone, NPWP, BPJS, Bank Account) yang 
     * mungkin dalam format scientific notation atau dengan prefix apostrophe.
     * 
     * Input:  "3.33E+15" atau "'3330000000000000" atau "3330000000000000"
     * Output: "3330000000000000"
     */
    private static function sanitizeNumberText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $cleaned = ltrim(trim($value), "'");

        // Deteksi scientific notation (mengandung E+ atau e+)
        if (stripos($cleaned, 'E') !== false) {
            // Konversi dari scientific notation ke integer string
            $number = floatval($cleaned);
            if (!is_nan($number) && $number > 0) {
                // sprintf dengan %.0f untuk menghindari scientific notation
                $cleaned = sprintf('%.0f', $number);
            }
        }

        return $cleaned;
    }
}
