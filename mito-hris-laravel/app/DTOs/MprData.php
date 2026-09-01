<?php

namespace App\DTOs;

class MprData
{
    public function __construct(
        public ?string $mprNumber = null,
        public ?string $requestDate = null,
        // Requestor fields (canonical)
        public ?string $requestorName = null,
        public ?string $requestorEmail = null,
        // Entity = company yang dipilih Manager dari daftar entity-nya
        public ?string $entity = null,
        // Branch = lokasi/cabang milik authenticated requestor (fixed, tidak bisa diubah user)
        public ?string $branch = null,
        // -- Detail posisi --
        public ?string $department = null,
        public ?string $division = null,
        public ?string $position = null,
        public ?string $jobLevel = null,
        public ?string $workLocation = null,
        public ?string $employmentType = null,
        public ?int    $quantity = 1,
        public ?string $expectedJoinDate = null,
        public ?string $reason = null,
        public ?string $replacementFor = null,
        public ?string $jobDescription = null,
        public ?string $requirements = null,
        public ?string $notes = null,
        public ?string $status = 'Submitted',
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        // -- Refactor Create MPR: field baru (backward compatible, nullable) --
        public ?string $requestorPosition = null,
        public ?string $grade = null,
        public ?string $workArea = null,
        public ?string $workingDays = null,   // multi-select, disimpan sebagai label dipisah ", "
        public ?string $workingHours = null,  // multi-select, disimpan sebagai label dipisah ", "
        public ?string $shiftDetail = null,   // free text, wajib hanya jika shifting dipilih
        public ?string $benefits = null,      // multi-select, disimpan sebagai label dipisah ", "
        public ?string $educationBackground = null, // single selection
        public ?string $workExperience = null,      // single selection
        public ?string $skillsCompetencies = null,
        public ?string $languages = null,
        public ?string $industryReference = null,
        public ?string $specialNotes = null,
        public ?string $keyResultsTargets = null,
    ) {}

    // -------------------------------------------------------------------------
    // Backward-compat aliases so existing code using ->managerName etc. still works
    // -------------------------------------------------------------------------
    public function __get(string $name): mixed
    {
        return match ($name) {
            'managerName'  => $this->requestorName, // legacy alias
            'managerEmail' => $this->requestorEmail, // legacy alias
            'company'      => $this->entity, // legacy alias
            default        => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['managerName', 'managerEmail', 'company'], true);
    }

    // -------------------------------------------------------------------------
    // Hydrate from Google Sheet row
    // -------------------------------------------------------------------------
    public static function fromSheetRow(array $row): self
    {
        // Support both old column names (Manager Name / Company) and new ones (Requestor Name / Entity)
        $requestorName  = $row['Requestor Name']  ?? $row['Manager Name']  ?? null; // 'Manager Name' is legacy
        $requestorEmail = $row['Requestor Email'] ?? $row['Manager Email'] ?? null; // 'Manager Email' is legacy
        $entity         = $row['Entity']           ?? $row['Company']       ?? null; // 'Company' is legacy
        $branch         = $row['Branch']           ?? null;

        return new self(
            mprNumber:       $row['MPR Number']         ?? null,
            requestDate:     $row['Request Date']       ?? null,
            requestorName:   $requestorName,
            requestorEmail:  $requestorEmail,
            entity:          $entity,
            branch:          $branch,
            department:      $row['Department']         ?? null,
            division:        $row['Division']           ?? null,
            position:        $row['Position']           ?? null,
            jobLevel:        $row['Job Level']          ?? null,
            workLocation:    $row['Work Location']      ?? null,
            employmentType:  $row['Employment Type']    ?? null,
            quantity:        isset($row['Quantity']) && $row['Quantity'] !== '' ? (int) $row['Quantity'] : 1,
            expectedJoinDate: $row['Expected Join Date'] ?? null,
            reason:          $row['Reason']             ?? null,
            replacementFor:  $row['Replacement For']    ?? null,
            jobDescription:  $row['Job Description']    ?? null,
            requirements:    $row['Requirements']       ?? null,
            notes:           $row['Notes']              ?? null,
            status:          $row['Status']             ?? 'Submitted',
            createdBy:       $row['Created By']         ?? null,
            createdAt:       $row['Created At']         ?? null,
            updatedAt:       $row['Updated At']         ?? null,
            // -- Field baru: safe fallback null untuk row lama yang belum punya kolom --
            requestorPosition:    $row['Requestor Position']    ?? null,
            grade:                $row['Grade']                 ?? null,
            workArea:             $row['Work Area']             ?? null,
            workingDays:          $row['Working Days']          ?? null,
            workingHours:         $row['Working Hours']         ?? null,
            shiftDetail:          $row['Shift Detail']          ?? null,
            benefits:             $row['Benefits']              ?? null,
            educationBackground:  $row['Education Background']  ?? null,
            workExperience:       $row['Work Experience']       ?? null,
            skillsCompetencies:   $row['Skills / Competencies'] ?? null,
            languages:            $row['Languages']             ?? null,
            industryReference:    $row['Industry Reference']    ?? null,
            specialNotes:         $row['Special Notes']         ?? null,
            keyResultsTargets:    $row['Key Results / Targets'] ?? null,
        );
    }

