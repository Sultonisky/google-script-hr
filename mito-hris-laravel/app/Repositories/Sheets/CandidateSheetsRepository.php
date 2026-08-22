<?php

namespace App\Repositories\Sheets;

use App\DTOs\CandidateData;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;

class CandidateSheetsRepository implements CandidateRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.candidates', 'data_kandidat');
    }

    public function getAll(array $filters = []): Collection
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $collection = collect($rows)->map(fn($row) => CandidateData::fromSheetRow($row));

        if (!empty($filters['status'])) {
            $status = strtolower(trim($filters['status']));
            $collection = $collection->filter(function(CandidateData $c) use ($status) {
                return strtolower(trim($c->status ?? '')) === $status;
            });
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $collection = $collection->filter(function(CandidateData $c) use ($search) {
                return str_contains(strtolower($c->fullName ?? ''), $search)
                    || str_contains(strtolower($c->recruitmentId ?? ''), $search)
                    || str_contains(strtolower($c->email ?? ''), $search)
                    || str_contains(strtolower($c->nik ?? ''), $search)
                    || str_contains(strtolower($c->positionApplied ?? ''), $search);
            });
        }

        if (!empty($filters['city'])) {
            $city = strtolower(trim($filters['city']));
            $collection = $collection->filter(function(CandidateData $c) use ($city) {
                return str_contains(strtolower($c->city ?? ''), $city);
            });
        }

        return $collection->values();
    }

    public function findById(string $recruitmentId): ?CandidateData
    {
        $sheetKeys = ['candidates', 'candidates_hold', 'candidates_blacklist', 'candidates_accepted', 'candidates_probation'];
        foreach ($sheetKeys as $key) {
            $sheetName = config("google.sheets.{$key}");
            if (!$sheetName) continue;
            $row = $this->sheets->findRowBy($sheetName, 'Recruitment ID', $recruitmentId);
            if ($row) return CandidateData::fromSheetRow($row);
        }
        return null;
    }

    public function findByNik(string $nik): ?CandidateData
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $cleanNik = trim(ltrim($nik, "'"));
        foreach ($rows as $row) {
            $rowNik = trim(ltrim($row['NIK'] ?? '', "'"));
            if ($rowNik === $cleanNik) {
                return CandidateData::fromSheetRow($row);
            }
        }
        return null;
    }

    public function create(CandidateData $data): CandidateData
    {
        if (empty($data->recruitmentId)) {
            $datePart = now()->timezone('Asia/Jakarta')->format('Ymd');
            $randomPart = sprintf('%04d', rand(1, 9999));
            $data->recruitmentId = "REC-{$datePart}-{$randomPart}";
        }

        $rowValues = $data->toSheetRow();
        $this->sheets->appendRow($this->sheetName, $rowValues);

        return $data;
    }

    public function update(string $recruitmentId, array $attributes): bool
    {
        $existing = $this->findById($recruitmentId);
        if (!$existing || !$existing->rowNumber) {
            return false;
        }

        $allRows = $this->sheets->getRange($this->sheetName, "A{$existing->rowNumber}:ZZ{$existing->rowNumber}", false);
        if (empty($allRows)) return false;

        $headers = $this->sheets->getRange($this->sheetName, '1:1', true)[0] ?? [];
        $currentRow = $allRows[0];

        foreach ($attributes as $key => $val) {
            $colIdx = array_search($key, $headers);
            if ($colIdx !== false) {
                $currentRow[$colIdx] = $val;
            }
        }

        // Update Updated At column
        $updatedAtIdx = array_search('Updated At', $headers);
        if ($updatedAtIdx !== false) {
            $currentRow[$updatedAtIdx] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        return $this->sheets->updateRow($this->sheetName, $existing->rowNumber, $currentRow);
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

    public function getAllFromSheets(array $sheetKeys = []): Collection
    {
        if (empty($sheetKeys)) {
            $sheetKeys = ['candidates', 'candidates_hold', 'candidates_blacklist', 'candidates_accepted', 'candidates_probation'];
        }

        $all = collect();
        foreach ($sheetKeys as $key) {
            $sheetName = config("google.sheets.{$key}");
            if (!$sheetName) continue;
            $rows = $this->sheets->getRowsAsAssoc($sheetName);
            foreach ($rows as $row) {
                $all->push(CandidateData::fromSheetRow($row));
            }
        }

        return $all;
    }

    public function moveToSheet(string $recruitmentId, string $targetSheetKey, array $extraData = []): bool
    {
        $candidate = $this->findById($recruitmentId);
        if (!$candidate) {
            return false;
        }

        $targetSheetName = config("google.sheets.{$targetSheetKey}");
        if (!$targetSheetName) {
            return false;
        }

        $rowValues = $candidate->toSheetRow();
        
        if (!empty($extraData)) {
            $headers = $this->sheets->getRange($targetSheetName, '1:1', true)[0] ?? [];
            foreach ($extraData as $key => $val) {
                $colIdx = array_search($key, $headers);
                if ($colIdx !== false && isset($rowValues[$colIdx])) {
                    $rowValues[$colIdx] = $val;
                }
            }
        }

        $this->sheets->appendRow($targetSheetName, $rowValues);
        $this->deleteFromSheet($recruitmentId, 'candidates');

        return true;
    }

    public function deleteFromSheet(string $recruitmentId, string $sheetKey): bool
    {
        $sheetName = config("google.sheets.{$sheetKey}");
        if (!$sheetName) {
            return false;
        }

        $row = $this->sheets->findRowBy($sheetName, 'Recruitment ID', $recruitmentId);
        if (!$row || !isset($row['_row_number'])) {
            return false;
        }

        $rowNumber = $row['_row_number'];
        $totalRows = count($this->sheets->getRowsAsAssoc($sheetName));
        
        if ($rowNumber <= $totalRows) {
            $lastRow = $this->sheets->getRange($sheetName, "A{$totalRows}:ZZ{$totalRows}", false);
            if (!empty($lastRow)) {
                $this->sheets->updateRow($sheetName, $rowNumber, $lastRow[0]);
            }
            
            $rangeToDelete = "{$sheetName}!A{$totalRows}:ZZ{$totalRows}";
            try {
                $service = app(\App\Services\Google\GoogleClientFactory::class)->getSheetsService();
                $request = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => 0,
                                'dimension' => 'ROWS',
                                'startIndex' => $totalRows - 1,
                                'endIndex' => $totalRows
                            ]
                        ]
                    ]
                ]);
                $service->spreadsheets->batchUpdate(config('google.spreadsheet_id'), $request);
                $this->sheets->clearCache($sheetName);
                return true;
            } catch (\Throwable $e) {
                \Log::error("Failed to delete row {$rowNumber} from {$sheetName}: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}
