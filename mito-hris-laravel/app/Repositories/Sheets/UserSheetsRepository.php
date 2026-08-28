<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Google\GoogleSheetsService;

class UserSheetsRepository implements UserRepositoryInterface
{
    private const DEFAULT_USER_HEADERS = [
        'Email',
        'Username',
        'Full Name',
        'Role',
        'Status',
        'Password Hash',
        'Last Login',
        'Created At',
        'Updated At',
        'Created By',
    ];

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
            'Username' => $data['username'] ?? '',
            'Full Name' => $data['fullName'] ?? '',
            'Role' => $data['role'] ?? 'Viewer',
            'Status' => $data['status'] ?? 'Active',
            'Password Hash' => $data['passwordHash'] ?? '',
            'Last Login' => $data['lastLogin'] ?? '',
            'Created At' => $data['createdAt'] ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Updated At' => $data['updatedAt'] ?? now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'Created By' => $data['createdBy'] ?? 'system',
        ];
        $this->sheets->appendRow($this->sheetName, array_map(
            fn (string $header): string => (string) ($row[$header] ?? ''),
            $this->userHeaders()
        ));
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

        $headers = $this->userHeaders();
        $rowNumber = $targetIndex + 2; // +2 karena header row + zero-based
        $currentRow = $this->sheets->getRange($this->sheetName, "A{$rowNumber}:J{$rowNumber}", false)[0] ?? [];

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
                'createdAt'    => 'Created At',
                'updatedAt'    => 'Updated At',
                'createdBy'    => 'Created By',
            ];
            if (isset($colMap[$key])) {
                $colIdx = array_search($colMap[$key], $headers);
                if ($colIdx !== false) {
                    $currentRow[$colIdx] = $value;
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

    private function userHeaders(): array
    {
        return config('hris.schemas.Users', self::DEFAULT_USER_HEADERS);
    }
}
