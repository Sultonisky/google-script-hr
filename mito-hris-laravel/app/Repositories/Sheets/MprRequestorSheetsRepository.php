<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Services\Google\GoogleSheetsService;

class MprRequestorSheetsRepository implements MprRequestorRepositoryInterface
{
    // Column header names — MUST match config/hris.php schemas.mpr_requestor
    protected const HEADERS = [
        'Requestor ID', 'Email', 'Username', 'Full Name', 'Job Position',
        'Role', 'Status', 'Password Hash', 'Last Login', 'Created At',
        'Updated At', 'Created By',
    ];

    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.mpr_requestor', 'mpr_requestor');
    }

    // -------------------------------------------------------------------------
    // READ
    // -------------------------------------------------------------------------

    public function findByEmail(string $email): ?array
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        foreach ($rows as $row) {
            if (strtolower(trim($row['Email'] ?? '')) === strtolower(trim($email))) {
                return $row;
            }
        }
        return null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $needle = strtolower(trim($identifier));
        foreach ($rows as $row) {
            $email    = strtolower(trim($row['Email']    ?? ''));
            $username = strtolower(trim($row['Username'] ?? ''));
            if ($email === $needle || $username === $needle) {
                return $row;
            }
        }
        return null;
    }

    public function getAll(): array
    {
        return $this->sheets->getRowsAsAssoc($this->sheetName);
    }

    public function isEmpty(): bool
    {
        return empty($this->sheets->getRowsAsAssoc($this->sheetName));
    }

    // -------------------------------------------------------------------------
    // WRITE
    // -------------------------------------------------------------------------

    public function create(array $data): void
    {
        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $row = [
            'Requestor ID' => $data['requestorId']  ?? $this->generateNextId(),
            'Email'        => $data['email']         ?? '',
            'Username'     => $data['username']      ?? '',
            'Full Name'    => $data['fullName']      ?? '',
            'Job Position' => $data['jobPosition']   ?? '',
            'Role'         => $data['role']          ?? 'Manager',
            'Status'       => $data['status']        ?? 'Active',
            'Password Hash' => $data['passwordHash'] ?? '',
            'Last Login'   => '',
            'Created At'   => $now,
            'Updated At'   => $now,
            'Created By'   => $data['createdBy']     ?? 'system',
        ];

        // Ensure the sheet headers exist before writing
        $this->sheets->ensureSheetHeaders(
            $this->sheetName,
            config('hris.schemas.mpr_requestor', self::HEADERS)
        );

        $this->sheets->appendRow($this->sheetName, array_values($row));
    }

    public function updateByEmail(string $email, array $data): void
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);

        $targetIndex = null;
        foreach ($rows as $index => $row) {
            if (strtolower(trim($row['Email'] ?? '')) === strtolower(trim($email))) {
                $targetIndex = $index;
                break;
            }
        }
        if ($targetIndex === null) {
            return;
        }

        $headers   = self::HEADERS;
        $rowNumber = $targetIndex + 2; // +2: header row (1) + 0-based offset
        $colCount  = count($headers);

        // Read current row (columns A to M = 13 columns)
        $endCol    = $this->columnIndexToLetter($colCount);
        $currentRow = $this->sheets->getRange(
            $this->sheetName,
            "A{$rowNumber}:{$endCol}{$rowNumber}",
            false
        )[0] ?? [];

        // Pad to full header length so new columns can be written
        while (count($currentRow) < $colCount) {
            $currentRow[] = '';
        }

        // Key → column header mapping
        $colMap = [
            'passwordHash' => 'Password Hash',
            'fullName'     => 'Full Name',
            'username'     => 'Username',
            'jobPosition'  => 'Job Position',
            'role'         => 'Role',
            'status'       => 'Status',
            'lastLogin'    => 'Last Login',
        ];

        foreach ($data as $key => $value) {
            if (isset($colMap[$key])) {
                $colIdx = array_search($colMap[$key], $headers);
                if ($colIdx !== false) {
                    $currentRow[$colIdx] = is_array($value) ? implode(', ', $value) : $value;
                }
            }
        }

        // Always update Updated At
        $updatedAtIdx = array_search('Updated At', $headers);
        if ($updatedAtIdx !== false) {
            $currentRow[$updatedAtIdx] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        $this->sheets->updateRow($this->sheetName, $rowNumber, $currentRow);
    }

    public function deleteByEmail(string $email): void
    {
        $this->updateByEmail($email, ['status' => 'Inactive']);
    }

    public function updateLastLogin(string $email): void
    {
        $this->updateByEmail($email, [
            'lastLogin' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);
    }

    // -------------------------------------------------------------------------
    // ID GENERATION
    // -------------------------------------------------------------------------

    public function generateNextId(): string
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $max  = 0;

        foreach ($rows as $row) {
            $id = trim($row['Requestor ID'] ?? '');
            // Pattern: MPR-REQ-001 … MPR-REQ-999
            if (preg_match('/^MPR-REQ-(\d+)$/i', $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'MPR-REQ-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    protected function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod    = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index  = (int)(($index - $mod) / 26);
        }
        return $letter;
    }
}