    // -------------------------------------------------------------------------
    // Serialize to sheet row (must match config/hris.php schemas.MPR order)
    // -------------------------------------------------------------------------
    public function toSheetRow(): array
    {
        return [
            'MPR Number'         => (string) ($this->mprNumber       ?? ''),
            'Request Date'       => (string) ($this->requestDate      ?? ''),
            'Requestor Name'     => (string) ($this->requestorName    ?? ''),
            'Requestor Email'    => (string) ($this->requestorEmail   ?? ''),
            'Entity'             => (string) ($this->entity           ?? ''),
            'Branch'             => (string) ($this->branch           ?? ''),
            'Department'         => (string) ($this->department       ?? ''),
            'Division'           => (string) ($this->division         ?? ''),
            'Position'           => (string) ($this->position         ?? ''),
            'Job Level'          => (string) ($this->jobLevel         ?? ''),
            'Work Location'      => (string) ($this->workLocation     ?? ''),
            'Employment Type'    => (string) ($this->employmentType   ?? ''),
            'Quantity'           => (string) ($this->quantity         ?? 1),
            'Expected Join Date' => (string) ($this->expectedJoinDate ?? ''),
            'Reason'             => (string) ($this->reason           ?? ''),
            'Replacement For'    => (string) ($this->replacementFor   ?? ''),
            'Job Description'    => (string) ($this->jobDescription   ?? ''),
            'Requirements'       => (string) ($this->requirements     ?? ''),
            'Notes'              => (string) ($this->notes            ?? ''),
            'Status'             => (string) ($this->status           ?? 'Submitted'),
            'Created By'         => (string) ($this->createdBy        ?? ''),
            'Created At'         => (string) ($this->createdAt        ?? ''),
            'Updated At'         => (string) ($this->updatedAt        ?? ''),
            'Requestor Position'      => (string) ($this->requestorPosition   ?? ''),
            'Grade'                   => (string) ($this->grade               ?? ''),
            'Work Area'               => (string) ($this->workArea            ?? ''),
            'Working Days'            => (string) ($this->workingDays         ?? ''),
            'Working Hours'           => (string) ($this->workingHours        ?? ''),
            'Shift Detail'            => (string) ($this->shiftDetail         ?? ''),
            'Benefits'                => (string) ($this->benefits            ?? ''),
            'Education Background'    => (string) ($this->educationBackground ?? ''),
            'Work Experience'         => (string) ($this->workExperience      ?? ''),
            'Skills / Competencies'   => (string) ($this->skillsCompetencies  ?? ''),
            'Languages'               => (string) ($this->languages           ?? ''),
            'Industry Reference'      => (string) ($this->industryReference   ?? ''),
            'Special Notes'           => (string) ($this->specialNotes        ?? ''),
            'Key Results / Targets'   => (string) ($this->keyResultsTargets   ?? ''),
        ];
    }

    // -------------------------------------------------------------------------
    // Serialize to array (for JSON responses & frontend)
    // -------------------------------------------------------------------------
    public function toArray(): array
    {
        return [
            'mpr_number'         => $this->mprNumber,
            'request_date'       => $this->requestDate,
            'requestor_name'     => $this->requestorName,
            'requestor_email'    => $this->requestorEmail,
            // Keep legacy keys so existing frontend JS still works
            'manager_name'       => $this->requestorName,
            'manager_email'      => $this->requestorEmail,
            'entity'             => $this->entity,
            'branch'             => $this->branch,
            // Keep legacy key so existing templates still work
            'company'            => $this->entity,
            'department'         => $this->department,
            'division'           => $this->division,
            'position'           => $this->position,
            'job_level'          => $this->jobLevel,
            'work_location'      => $this->workLocation,
            'employment_type'    => $this->employmentType,
            'quantity'           => $this->quantity,
            'expected_join_date' => $this->expectedJoinDate,
            'reason'             => $this->reason,
            'replacement_for'    => $this->replacementFor,
            'job_description'    => $this->jobDescription,
            'requirements'       => $this->requirements,
            'notes'              => $this->notes,
            'status'             => $this->status,
            'created_by'         => $this->createdBy,
            'created_at'         => $this->createdAt,
            'updated_at'         => $this->updatedAt,
            'requestor_position'    => $this->requestorPosition,
            'grade'                 => $this->grade,
            'work_area'             => $this->workArea,
            'working_days'          => $this->workingDays,
            'working_hours'         => $this->workingHours,
            'shift_detail'          => $this->shiftDetail,
            'benefits'              => $this->benefits,
            'education_background'  => $this->educationBackground,
            'work_experience'       => $this->workExperience,
            'skills_competencies'   => $this->skillsCompetencies,
            'languages'             => $this->languages,
            'industry_reference'    => $this->industryReference,
            'special_notes'         => $this->specialNotes,
            'key_results_targets'   => $this->keyResultsTargets,
        ];
    }
}
