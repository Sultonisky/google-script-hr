<?php

namespace App\Repositories\Database;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Database-backed user repository for local development.
 *
 * Implements the same interface as UserSheetsRepository but reads/writes
 * from the Eloquent users table instead of Google Sheets.
 *
 * Maps Eloquent attributes to the sheet-style array format that AuthService expects:
 *   Email, Username, Full Name, Role, Status, Password Hash, Last Login, etc.
 */
class UserDatabaseRepository implements UserRepositoryInterface
{
    public function findByIdentifier(string $identifier): ?array
    {
        $identifier = strtolower(trim($identifier));

        $user = User::whereRaw('LOWER(email) = ?', [$identifier])
            ->orWhereRaw('LOWER(name) = ?', [$identifier])
            ->first();

        return $user ? $this->toSheetFormat($user) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();

        return $user ? $this->toSheetFormat($user) : null;
    }

    public function getAll(): array
    {
        return User::all()->map(fn(User $u) => $this->toSheetFormat($u))->toArray();
    }

    public function create(array $data): void
    {
        User::create([
            'name'     => $data['fullName'] ?? $data['name'] ?? '',
            'email'    => $data['email'] ?? '',
            'password' => $data['passwordHash'] ?? '',
            'role'     => $data['role'] ?? 'User',
            'status'   => $data['status'] ?? 'Active',
        ]);
    }

    public function updateByEmail(string $email, array $data): void
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->first();
        if (!$user) {
            return;
        }

        $updates = [];
        if (isset($data['passwordHash'])) {
            $updates['password'] = $data['passwordHash'];
        }
        if (isset($data['fullName'])) {
            $updates['name'] = $data['fullName'];
        }
        if (isset($data['role'])) {
            $updates['role'] = $data['role'];
        }
        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (!empty($updates)) {
            $user->update($updates);
        }
    }

    public function deleteByEmail(string $email): void
    {
        User::whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->delete();
    }

    public function isEmpty(): bool
    {
        return User::count() === 0;
    }

    public function updateLastLogin(string $email): void
    {
        // No 'last_login' column on users table — skip silently.
        // The sheet-based repo updates a column; local DB users don't need this.
    }

    /**
     * Convert an Eloquent User to the sheet-style array that AuthService expects.
     */
    private function toSheetFormat(User $user): array
    {
        return [
            'Email'         => $user->email,
            'Username'      => $user->email,
            'Full Name'     => $user->name,
            'Role'          => $user->role ?? 'User',
            'Status'        => $user->status ?? 'Active',
            'Password Hash' => $user->password ?? '',
            'Last Login'    => '',
            'Created At'    => $user->created_at?->toDateTimeString() ?? '',
            'Updated At'    => $user->updated_at?->toDateTimeString() ?? '',
            'Created By'    => 'system',
        ];
    }
}
