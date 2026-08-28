<?php

namespace App\Repositories\GoogleSheets;

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

    public function findByIdentifier(string $identifier): ?array
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        foreach ($rows as $row) {
            if (strtolower(trim($row['Email'] ?? '')) === strtolower(trim($identifier))) {
                return $row;
            }
            if (strtolower(trim($row['Username'] ?? '')) === strtolower(trim($identifier))) {
                return $row;
            }
        }
        return null;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findByIdentifier($email);
    }

    public function updateByEmail(string $email, array $data): void
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $headers = $this->userHeaders();
        $emailCol = array_search('Email', $headers);
        if ($emailCol === false) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (strtolower(trim($row['Email'] ?? '')) === strtolower(trim($email))) {
                $rowNumber = $row['_row_number'] ?? ($index + 2);
                $values = [];
                foreach ($headers as $col) {
                    $dataKey = $this->dataKeyForHeader($col);
                    $values[] = array_key_exists($dataKey, $data)
                        ? $data[$dataKey]
                        : ($data[$col] ?? $row[$col] ?? '');
                }
                $this->sheets->updateRow($this->sheetName, $rowNumber, $values);
                return;
            }
        }
    }

    public function create(array $data): void
    {
        $sheetHeaders = $this->sheets->getRange($this->sheetName, '1:1', false);
        if (empty($sheetHeaders) || empty($sheetHeaders[0])) {
            // Sheet has no header row yet; this shouldn't happen in normal usage
            return;
        }

        // Normalize incoming data keys for matching.
        // SeedUsersCommand passes camelCase keys (e.g. 'passwordHash', 'fullName').
        // The sheet headers use display format (e.g. 'Password Hash', 'Full Name').
        // Build a flat lookup: normalize both sides to lowercase-no-space for fuzzy match.
        $normalize = fn(string $s) => strtolower(preg_replace('/[\s_\-]/', '', $s));

        $normalizedData = [];
        foreach ($data as $k => $v) {
            $normalizedData[$normalize($k)] = $v;
        }

        $values = [];
        foreach ($this->userHeaders() as $col) {
            $normalizedCol = $normalize($col);
            $values[] = $normalizedData[$normalizedCol] ?? '';
        }

        $this->sheets->appendRow($this->sheetName, $values);
    }

    public function isEmpty(): bool
    {
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        return empty($rows);
    }

    public function updateLastLogin(string $email): void
    {
        $data = ['Last Login' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')];
        $this->updateByEmail($email, $data);
    }

    public function getAll(): array
    {
        return $this->sheets->getRowsAsAssoc($this->sheetName);
    }

    public function deleteByEmail(string $email): void
    {
        // TODO: Implement row deletion from Google Sheets
        throw new \RuntimeException('deleteByEmail not implemented yet');
    }

    private function dataKeyForHeader(string $header): string
    {
        return match ($header) {
            'Email' => 'email',
            'Username' => 'username',
            'Full Name' => 'fullName',
            'Role' => 'role',
            'Status' => 'status',
            'Password Hash' => 'passwordHash',
            'Last Login' => 'lastLogin',
            'Created At' => 'createdAt',
            'Updated At' => 'updatedAt',
            'Created By' => 'createdBy',
            default => $header,
        };
    }

    private function userHeaders(): array
    {
        return config('hris.schemas.Users', self::DEFAULT_USER_HEADERS);
    }
}