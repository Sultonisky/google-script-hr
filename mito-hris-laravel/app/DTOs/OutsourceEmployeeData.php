<?php

namespace App\DTOs;

use App\Support\OutsourceEmployeeAttributeMap;

class OutsourceEmployeeData
{
    public function __construct(
        public ?string $outsourceId = null,
        public ?string $fullName = null,
        public ?string $citizenIdAddress = null,
        public ?string $birthDate = null,
        public ?string $birthPlace = null,
        public ?string $lastEducation = null,
        public ?string $whatsappNumber = null,
        public ?string $email = null,
        public ?string $jobTitle = null,
        public ?string $workLocation = null,
        public ?string $workCity = null,
        public ?string $bankAccount = null,
        public ?string $mitoJoinDate = null,
        public ?string $contractStartDate = null,
        public ?string $contractEndDate = null,
        public ?string $costCenter = null,
        public ?string $entity = null,
        public ?string $payrollScheme = null,
        public ?float $umkAmount = null,
        public ?float $basicSalary = null,
        public ?float $incentiveAmount = null,
        public ?string $remarks = null,
        public ?string $vendor = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $rowNumber = null,
    ) {}

    public static function fromSheetRow(array $row): self
    {
        return OutsourceEmployeeAttributeMap::fromSheetRow($row);
    }

    /** @return list<string> */
    public function toSheetRow(): array
    {
        return OutsourceEmployeeAttributeMap::toSheetRow($this);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = get_object_vars($this);
        unset($data['rowNumber']);

        return $data;
    }

    /**
     * Employee-shaped view for shared document pipelines (PKWT TAD PDFs, SK numbering).
     */
    public function toEmployeeData(): EmployeeData
    {
        return new EmployeeData(
            employeeId: $this->outsourceId,
            fullName: $this->fullName,
            branchName: $this->entity,
            jobPosition: $this->jobTitle,
            jobPositionLocation: $this->workLocation,
            lokasiKerja: $this->workCity,
            joinDate: $this->contractStartDate ?: $this->mitoJoinDate,
            statusEmployee: 'Outsource',
            personalEmail: $this->email,
            endDateContract: $this->contractEndDate,
            contractStart: $this->contractStartDate,
            birthPlace: $this->birthPlace,
            birthDate: $this->birthDate,
            citizenIdAddress: $this->citizenIdAddress,
            bankName: 'BCA',
            bankAccount: $this->bankAccount,
            mobilePhone: $this->whatsappNumber,
            costCenter: $this->costCenter,
            outsourceVendor: $this->vendor,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
