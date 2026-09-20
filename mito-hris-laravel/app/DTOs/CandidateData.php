<?php

namespace App\DTOs;

class CandidateData
{
    public function __construct(
        public ?string $recruitmentId = null,
        public ?string $createdDate = null,
        public ?string $fullName = null,
        public ?string $nik = null,
        public ?string $birthDate = null,
        public ?string $age = null,
        public ?string $gender = null,
        public ?string $maritalStatus = null,
        public ?string $bloodType = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $positionApplied = null,
        public ?string $education = null,
        public ?string $workExperience = null,
        public ?string $lastCompany = null,
        public ?string $currentEmploymentStatus = null,
        public ?string $availableToJoin = null,
        public ?string $expectedSalary = null,
        public ?string $recruitmentSource = null,
        public ?string $cvLink = null,
        public ?string $status = 'Pending',
        public ?string $hrNotes = null,
        public ?string $createdBy = 'Candidate',
        public ?string $updatedAt = null,
        public ?string $holdReason = null,
        public ?string $holdFollowUpDate = null,
        public ?string $blacklistReason = null,
        public ?string $blacklistDate = null,
        public ?string $blacklistUpdatedBy = null,
        public ?string $employeeId = null,
        public ?string $processedDate = null,
        public ?string $processedBy = null,
        public ?string $offeringCreated = null,
        public ?string $offeringUpdated = null,
        public ?string $offeringCreatedBy = null,
        public ?string $offeringUpdatedBy = null,
        public ?string $offeringCompanyEntity = null,
        public ?string $offeringPosition = null,
        public ?string $offeringSalary = null,
        public ?string $offeringJoinDate = null,
        public ?string $offeringNotes = null,
        public ?string $offeringResponse = null,
        public ?string $offeringResponseNotes = null,
        public ?string $offeringResponseDate = null,
        public ?string $offeringResponseBy = null,
        public ?string $offeringDivision = null,
        public ?string $offeringJobLevel = null,
        public ?string $offeringLokasiKerja = null,
        public ?string $offeringSalaryBasic = null,
        public ?string $offeringAllowPulsa = null,
        public ?string $offeringAllowTransport = null,
        public ?string $offeringEmploymentStatus = null,
        public ?string $offeringContractDuration = null,
        public ?string $offeringWorkingHours = null,
        public ?string $onboardingStatus = null,
        public ?string $onboardingDate = null,
        public ?string $onboardingBy = null,
        public ?int $rowNumber = null
    ) {}

    public static function fromSheetRow(array $row): self
    {
        return new self(
            recruitmentId: $row['Recruitment ID'] ?? null,
            createdDate: $row['Created Date'] ?? null,
            fullName: $row['Full Name'] ?? null,
            nik: self::sanitizeNik($row['NIK'] ?? ''),
            birthDate: $row['Birth Date'] ?? null,
            age: $row['Age'] ?? null,
            gender: $row['Gender'] ?? null,
            maritalStatus: $row['Marital Status'] ?? null,
            bloodType: $row['Blood Type'] ?? null,
            email: $row['Email'] ?? null,
            phone: self::sanitizePhone($row['Phone'] ?? ''),
            address: $row['Address'] ?? null,
            city: $row['City'] ?? null,
            positionApplied: $row['Position Applied'] ?? null,
            education: $row['Education'] ?? null,
            workExperience: $row['Work Experience'] ?? null,
            lastCompany: $row['Last Company'] ?? null,
            currentEmploymentStatus: $row['Current Employment Status'] ?? null,
            availableToJoin: $row['Available to Join'] ?? null,
            expectedSalary: $row['Expected Salary'] ?? null,
            recruitmentSource: $row['Recruitment Source'] ?? null,
            cvLink: $row['CV Link'] ?? null,
            status: $row['Status'] ?? 'Pending',
            hrNotes: $row['HR Notes'] ?? null,
            createdBy: $row['Created By'] ?? null,
            updatedAt: $row['Updated At'] ?? null,
            holdReason: $row['Hold Reason'] ?? null,
            holdFollowUpDate: $row['Hold Follow Up Date'] ?? null,
            blacklistReason: $row['Blacklist Reason'] ?? null,
            blacklistDate: $row['Blacklist Date'] ?? null,
            blacklistUpdatedBy: $row['Blacklist Updated By'] ?? null,
            employeeId: $row['Employee ID'] ?? null,
            processedDate: $row['Processed Date'] ?? null,
            processedBy: $row['Processed By'] ?? null,
            offeringCreated: $row['Offering Created'] ?? null,
            offeringUpdated: $row['Offering Updated'] ?? null,
            offeringCreatedBy: $row['Offering Created By'] ?? null,
            offeringUpdatedBy: $row['Offering Updated By'] ?? null,
            offeringCompanyEntity: $row['Offering Company Entity'] ?? null,
            offeringPosition: $row['Offering Position'] ?? null,
            offeringSalary: $row['Offering Salary'] ?? null,
            offeringJoinDate: $row['Offering Join Date'] ?? null,
            offeringNotes: $row['Offering Notes'] ?? null,
            offeringResponse: $row['Offering Response'] ?? null,
            offeringResponseNotes: $row['Offering Response Notes'] ?? null,
            offeringResponseDate: $row['Offering Response Date'] ?? null,
            offeringResponseBy: $row['Offering Response By'] ?? null,
            offeringDivision: $row['Offering Division'] ?? null,
            offeringJobLevel: $row['Offering Job Level'] ?? null,
            offeringLokasiKerja: $row['Offering Lokasi Kerja'] ?? null,
            offeringSalaryBasic: $row['Offering Salary Basic'] ?? null,
            offeringAllowPulsa: $row['Offering Allow Pulsa']
                ?? $row['Offering Allowance Pulsa']
                ?? $row['Allow Pulsa']
                ?? null,
            offeringAllowTransport: $row['Offering Allow Transport'] ?? null,
            offeringEmploymentStatus: $row['Offering Employment Status'] ?? null,
            offeringContractDuration: $row['Offering Contract Duration'] ?? null,
            offeringWorkingHours: $row['Offering Working Hours'] ?? null,
            onboardingStatus: $row['Onboarding Status'] ?? null,
            onboardingDate: $row['Onboarding Date'] ?? null,
            onboardingBy: $row['Onboarding By'] ?? null,
            rowNumber: $row['_row_number'] ?? null
        );
    }

