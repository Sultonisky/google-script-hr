<?php

namespace App\Support;

use App\DTOs\CandidateData;
use App\Models\Candidate;

/**
 * Maps Candidate DTO ↔ Eloquent ↔ Sheets header keys.
 * Sheet tab location becomes lifecycle_status on a single candidates table.
 */
final class CandidateAttributeMap
{
    public const LIFECYCLE_PENDING = 'pending';
    public const LIFECYCLE_HOLD = 'hold';
    public const LIFECYCLE_BLACKLIST = 'blacklist';
    public const LIFECYCLE_ACCEPTED = 'accepted';
    public const LIFECYCLE_PROBATION = 'probation';

    /** @var array<string, string> config google.sheets key → lifecycle */
    public const BUCKET_TO_LIFECYCLE = [
        'candidates' => self::LIFECYCLE_PENDING,
        'candidates_hold' => self::LIFECYCLE_HOLD,
        'candidates_blacklist' => self::LIFECYCLE_BLACKLIST,
        'candidates_accepted' => self::LIFECYCLE_ACCEPTED,
        'candidates_probation' => self::LIFECYCLE_PROBATION,
    ];

    /** @var list<string> */
    public const OFFERING_HEADERS = [
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
        'Offering Division',
        'Offering Job Level',
        'Offering Lokasi Kerja',
        'Offering Salary Basic',
        'Offering Allow Pulsa',
        'Offering Allowance Pulsa',
        'Allow Pulsa',
        'Offering Allow Transport',
        'Offering Employment Status',
        'Offering Contract Duration',
        'Offering Working Hours',
    ];

    /** @var list<string> */
    public const EXTRA_HEADERS = [
        'CV Link',
        'Onboarding Status',
        'Onboarding Date',
        'Onboarding By',
    ];

    /** @var array<string, string> sheet header → column (non-JSON) */
    public const SHEET_TO_COLUMN = [
        'Recruitment ID' => 'recruitment_id',
        'Created Date' => 'created_date',
        'Full Name' => 'full_name',
        'NIK' => 'nik',
        'Birth Date' => 'birth_date',
        'Age' => 'age',
        'Gender' => 'gender',
        'Blood Type' => 'blood_type',
        'Marital Status' => 'marital_status',
        'Email' => 'email',
        'Phone' => 'phone',
        'Address' => 'address',
        'City' => 'city',
        'Position Applied' => 'position_applied',
        'Education' => 'education',
        'Work Experience' => 'work_experience',
        'Last Company' => 'last_company',
        'Current Employment Status' => 'current_employment_status',
        'Available to Join' => 'available_to_join',
        'Expected Salary' => 'expected_salary',
        'Recruitment Source' => 'recruitment_source',
        'Status' => 'status',
        'HR Notes' => 'hr_notes',
        'Created By' => 'created_by',
        'Hold Reason' => 'hold_reason',
        'Hold Follow Up Date' => 'hold_follow_up_date',
        'Blacklist Reason' => 'blacklist_reason',
        'Blacklist Date' => 'blacklist_date',
        'Blacklist Updated By' => 'blacklist_updated_by',
        'Employee ID' => 'employee_id',
        'Processed Date' => 'processed_date',
        'Processed By' => 'processed_by',
    ];

    public static function bucketToLifecycle(string $bucket): ?string
    {
        return self::BUCKET_TO_LIFECYCLE[$bucket] ?? null;
    }

