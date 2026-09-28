<?php

namespace App\Repositories\Database;

use App\DTOs\CandidateData;
use App\Models\Candidate;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Support\CandidateAttributeMap;
use Illuminate\Support\Collection;

class CandidateDatabaseRepository implements CandidateRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        $query = Candidate::query()
            ->where('lifecycle_status', CandidateAttributeMap::LIFECYCLE_PENDING);

        if (!empty($filters['status'])) {
            $query->whereRaw('LOWER(TRIM(status)) = ?', [strtolower(trim((string) $filters['status']))]);
        }

        if (!empty($filters['city'])) {
            $city = '%' . strtolower(trim((string) $filters['city'])) . '%';
            $query->whereRaw('LOWER(COALESCE(city, \'\')) LIKE ?', [$city]);
        }

        if (!empty($filters['search'])) {
            $search = '%' . strtolower(trim((string) $filters['search'])) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(COALESCE(full_name, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(recruitment_id, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(nik, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(position_applied, \'\')) LIKE ?', [$search]);
            });
        }

        return $query->orderBy('id')
            ->get()
            ->map(fn (Candidate $row) => CandidateAttributeMap::toData($row))
            ->values();
    }

    public function findById(string $recruitmentId): ?CandidateData
    {
        $row = Candidate::where('recruitment_id', trim($recruitmentId))->first();

        return $row ? CandidateAttributeMap::toData($row) : null;
    }

    public function findByNik(string $nik, bool $useCache = true): ?CandidateData
    {
        $cleanNik = ltrim(trim($nik), "'");
        if ($cleanNik === '') {
            return null;
        }

        $row = Candidate::query()
            ->where('nik', $cleanNik)
            ->whereRaw('LOWER(TRIM(COALESCE(status, \'\'))) != ?', ['deleted'])
            ->orderBy('id')
            ->first();

        return $row ? CandidateAttributeMap::toData($row) : null;
    }

    public function create(CandidateData $data): CandidateData
    {
        if (empty($data->recruitmentId)) {
            $datePart = now()->timezone('Asia/Jakarta')->format('Ymd');
            $randomPart = sprintf('%04d', random_int(1, 9999));
            $data->recruitmentId = "REC-{$datePart}-{$randomPart}";
        }

        if (empty($data->createdDate)) {
            $data->createdDate = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        $model = Candidate::create(CandidateAttributeMap::toFillable($data, CandidateAttributeMap::LIFECYCLE_PENDING));

        return CandidateAttributeMap::toData($model->fresh());
    }

    public function update(string $recruitmentId, array $attributes): bool
    {
        $model = Candidate::where('recruitment_id', trim($recruitmentId))->first();
        if (!$model) {
            return false;
        }

        CandidateAttributeMap::applySheetAttributes($model, $attributes);
        $model->touch();

        return $model->save();
    }

    public function updateStatus(string $recruitmentId, string $status, ?string $notes = null, array $extra = []): bool
    {
        $attributes = array_merge(['Status' => $status], $extra);
        if ($notes !== null) {
            $attributes['HR Notes'] = $notes;
        }

        return $this->update($recruitmentId, $attributes);
    }

    public function delete(string $recruitmentId): bool
    {
        return $this->updateStatus($recruitmentId, 'Deleted');
    }

    public function listByLifecycle(array $buckets = []): Collection
    {
        if ($buckets === []) {
            $buckets = array_keys(CandidateAttributeMap::BUCKET_TO_LIFECYCLE);
        }

        $lifecycles = [];
        foreach ($buckets as $bucket) {
            $lifecycle = CandidateAttributeMap::bucketToLifecycle($bucket);
            if ($lifecycle !== null) {
                $lifecycles[] = $lifecycle;
            }
        }

        if ($lifecycles === []) {
            return collect();
        }

        return Candidate::query()
            ->whereIn('lifecycle_status', $lifecycles)
            ->orderBy('id')
            ->get()
            ->map(fn (Candidate $row) => CandidateAttributeMap::toData($row))
            ->values();
    }

    public function moveToHold(string $recruitmentId, array $extraData = []): bool
    {
        return $this->moveToSheet($recruitmentId, 'candidates_hold', $extraData);
    }

    public function moveToBlacklist(string $recruitmentId, array $extraData = []): bool
    {
        return $this->moveToSheet($recruitmentId, 'candidates_blacklist', $extraData);
    }

    public function moveToAccepted(string $recruitmentId, array $extraData = []): bool
    {
        return $this->moveToSheet($recruitmentId, 'candidates_accepted', $extraData);
    }

    public function getAllFromSheets(array $sheetKeys = []): Collection
    {
        return $this->listByLifecycle($sheetKeys);
    }

    public function moveToSheet(string $recruitmentId, string $targetSheetKey, array $extraData = []): bool
    {
        $lifecycle = CandidateAttributeMap::bucketToLifecycle($targetSheetKey);
        if ($lifecycle === null) {
            return false;
        }

        $model = Candidate::where('recruitment_id', trim($recruitmentId))->first();
        if (!$model) {
            return false;
        }

        CandidateAttributeMap::applySheetAttributes($model, $extraData);
        $model->lifecycle_status = $lifecycle;
        $model->touch();

        return $model->save();
    }

    public function deleteFromSheet(string $recruitmentId, string $sheetKey): bool
    {
        $lifecycle = CandidateAttributeMap::bucketToLifecycle($sheetKey);
        $model = Candidate::where('recruitment_id', trim($recruitmentId))->first();
        if (!$model) {
            return false;
        }

        // Already moved away from this bucket (DB analogue of physical row delete after move).
        if ($lifecycle !== null && $model->lifecycle_status !== $lifecycle) {
            return true;
        }

        $model->status = 'Deleted';
        $model->touch();

        return $model->save();
    }
}