    /**
     * Build a positional row for data_kandidat (25 columns).
     *
     * Final schema (index → header):
     *   0  Recruitment ID           13 Education
     *   1  Created Date             14 Work Experience
     *   2  Full Name                15 Last Company
     *   3  NIK                      16 Current Employment Status
     *   4  Birth Date               17 Available to Join
     *   5  Age                      18 Expected Salary
     *   6  Gender                   19 Expected Salary
     *   7  Blood Type               20 Recruitment Source
     *   8  Marital Status           21 Status
     *   9  Email                    22 HR Notes
     *  10  Phone                    23 Created By
     *  11  Address                  24 Updated At
     *  12  City
     *  13  Position Applied
     *  14  Education
     *  15  Work Experience
     *  16  Last Company
     *  17  Current Employment Status
     *  18  Available to Join
     *
     * Blood Type sits after Gender, matching config/hris.php schemas.data_kandidat.
     *
     * NOTE: CV Link has been removed from this sheet.
     * Pipeline columns (Hold Reason, Blacklist Reason, Employee ID, etc.)
     * are NOT written to data_kandidat; they live only in their
     * respective destination sheets (kandidat_hold / kandidat_blacklist /
     * kandidat_accepted).
     */
    public function toSheetRow(): array
    {
        return [
            $this->recruitmentId ?? '',
            $this->createdDate ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $this->fullName ?? '',
            "'" . ($this->nik ?? ''),
            $this->birthDate ?? '',
            $this->age ?? '',
            $this->gender ?? '',
            $this->bloodType ?? '',
            $this->maritalStatus ?? '',
            $this->email ?? '',
            "'" . ($this->phone ?? ''),
            $this->address ?? '',
            $this->city ?? '',
            $this->positionApplied ?? '',
            $this->education ?? '',
            $this->workExperience ?? '',
            $this->lastCompany ?? '',
            $this->currentEmploymentStatus ?? '',
            $this->availableToJoin ?? '',
            $this->expectedSalary ?? '',
            $this->recruitmentSource ?? '',
            $this->status ?? 'Pending',
            $this->hrNotes ?? '',
            $this->createdBy ?? 'Candidate',
            $this->updatedAt ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Sanitize NIK yang mungkin dalam format scientific notation atau dengan prefix apostrophe.
     * Input: "3.33E+15" atau "'3330000000000000" atau "3330000000000000"
     * Output: "3330000000000000"
     */
    private static function sanitizeNik(?string $value): ?string
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

    /**
     * Sanitize Phone yang mungkin dengan prefix apostrophe.
     * Input: "'081234567890" atau "081234567890"
     * Output: "081234567890"
     */
    private static function sanitizePhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return ltrim(trim($value), "'");
    }
}
