<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedUsersCommand extends Command
{
    protected $signature = 'mito:seed-users {--force : Overwrite existing users}';
    protected $description = 'Seed default users for each role (Super Admin, Admin, Super User, User)';

    protected array $users = [
        ['email' => 'admin@mito.co.id',          'username' => 'admin',          'name' => 'Super Admin',    'role' => 'Super Admin'],
        ['email' => 'hrmanager@mito.co.id',       'username' => 'hrmanager',      'name' => 'HR Manager',          'role' => 'Admin'],
        ['email' => 'hrrecruitment@mito.co.id',   'username' => 'hrrecruitment',  'name' => 'HR Recruitment',     'role' => 'Super User'],
        ['email' => 'hrstaff@mito.co.id',         'username' => 'hrstaff',        'name' => 'HR Staff',           'role' => 'User'],
        // NOTE: Manager accounts are NOT seeded here.
        // They belong to the mpr_requestor sheet.
        // Run: php artisan mito:seed-mpr-requestors
    ];

    public function handle(UserRepositoryInterface $userRepo): int
    {
        $this->info('Seeding default users...');

        $password = 'Mahakarya2026'; // default password for all seeded users
        $force = $this->option('force');
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($this->users as $userData) {
            $existing = $userRepo->findByEmail($userData['email']);
            if ($existing && !$force) {
                $this->warn("User {$userData['email']} already exists. Use --force to overwrite.");
                $skippedCount++;
                continue;
            }

            $data = [
                'username' => $userData['username'],
                'fullName' => $userData['name'],
                'role' => $userData['role'],
                'status' => 'Active',
                'passwordHash' => Hash::make($password),
                'createdBy' => 'seed-command',
            ];

            if ($existing) {
                $userRepo->updateByEmail($userData['email'], $data);
                $this->info("Updated user: {$userData['email']} ({$userData['role']})");
            } else {
                $userRepo->create(array_merge(['email' => $userData['email']], $data));
                $this->info("Created user: {$userData['email']} ({$userData['role']})");
                $createdCount++;
            }
        }

        $this->info('Default users seeded successfully.');
        if ($createdCount > 0) {
            $this->info('Default password: ' . $password);
        }
        if ($skippedCount > 0) {
            $this->line("Skipped {$skippedCount} existing user(s). Use --force to overwrite.");
        }
        return Command::SUCCESS;
    }
}
