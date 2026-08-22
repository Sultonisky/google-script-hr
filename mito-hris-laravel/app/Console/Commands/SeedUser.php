<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedUser extends Command
{
    protected $signature = 'app:seed-user {email} {username} {name} {password} {role=Super Admin}';
    protected $description = 'Seed atau update user di Google Sheets dengan bcrypt password';

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $username = trim((string) $this->argument('username'));
        $name = trim((string) $this->argument('name'));
        $password = (string) $this->argument('password');
        $role = trim((string) $this->argument('role'));

        $existing = $this->userRepository->findByEmail($email);

        if ($existing !== null) {
            $this->userRepository->updateByEmail($email, [
                'username' => $username,
                'fullName' => $name,
                'role' => $role,
                'status' => 'Active',
                'passwordHash' => Hash::make($password),
            ]);

            $this->info("User {$email} berhasil di-update.");

            return self::SUCCESS;
        }

        $this->userRepository->create([
            'email' => $email,
            'username' => $username,
            'fullName' => $name,
            'role' => $role,
            'status' => 'Active',
            'passwordHash' => Hash::make($password),
            'createdBy' => 'seed-command',
        ]);

        $this->info("User {$email} berhasil dibuat dengan role {$role}.");

        return self::SUCCESS;
    }
}
