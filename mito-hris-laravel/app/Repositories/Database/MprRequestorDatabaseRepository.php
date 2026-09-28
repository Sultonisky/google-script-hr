<?php

namespace App\Repositories\Database;

use App\Models\MprRequestor;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;

class MprRequestorDatabaseRepository implements MprRequestorRepositoryInterface
{
    public function findByEmail(string $email): ?array
    {
        $row = MprRequestor::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->first();

        return $row ? $this->toSheetFormat($row) : null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->findByEmail($identifier);
    }

    public function getAll(): array
    {
        return MprRequestor::query()
            ->orderBy('id')
            ->get()
            ->map(fn (MprRequestor $row) => $this->toSheetFormat($row))
            ->all();
    }

    public function create(array $data): void
    {
        MprRequestor::create([
            'requestor_id' => $data['requestorId'] ?? $this->generateNextId(),
            'email' => $data['email'] ?? '',
            'username' => $data['username'] ?? '',
            'full_name' => $data['fullName'] ?? '',
            'job_position' => $data['jobPosition'] ?? '',
            'role' => $data['role'] ?? 'Manager',
            'status' => $data['status'] ?? 'Active',
            'password_hash' => $data['passwordHash'] ?? '',
            'last_login' => '',
            'created_by' => $data['createdBy'] ?? 'system',
        ]);
    }

    public function updateByEmail(string $email, array $data): void
    {
        $row = MprRequestor::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->first();
        if (!$row) {
            return;
        }

        $map = [
            'passwordHash' => 'password_hash',
            'fullName' => 'full_name',
            'username' => 'username',
            'jobPosition' => 'job_position',
            'role' => 'role',
            'status' => 'status',
            'lastLogin' => 'last_login',
        ];

        $updates = [];
        foreach ($data as $key => $value) {
            if (!isset($map[$key])) {
                continue;
            }
            $updates[$map[$key]] = is_array($value) ? implode(', ', $value) : $value;
        }

        if ($updates !== []) {
            $row->update($updates);
        }
    }

    public function deleteByEmail(string $email): void
    {
        $this->updateByEmail($email, ['status' => 'Inactive']);
    }

    public function isEmpty(): bool
    {
        return MprRequestor::query()->count() === 0;
    }

    public function updateLastLogin(string $email): void
    {
        $this->updateByEmail($email, [
            'lastLogin' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);
    }

    public function generateNextId(): string
    {
        $ids = MprRequestor::query()->pluck('requestor_id');
        $max = 0;
        foreach ($ids as $id) {
            if (preg_match('/^MPR-REQ-(\d+)$/i', trim((string) $id), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'MPR-REQ-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    private function toSheetFormat(MprRequestor $row): array
    {
        return [
            'Requestor ID' => (string) $row->requestor_id,
            'Email' => (string) $row->email,
            'Username' => (string) ($row->username ?? ''),
            'Full Name' => (string) ($row->full_name ?? ''),
            'Job Position' => (string) ($row->job_position ?? ''),
            'Role' => (string) ($row->role ?? ''),
            'Status' => (string) ($row->status ?? ''),
            'Password Hash' => (string) ($row->password_hash ?? ''),
            'Last Login' => (string) ($row->last_login ?? ''),
            'Created At' => optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            'Updated At' => optional($row->updated_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') ?? '',
            'Created By' => (string) ($row->created_by ?? ''),
        ];
    }
}
