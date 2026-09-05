<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds GA_IT and LEGAL dummy users for local development.
 *
 * Run: php artisan db:seed --class=LocalDevUsersSeeder
 *
 * Idempotent: safe to run multiple times; updates existing records.
 */
class LocalDevUsersSeeder extends Seeder
{
    private const USERS = [
        [
            'name'  => 'GA IT User',
            'email' => 'ga.it@mitogroup.local',
            'role'  => 'GA_IT',
            'pass'  => 'ga_it_secret',
        ],
        [
            'name'  => 'Legal User',
            'email' => 'legal@mitogroup.local',
            'role'  => 'LEGAL',
            'pass'  => 'legal_secret',
        ],
    ];

    public function run(): void
    {
        foreach (self::USERS as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'     => $u['name'],
                    'role'     => $u['role'],
                    'status'   => 'Active',
                    'password' => Hash::make($u['pass']),
                ],
            );

            $this->command?->info("Seeded: {$u['email']} ({$u['role']})");
        }
    }
}
