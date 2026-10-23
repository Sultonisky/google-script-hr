<?php

namespace App\Repositories\GoogleSheets;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\Google\GoogleSheetsService;

class UserPermissionSheetsRepository implements UserPermissionRepositoryInterface
{
    private const HEADERS = [
        'User Email', 'Permission Key', 'Granted', 'Granted By', 'Created At', 'Updated At',
    ];

    public function __construct(private GoogleSheetsService $sheets)
    {
    }

    public function mappingsForUser(string $email): array
    {
        $email = strtolower(trim($email));
        return array_values(array_filter(
            $this->sheets->getRowsAsAssoc(config('google.sheets.user_permissions', 'User_Permissions')),
            fn (array $row): bool => strtolower(trim((string) ($row['User Email'] ?? ''))) === $email
        ));
    }

    public function upsert(string $email, string $permissionKey, bool $granted, string $grantedBy): bool
    {
        $sheet = config('google.sheets.user_permissions', 'User_Permissions');
        $normalizedEmail = strtolower(trim($email));
        $rows = $this->sheets->getRowsAsAssoc($sheet, false);
        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $matched = false;
        $success = true;
        foreach ($rows as $row) {
            if (strtolower(trim((string) ($row['User Email'] ?? ''))) !== $normalizedEmail
                || trim((string) ($row['Permission Key'] ?? '')) !== $permissionKey) {
                continue;
            }

            $matched = true;

            $headers = config('hris.schemas.User_Permissions', self::HEADERS);
            $values = [];
            foreach ($headers as $header) {
                $values[] = match ($header) {
                    'User Email' => $normalizedEmail,
                    'Permission Key' => $permissionKey,
                    'Granted' => $granted ? 'TRUE' : 'FALSE',
                    'Granted By' => $grantedBy,
                    'Created At' => $row['Created At'] ?? $now,
                    'Updated At' => $now,
                    default => '',
                };
            }

            $success = $this->sheets->updateRow($sheet, (int) $row['_row_number'], $values) && $success;
        }

        if ($matched) {
            return $success;
        }

        return $this->sheets->appendRow($sheet, [$normalizedEmail, $permissionKey, $granted ? 'TRUE' : 'FALSE', $grantedBy, $now, $now]);
    }

    public function reassignEmail(string $fromEmail, string $toEmail): void
    {
        $fromEmail = strtolower(trim($fromEmail));
        $toEmail = strtolower(trim($toEmail));
        if ($fromEmail === '' || $toEmail === '' || $fromEmail === $toEmail) {
            return;
        }

        $sheet = config('google.sheets.user_permissions', 'User_Permissions');
        $headers = config('hris.schemas.User_Permissions', self::HEADERS);
        $now = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        foreach ($this->sheets->getRowsAsAssoc($sheet, false) as $row) {
            if (strtolower(trim((string) ($row['User Email'] ?? ''))) !== $fromEmail) {
                continue;
            }

            $values = [];
            foreach ($headers as $header) {
                $values[] = match ($header) {
                    'User Email' => $toEmail,
                    'Updated At' => $now,
                    default => $row[$header] ?? '',
                };
            }

            $rowNumber = (int) ($row['_row_number'] ?? 0);
            if ($rowNumber < 2) {
                continue;
            }
            $this->sheets->updateRow($sheet, $rowNumber, $values);
        }
    }
}