    /**
     * Build Sheets-style associative row from DB model (for DB → Sheets mirror).
     *
     * @return array<string, string>
     */
    public static function toSheetAssoc(Candidate $model): array
    {
        $assoc = [];
        foreach (self::SHEET_TO_COLUMN as $header => $column) {
            $value = $model->{$column} ?? '';
            if ($value !== null && $value !== '' && in_array($column, ['nik', 'phone'], true)) {
                $assoc[$header] = "'".ltrim((string) $value, "'");
            } else {
                $assoc[$header] = (string) ($value ?? '');
            }
        }

        $offering = is_array($model->offering_payload) ? $model->offering_payload : [];
        $extra = is_array($model->extra_payload) ? $model->extra_payload : [];
        foreach (array_merge(self::OFFERING_HEADERS, self::EXTRA_HEADERS) as $header) {
            if (array_key_exists($header, $offering)) {
                $assoc[$header] = (string) $offering[$header];
            } elseif (array_key_exists($header, $extra)) {
                $assoc[$header] = (string) $extra[$header];
            }
        }

        $assoc['Updated At'] = optional($model->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '';

        return $assoc;
    }

    public static function toData(Candidate $model): CandidateData
    {
        $offering = is_array($model->offering_payload) ? $model->offering_payload : [];
        $extra = is_array($model->extra_payload) ? $model->extra_payload : [];

        return new CandidateData(
            recruitmentId: $model->recruitment_id,
            createdDate: $model->created_date,
            fullName: $model->full_name,
            nik: self::stripQuote($model->nik),
            birthDate: $model->birth_date,
            age: $model->age,
            gender: $model->gender,
            maritalStatus: $model->marital_status,
            bloodType: $model->blood_type,
            email: $model->email,
            phone: self::stripQuote($model->phone),
            address: $model->address,
            city: $model->city,
            positionApplied: $model->position_applied,
            education: $model->education,
            workExperience: $model->work_experience,
            lastCompany: $model->last_company,
            currentEmploymentStatus: $model->current_employment_status,
            availableToJoin: $model->available_to_join,
            expectedSalary: $model->expected_salary,
            recruitmentSource: $model->recruitment_source,
            cvLink: $extra['CV Link'] ?? null,
            status: $model->status ?? 'Pending',
            hrNotes: $model->hr_notes,
            createdBy: $model->created_by,
            updatedAt: optional($model->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            holdReason: $model->hold_reason,
            holdFollowUpDate: $model->hold_follow_up_date,
            blacklistReason: $model->blacklist_reason,
            blacklistDate: $model->blacklist_date,
            blacklistUpdatedBy: $model->blacklist_updated_by,
            employeeId: $model->employee_id,
            processedDate: $model->processed_date,
            processedBy: $model->processed_by,
            offeringCreated: $offering['Offering Created'] ?? null,
            offeringUpdated: $offering['Offering Updated'] ?? null,
            offeringCreatedBy: $offering['Offering Created By'] ?? null,
            offeringUpdatedBy: $offering['Offering Updated By'] ?? null,
            offeringCompanyEntity: $offering['Offering Company Entity'] ?? null,
            offeringPosition: $offering['Offering Position'] ?? null,
            offeringSalary: $offering['Offering Salary'] ?? null,
            offeringJoinDate: $offering['Offering Join Date'] ?? null,
            offeringNotes: $offering['Offering Notes'] ?? null,
            offeringResponse: $offering['Offering Response'] ?? null,
            offeringResponseNotes: $offering['Offering Response Notes'] ?? null,
            offeringResponseDate: $offering['Offering Response Date'] ?? null,
            offeringResponseBy: $offering['Offering Response By'] ?? null,
            offeringDivision: $offering['Offering Division'] ?? null,
            offeringJobLevel: $offering['Offering Job Level'] ?? null,
            offeringLokasiKerja: $offering['Offering Lokasi Kerja'] ?? null,
            offeringSalaryBasic: $offering['Offering Salary Basic'] ?? null,
            offeringAllowPulsa: $offering['Offering Allow Pulsa']
                ?? $offering['Offering Allowance Pulsa']
                ?? $offering['Allow Pulsa']
                ?? null,
            offeringAllowTransport: $offering['Offering Allow Transport'] ?? null,
            offeringEmploymentStatus: $offering['Offering Employment Status'] ?? null,
            offeringContractDuration: $offering['Offering Contract Duration'] ?? null,
            offeringWorkingHours: $offering['Offering Working Hours'] ?? null,
            onboardingStatus: $extra['Onboarding Status'] ?? null,
            onboardingDate: $extra['Onboarding Date'] ?? null,
            onboardingBy: $extra['Onboarding By'] ?? null,
            rowNumber: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toFillable(CandidateData $data, string $lifecycle = self::LIFECYCLE_PENDING): array
    {
        $offering = [];
        foreach (self::OFFERING_HEADERS as $header) {
            $value = self::dtoOfferingValue($data, $header);
            if ($value !== null && $value !== '') {
                $offering[$header === 'Offering Allowance Pulsa' || $header === 'Allow Pulsa'
                    ? 'Offering Allow Pulsa'
                    : $header] = $value;
            }
        }

        $extra = array_filter([
            'CV Link' => $data->cvLink,
            'Onboarding Status' => $data->onboardingStatus,
            'Onboarding Date' => $data->onboardingDate,
            'Onboarding By' => $data->onboardingBy,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'recruitment_id' => $data->recruitmentId,
            'lifecycle_status' => $lifecycle,
            'full_name' => $data->fullName,
            'nik' => self::stripQuote($data->nik),
            'birth_date' => $data->birthDate,
            'age' => $data->age,
            'gender' => $data->gender,
            'blood_type' => $data->bloodType,
            'marital_status' => $data->maritalStatus,
            'email' => $data->email,
            'phone' => self::stripQuote($data->phone),
            'address' => $data->address,
            'city' => $data->city,
            'position_applied' => $data->positionApplied,
            'education' => $data->education,
            'work_experience' => $data->workExperience,
            'last_company' => $data->lastCompany,
            'current_employment_status' => $data->currentEmploymentStatus,
            'available_to_join' => $data->availableToJoin,
            'expected_salary' => $data->expectedSalary,
            'recruitment_source' => $data->recruitmentSource,
            'status' => $data->status ?? 'Pending',
            'hr_notes' => $data->hrNotes,
            'created_by' => $data->createdBy ?? 'Candidate',
            'hold_reason' => $data->holdReason,
            'hold_follow_up_date' => $data->holdFollowUpDate,
            'blacklist_reason' => $data->blacklistReason,
            'blacklist_date' => $data->blacklistDate,
            'blacklist_updated_by' => $data->blacklistUpdatedBy,
            'employee_id' => $data->employeeId,
            'processed_date' => $data->processedDate,
            'processed_by' => $data->processedBy,
            'offering_payload' => $offering === [] ? null : $offering,
            'extra_payload' => $extra === [] ? null : $extra,
            'created_date' => $data->createdDate ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply Sheets-style attribute bag onto a Candidate model (mutates).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function applySheetAttributes(Candidate $model, array $attributes): void
    {
        $offering = is_array($model->offering_payload) ? $model->offering_payload : [];
        $extra = is_array($model->extra_payload) ? $model->extra_payload : [];

        foreach ($attributes as $key => $value) {
            if ($key === 'Offering Allowance Pulsa' || $key === 'Allow Pulsa') {
                $key = 'Offering Allow Pulsa';
            }

            if (isset(self::SHEET_TO_COLUMN[$key])) {
                $column = self::SHEET_TO_COLUMN[$key];
                if (in_array($column, ['nik', 'phone'], true)) {
                    $value = self::stripQuote(is_string($value) ? $value : (string) $value);
                }
                $model->{$column} = $value;

                continue;
            }

            if (in_array($key, self::OFFERING_HEADERS, true) || $key === 'Offering Allow Pulsa') {
                $offering[$key] = $value === null ? '' : (string) $value;

                continue;
            }

            if (in_array($key, self::EXTRA_HEADERS, true)) {
                $extra[$key] = $value === null ? '' : (string) $value;
            }
        }

        $model->offering_payload = $offering === [] ? null : $offering;
        $model->extra_payload = $extra === [] ? null : $extra;
    }

    private static function dtoOfferingValue(CandidateData $data, string $header): ?string
    {
        return match ($header) {
            'Offering Created' => $data->offeringCreated,
            'Offering Updated' => $data->offeringUpdated,
            'Offering Created By' => $data->offeringCreatedBy,
            'Offering Updated By' => $data->offeringUpdatedBy,
            'Offering Company Entity' => $data->offeringCompanyEntity,
            'Offering Position' => $data->offeringPosition,
            'Offering Salary' => $data->offeringSalary,
            'Offering Join Date' => $data->offeringJoinDate,
            'Offering Notes' => $data->offeringNotes,
            'Offering Response' => $data->offeringResponse,
            'Offering Response Notes' => $data->offeringResponseNotes,
            'Offering Response Date' => $data->offeringResponseDate,
            'Offering Response By' => $data->offeringResponseBy,
            'Offering Division' => $data->offeringDivision,
            'Offering Job Level' => $data->offeringJobLevel,
            'Offering Lokasi Kerja' => $data->offeringLokasiKerja,
            'Offering Salary Basic' => $data->offeringSalaryBasic,
            'Offering Allow Pulsa', 'Offering Allowance Pulsa', 'Allow Pulsa' => $data->offeringAllowPulsa,
            'Offering Allow Transport' => $data->offeringAllowTransport,
            'Offering Employment Status' => $data->offeringEmploymentStatus,
            'Offering Contract Duration' => $data->offeringContractDuration,
            'Offering Working Hours' => $data->offeringWorkingHours,
            default => null,
        };
    }

    private static function stripQuote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return ltrim(trim($value), "'");
    }
}
