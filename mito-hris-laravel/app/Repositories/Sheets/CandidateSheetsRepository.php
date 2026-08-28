<?php

namespace App\Repositories\Sheets;

use App\DTOs\CandidateData;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use Illuminate\Support\Collection;
use Log;

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
            $collection = $collection->filter(function (CandidateData $c) use ($status) {
                return strtolower(trim($c->status ?? '')) === $status;
            });
        }

        if (!empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $collection = $collection->filter(function (CandidateData $c) use ($search) {
                return str_contains(strtolower($c->fullName ?? ''), $search)
                    || str_contains(strtolower($c->recruitmentId ?? ''), $search)
                    || str_contains(strtolower($c->email ?? ''), $search)
                    || str_contains(strtolower($c->nik ?? ''), $search)
                    || str_contains(strtolower($c->positionApplied ?? ''), $search);
            });
        }

        if (!empty($filters['city'])) {
            $city = strtolower(trim($filters['city']));
            $collection = $collection->filter(function (CandidateData $c) use ($city) {
                return str_contains(strtolower($c->city ?? ''), $city);
            });
        }

        return $collection->values();
    }

    public function findById(string $recruitmentId): ?CandidateData
    {
        $sheetKeys = ['candidates_accepted', 'candidates_probation', 'candidates_hold', 'candidates_blacklist', 'candidates'];
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
        // Cari sheet & row yang benar — kandidat bisa berada di data_kandidat,
        // kandidat_hold, kandidat_blacklist, atau kandidat_accepted (setelah
        // dipindah lewat moveToSheet). Update harus menargetkan sheet asalnya.
        $location = $this->locateRow($recruitmentId);
        if (!$location) {
            return false;
        }

        $sheetName = $location['sheetName'];
        $rowNumber = $location['rowNumber'];

        $allRows = $this->sheets->getRange($sheetName, "A{$rowNumber}:ZZ{$rowNumber}", false);
        if (empty($allRows)) return false;

        $headers = $this->sheets->getRange($sheetName, '1:1', false)[0] ?? [];
        $currentRow = array_values($allRows[0]);

        // Ensure currentRow is at least as wide as headers (sparse rows may be shorter)
        $headerCount = count($headers);
        while (count($currentRow) < $headerCount) {
            $currentRow[] = '';
        }

        foreach ($attributes as $key => $val) {
            $headerAliases = [
                'Offering Allow Pulsa' => ['Offering Allow Pulsa', 'Offering Allowance Pulsa', 'Allow Pulsa'],
            ];
            $colIdx = false;
            foreach ($headerAliases[$key] ?? [$key] as $header) {
                $colIdx = array_search($header, $headers);
                if ($colIdx !== false) {
                    break;
                }
            }
            if ($colIdx !== false) {
                $currentRow[$colIdx] = $val;
            }
        }

        // Update Updated At column
        $updatedAtIdx = array_search('Updated At', $headers);
        if ($updatedAtIdx !== false) {
            $currentRow[$updatedAtIdx] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        // Google Sheets API v4 requires all values to be strings/scalars.
        // Cast every value to string — null becomes '', arrays/objects become ''.
        $currentRow = array_values(array_map(function ($v) {
            if ($v === null || $v === false) return '';
            if (is_array($v) || is_object($v)) return '';
            return (string) $v;
        }, $currentRow));

        return $this->sheets->updateRow($sheetName, $rowNumber, $currentRow);
    }

    /**
     * Cari lokasi (sheet + nomor baris) sebuah Recruitment ID di seluruh sheet kandidat.
     * Mengembalikan ['sheetName' => string, 'rowNumber' => int] atau null bila tidak ditemukan.
     */
    private function locateRow(string $recruitmentId): ?array
    {
        $sheetKeys = ['candidates_accepted', 'candidates_probation', 'candidates_hold', 'candidates_blacklist', 'candidates'];
        foreach ($sheetKeys as $key) {
            $sheetName = config("google.sheets.{$key}");
            if (!$sheetName) continue;
            $row = $this->sheets->findRowBy($sheetName, 'Recruitment ID', $recruitmentId);
            if ($row && isset($row['_row_number'])) {
                return ['sheetName' => $sheetName, 'rowNumber' => (int) $row['_row_number']];
            }
        }
        return null;
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

        // Determine which source sheet currently holds this candidate (for correct delete later)
        $sourceLocation = $this->locateRow($recruitmentId);
        $sourceSheetKey = 'candidates'; // fallback
        if ($sourceLocation) {
            $sourceSheetName = $sourceLocation['sheetName'];
            // Reverse-lookup the config key from the sheet name
            foreach (config('google.sheets', []) as $key => $name) {
                if ($name === $sourceSheetName) {
                    $sourceSheetKey = $key;
                    break;
                }
            }
        }

        // Build row matching target sheet column structure
        // Target sheet (e.g. kandidat_accepted) may have more columns than toSheetRow() provides.
        $targetHeaders = $this->sheets->getRange($targetSheetName, '1:1', false)[0] ?? [];

        if (!empty($targetHeaders)) {
            // Build a full-width row aligned to target sheet headers
            $rowValues = [];
            // Map candidate fields by header name
            $baseAssoc = $this->candidateToAssoc($candidate);
            // Merge extra data (overrides / new columns)
            foreach ($extraData as $k => $v) {
                $baseAssoc[$k] = $v;
            }
            foreach ($targetHeaders as $header) {
                $rowValues[] = $baseAssoc[$header] ?? '';
            }
        } else {
            // Fallback: use compact toSheetRow() + inject extraData by position
            $rowValues = $candidate->toSheetRow();
            if (!empty($extraData)) {
                $headers = $this->sheets->getRange($targetSheetName, '1:1', true)[0] ?? [];
                foreach ($extraData as $key => $val) {
                    $colIdx = array_search($key, $headers);
                    if ($colIdx !== false) {
                        // Extend array if needed
                        while (count($rowValues) <= $colIdx) {
                            $rowValues[] = '';
                        }
                        $rowValues[$colIdx] = $val;
                    }
                }
            }
        }

        $this->sheets->appendRow($targetSheetName, $rowValues);

        // Delete from the actual source sheet (not hardcoded 'candidates')
        $this->deleteFromSheet($recruitmentId, $sourceSheetKey);

        return true;
    }

    /**
     * Convert CandidateData to assoc array keyed by Google Sheet header names.
     * This is the canonical mapping used for row construction.
     */
    private function candidateToAssoc(CandidateData $c): array
    {
        return [
            'Recruitment ID'              => $c->recruitmentId ?? '',
            'Created Date'                => $c->createdDate ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Full Name'                   => $c->fullName ?? '',
            'NIK'                         => "'" . ($c->nik ?? ''),
            'Birth Date'                  => $c->birthDate ?? '',
            'Age'                         => $c->age ?? '',
            'Gender'                      => $c->gender ?? '',
            'Marital Status'              => $c->maritalStatus ?? '',
            'Email'                       => $c->email ?? '',
            'Phone'                       => "'" . ($c->phone ?? ''),
            'Address'                     => $c->address ?? '',
            'City'                        => $c->city ?? '',
            'Position Applied'            => $c->positionApplied ?? '',
            'Education'                   => $c->education ?? '',
            'Work Experience'             => $c->workExperience ?? '',
            'Last Company'                => $c->lastCompany ?? '',
            'Current Employment Status'   => $c->currentEmploymentStatus ?? '',
            'Available to Join'           => $c->availableToJoin ?? '',
            'Expected Salary'             => $c->expectedSalary ?? '',
            'Recruitment Source'          => $c->recruitmentSource ?? '',
            'CV Link'                     => $c->cvLink ?? '',
            'Status'                      => $c->status ?? 'Pending',
            'HR Notes'                    => $c->hrNotes ?? '',
            'Created By'                  => $c->createdBy ?? 'Candidate',
            'Updated At'                  => $c->updatedAt ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Hold Reason'                 => $c->holdReason ?? '',
            'Hold Follow Up Date'         => $c->holdFollowUpDate ?? '',
            'Blacklist Reason'            => $c->blacklistReason ?? '',
            'Blacklist Date'              => $c->blacklistDate ?? '',
            'Blacklist Updated By'        => $c->blacklistUpdatedBy ?? '',
            'Employee ID'                 => $c->employeeId ?? '',
            'Processed Date'              => $c->processedDate ?? '',
            'Processed By'                => $c->processedBy ?? '',
            'Offering Created'            => $c->offeringCreated ?? '',
            'Offering Updated'            => $c->offeringUpdated ?? '',
            'Offering Created By'         => $c->offeringCreatedBy ?? '',
            'Offering Updated By'         => $c->offeringUpdatedBy ?? '',
            'Offering Company Entity'     => $c->offeringCompanyEntity ?? '',
            'Offering Position'           => $c->offeringPosition ?? '',
            'Offering Salary'             => $c->offeringSalary ?? '',
            'Offering Join Date'          => $c->offeringJoinDate ?? '',
            'Offering Notes'              => $c->offeringNotes ?? '',
            'Offering Response'           => $c->offeringResponse ?? '',
            'Offering Response Notes'     => $c->offeringResponseNotes ?? '',
            'Offering Response Date'      => $c->offeringResponseDate ?? '',
            'Offering Response By'        => $c->offeringResponseBy ?? '',
            'Onboarding Status'           => $c->onboardingStatus ?? '',
            'Onboarding Date'             => $c->onboardingDate ?? '',
            'Onboarding By'               => $c->onboardingBy ?? '',
            'Offering Division'           => $c->offeringDivision ?? '',
            'Offering Job Level'          => $c->offeringJobLevel ?? '',
            'Offering Lokasi Kerja'       => $c->offeringLokasiKerja ?? '',
            'Offering Salary Basic'       => $c->offeringSalaryBasic ?? '',
            'Offering Allow Pulsa'        => $c->offeringAllowPulsa ?? '',
            'Offering Allow Transport'    => $c->offeringAllowTransport ?? '',
            'Offering Employment Status'  => $c->offeringEmploymentStatus ?? '',
            'Offering Contract Duration'  => $c->offeringContractDuration ?? '',
            'Offering Working Hours'      => $c->offeringWorkingHours ?? '',
        ];
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
        $totalRows = count($this->sheets->getRowsAsAssoc($sheetName)) + 1; // +1 to account for header row

        try {
            $service       = app(\App\Services\Google\GoogleClientFactory::class)->getSheetsService();
            $spreadsheetId = config('google.spreadsheet_id');

            // Resolve real sheetId from spreadsheet metadata
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $realSheetId = null;
            foreach ($spreadsheet->getSheets() as $sheetObj) {
                if ($sheetObj->getProperties()->getTitle() === $sheetName) {
                    $realSheetId = $sheetObj->getProperties()->getSheetId();
                    break;
                }
            }

            if ($realSheetId === null) {
                Log::error("deleteFromSheet: sheetId not found for '{$sheetName}'");
                return false;
            }

            // Delete exactly the target row (1-indexed rowNumber → 0-indexed startIndex)
            $request = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [[
                    'deleteDimension' => [
                        'range' => [
                            'sheetId'    => $realSheetId,
                            'dimension'  => 'ROWS',
                            'startIndex' => $rowNumber - 1,   // 0-indexed
                            'endIndex'   => $rowNumber,       // exclusive
                        ],
                    ],
                ]],
            ]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $request);
            $this->sheets->clearCache($sheetName);
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to delete row {$rowNumber} from {$sheetName}: " . $e->getMessage());
            return false;
        }
    }
}
