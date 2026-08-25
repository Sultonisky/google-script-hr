<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Google\GoogleSheetsService;

class UserSheetsRepository implements UserRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.users', 'Users');
    }

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
        foreach ($rows as $row) {
            $email = strtolower(trim($row['Email'] ?? ''));
            $username = strtolower(trim($row['Username'] ?? ''));
            $identifierLower = strtolower(trim($identifier));
            if ($email === $identifierLower || $username === $identifierLower) {
                return $row;
            }
        }
        return null;
    }

    public function getAll(): array
    {
        return $this->sheets->getRowsAsAssoc($this->sheetName);
    }

    public function create(array $data): void
    {
        $row = [
            'Email' => $data['email'] ?? '',
            'Full Name' => $data['fullName'] ?? '',
            'Username' => $data['username'] ?? '',
            'Role' => $data['role'] ?? 'Viewer',
            'Status' => $data['status'] ?? 'Active',
            'Password Hash' => $data['passwordHash'] ?? '',
            'Last Login' => '',
            'Created By' => $data['createdBy'] ?? 'system',
            'Created At' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Updated At' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Entities' => is_array($data['entities'] ?? null) ? implode(', ', $data['entities']) : ($data['entities'] ?? ''),
            'Branch' => $data['branch'] ?? '',
        ];
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

        $headers = ['Email', 'Full Name', 'Username', 'Role', 'Status', 'Password Hash', 'Last Login', 'Created By', 'Created At', 'Updated At', 'Entities', 'Branch'];
        $rowNumber = $targetIndex + 2; // +2 karena header row + zero-based
        $currentRow = $this->sheets->getRange($this->sheetName, "A{$rowNumber}:L{$rowNumber}", false)[0] ?? [];

        // Pad row to full header length so new columns can be set
        while (count($currentRow) < count($headers)) {
            $currentRow[] = '';
        }

        foreach ($data as $key => $value) {
            $colMap = [
                'passwordHash' => 'Password Hash',
                'fullName'     => 'Full Name',
                'username'     => 'Username',
                'role'         => 'Role',
                'status'       => 'Status',
                'lastLogin'    => 'Last Login',
                'entities'     => 'Entities',
                'branch'       => 'Branch',
            ];
            if (isset($colMap[$key])) {
                $colIdx = array_search($colMap[$key], $headers);
                if ($colIdx !== false) {
                    // Normalize entities array to comma-separated string
                    $currentRow[$colIdx] = is_array($value) ? implode(', ', $value) : $value;
                }
            }
        }

        $updatedAtIdx = array_search('Updated At', $headers);
        if ($updatedAtIdx !== false) {
            $currentRow[$updatedAtIdx] = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        $this->sheets->updateRow($this->sheetName, $rowNumber, $currentRow);
    }

    public function deleteByEmail(string $email): void
    {
        // Soft delete: set status to Inactive
        $this->updateByEmail($email, ['status' => 'Inactive']);
    }

    public function isEmpty(): bool
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        return empty($rows);
    }

    public function updateLastLogin(string $email): void
    {
        $this->updateByEmail($email, ['lastLogin' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')]);
    }
}