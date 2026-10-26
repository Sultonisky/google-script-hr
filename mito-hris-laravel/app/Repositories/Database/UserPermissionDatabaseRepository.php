<?php

namespace App\Repositories\Database;

use App\Models\UserPermission;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;

class UserPermissionDatabaseRepository implements UserPermissionRepositoryInterface
{
    public function mappingsForUser(string $email): array
    {
        $email = strtolower(trim($email));

        return UserPermission::query()
            ->where('user_email', $email)
            ->orderBy('permission_key')
            ->get()
            ->map(fn (UserPermission $row) => [
                'User Email' => (string) $row->user_email,
                'Permission Key' => (string) $row->permission_key,
                'Granted' => $row->granted ? 'TRUE' : 'FALSE',
                'Granted By' => (string) ($row->granted_by ?? ''),
                'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
                'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            ])
            ->all();
    }

    public function upsert(string $email, string $permissionKey, bool $granted, string $grantedBy): bool
    {
        $email = strtolower(trim($email));
        $permissionKey = trim($permissionKey);

        UserPermission::query()->updateOrCreate(
            [
                'user_email' => $email,
                'permission_key' => $permissionKey,
            ],
            [
                'granted' => $granted,
                'granted_by' => $grantedBy,
            ]
        );

        return true;
    }

    public function reassignEmail(string $fromEmail, string $toEmail): void
    {
        $fromEmail = strtolower(trim($fromEmail));
        $toEmail = strtolower(trim($toEmail));
        if ($fromEmail === '' || $toEmail === '' || $fromEmail === $toEmail) {
            return;
        }

        UserPermission::query()
            ->whereRaw('LOWER(user_email) = ?', [$fromEmail])
            ->update(['user_email' => $toEmail]);
    }

    public function forceDeleteByEmail(string $email): int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        return UserPermission::query()->whereRaw('LOWER(user_email) = ?', [$email])->delete();
    }
}
