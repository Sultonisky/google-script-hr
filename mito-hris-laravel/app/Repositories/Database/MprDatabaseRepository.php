<?php

namespace App\Repositories\Database;

use App\DTOs\MprData;
use App\Models\MprRequest;
use App\Repositories\Contracts\MprRepositoryInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class MprDatabaseRepository implements MprRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        $query = MprRequest::query();

        if (!empty($filters['status'])) {
            $query->whereRaw('LOWER(TRIM(status)) = ?', [strtolower(trim((string) $filters['status']))]);
        }

        if (!empty($filters['department'])) {
            $query->whereRaw('LOWER(TRIM(department)) = ?', [strtolower(trim((string) $filters['department']))]);
        }

        if (!empty($filters['submit_by'])) {
            $query->whereRaw('LOWER(TRIM(requestor_name)) = ?', [strtolower(trim((string) $filters['submit_by']))]);
        }

        if (!empty($filters['company'])) {
            $company = '%' . strtolower(trim((string) $filters['company'])) . '%';
            $query->whereRaw('LOWER(COALESCE(entity, \'\')) LIKE ?', [$company]);
        }

        if (!empty($filters['search'])) {
            $search = '%' . strtolower(trim((string) $filters['search'])) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(COALESCE(mpr_number, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(requestor_name, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(requestor_email, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(entity, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(position, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(division, \'\')) LIKE ?', [$search]);
            });
        }

        return $query->orderByDesc('id')
            ->get()
            ->map(fn (MprRequest $row) => $this->toData($row))
            ->values();
    }

    public function getAllForManager(string $email, array $filters = []): Collection
    {
        $normalizedEmail = strtolower(trim($email));

        return $this->getAll($filters)->filter(function (MprData $mpr) use ($normalizedEmail) {
            return strtolower(trim($mpr->requestorEmail ?? '')) === $normalizedEmail
                || strtolower(trim($mpr->createdBy ?? '')) === $normalizedEmail;
        })->values();
    }

    public function findByMprNumber(string $mprNumber): ?MprData
    {
        $row = MprRequest::where('mpr_number', trim($mprNumber))->first();

        return $row ? $this->toData($row) : null;
    }

    public function findById(string $id): ?MprData
    {
        return $this->findByMprNumber($id);
    }

    public function create(MprData $data): MprData
    {
        $now = now()->timezone('Asia/Jakarta');

        if (empty($data->mprNumber)) {
            $data->mprNumber = 'MPR-' . $now->format('Ymd') . '-' . sprintf('%04d', random_int(1, 9999));
        }
        if (empty($data->requestDate)) {
            $data->requestDate = $now->format('Y-m-d');
        }
        if (empty($data->status)) {
            $data->status = 'Submitted';
        }

        $payload = $this->toFillable($data);
        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;

        $model = MprRequest::create($payload);

        return $this->toData($model->fresh());
    }

    public function update(string $mprNumber, MprData $data): MprData
    {
        $model = MprRequest::where('mpr_number', trim($mprNumber))->first();
        if (!$model) {
            throw new RuntimeException("MPR '{$mprNumber}' tidak ditemukan.");
        }

        $data->mprNumber = $model->mpr_number;
        $model->fill($this->toFillable($data));
        $model->save();

        return $this->toData($model->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function toFillable(MprData $data): array
    {
        return [
            'mpr_number' => $data->mprNumber,
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
        ];
    }

    private function toData(MprRequest $model): MprData
    {
        return new MprData(
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
            quantity: $model->quantity ?? 1,
            expectedJoinDate: $model->expected_join_date,
            reason: $model->reason,
            replacementFor: $model->replacement_for,
            jobDescription: $model->job_description,
            requirements: $model->requirements,
            notes: $model->special_notes,
            status: $model->status ?? 'Submitted',
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
    }
}
