<?php

namespace App\Repositories\Sheets;

use App\DTOs\MprData;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;

class MprSheetsRepository implements MprRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.mpr', 'MPR');
    }

    public function getAll(array $filters = []): Collection
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $collection = collect($rows)->map(fn($row) => MprData::fromSheetRow($row));

        if (!empty($filters['status'])) {
            $status = strtolower(trim($filters['status']));
            $collection = $collection->filter(function (MprData $mpr) use ($status) {
                return strtolower(trim($mpr->status ?? '')) === $status;
            });
        }

        if (!empty($filters['department'])) {
            $dept = strtolower(trim($filters['department']));
            $collection = $collection->filter(function (MprData $mpr) use ($dept) {
                return strtolower(trim($mpr->department ?? '')) === $dept;
            });
        }

        if (!empty($filters['submit_by'])) {
            $submitBy = strtolower(trim($filters['submit_by']));
            $collection = $collection->filter(function (MprData $mpr) use ($submitBy) {
                return strtolower(trim($mpr->requestorName ?? '')) === $submitBy;
            });
        }

        if (!empty($filters['company'])) {
            $company = strtolower(trim($filters['company']));
            $collection = $collection->filter(function (MprData $mpr) use ($company) {
                return str_contains(strtolower(trim($mpr->entity ?? '')), $company);
            });
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $collection = $collection->filter(function (MprData $mpr) use ($search) {
                return str_contains(strtolower($mpr->mprNumber ?? ''), $search)
                    || str_contains(strtolower($mpr->requestorName ?? ''), $search)
                    || str_contains(strtolower($mpr->requestorEmail ?? ''), $search)
                    || str_contains(strtolower($mpr->entity ?? ''), $search)
                    || str_contains(strtolower($mpr->position ?? ''), $search)
                    || str_contains(strtolower($mpr->department ?? ''), $search)
                    || str_contains(strtolower($mpr->division ?? ''), $search);
            });
        }

        // Return sorted by created_at descending (newest first)
        return $collection->sortByDesc(function (MprData $mpr) {
            return $mpr->createdAt ?? $mpr->requestDate ?? '';
        })->values();
    }

    public function getAllForManager(string $email, array $filters = []): Collection
    {
        $normalizedEmail = strtolower(trim($email));
        $all = $this->getAll($filters);

        return $all->filter(function (MprData $mpr) use ($normalizedEmail) {
            return strtolower(trim($mpr->requestorEmail ?? '')) === $normalizedEmail
                || strtolower(trim($mpr->createdBy ?? '')) === $normalizedEmail;
        })->values();
    }

    public function findByMprNumber(string $mprNumber): ?MprData
    {
        $cleanNumber = trim($mprNumber);
        $row = $this->sheets->findRowBy($this->sheetName, 'MPR Number', $cleanNumber);
        return $row ? MprData::fromSheetRow($row) : null;
    }

    public function findById(string $id): ?MprData
    {
        return $this->findByMprNumber($id);
    }

    public function create(MprData $data): MprData
    {
        $now = now()->timezone('Asia/Jakarta');

        if (empty($data->mprNumber)) {
            $datePart = $now->format('Ymd');
            $randomPart = sprintf('%04d', rand(1, 9999));
            $data->mprNumber = "MPR-{$datePart}-{$randomPart}";
        }

        if (empty($data->requestDate)) {
            $data->requestDate = $now->format('Y-m-d');
        }

        if (empty($data->status)) {
            $data->status = 'Submitted';
        }

        $data->createdAt = $now->format('Y-m-d H:i:s');
        $data->updatedAt = $now->format('Y-m-d H:i:s');

        // Ensure headers exist in the sheet — urutan HARUS identik dengan toSheetRow() & config/hris.php schemas.MPR
        $expectedHeaders = config('hris.schemas.MPR', [
            'MPR Number',
            'Request Date',
            'Requestor Name',
            'Requestor Email',
            'Entitas yang Dituju',
            'Department',
            'Division',
            'Approval Division',
            'Position',
            'Job Level',
            'Work Location',
            'Employment Type',
            'Quantity',
            'Expected Join Date',
            'Reason',
            'Replacement For',
            'Job Description',
            'Requirements',
            'Requestor Position',
            'Working Days',
            'Working Hours',
            'Shift Detail',
            'Benefits',
            'Education Background',
            'Work Experience',
            'Skills / Competencies',
            'Languages',
            'Industry Reference',
            'Special Notes',
            'Key Results / Targets',
            'Status',
            'Created By',
            'Created At',
            'Updated At',
        ]);
        $this->sheets->ensureSheetHeaders($this->sheetName, $expectedHeaders);

        // Build row values sesuai urutan header aktual di sheet,
        // identik dengan pendekatan update() — agar tidak geser jika urutan kolom sheet berbeda.
        $serialized   = $data->toSheetRow(); // associative array: header → value
        $sheetHeaders = $expectedHeaders;    // gunakan urutan dari config/hris.php sebagai canonical order

        $rowValues = [];
        foreach ($sheetHeaders as $header) {
            $rowValues[] = array_key_exists($header, $serialized) ? $serialized[$header] : '';
        }

        $this->sheets->appendRow($this->sheetName, $rowValues);

        return $data;
    }

    public function update(string $mprNumber, MprData $data): MprData
    {
        $row = $this->sheets->findRowBy($this->sheetName, 'MPR Number', trim($mprNumber));
        if (!$row || empty($row['_row_number'])) {
            throw new \RuntimeException("MPR '{$mprNumber}' tidak ditemukan.");
        }

        $serialized = $data->toSheetRow();
        $rowValues = [];
        foreach ($row as $header => $value) {
            if ($header === '_row_number') {
                continue;
            }
            $rowValues[] = array_key_exists($header, $serialized) ? $serialized[$header] : $value;
        }

        if (!$this->sheets->updateRow($this->sheetName, (int) $row['_row_number'], $rowValues)) {
            throw new \RuntimeException("Gagal memperbarui baris MPR '{$mprNumber}'.");
        }

        return $data;
    }
}
