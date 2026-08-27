<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Seed default MPR Requestor (Manpower) accounts into the mpr_requestor sheet.
 *
 * These accounts are completely separate from the internal HRIS Users sheet.
 * Each Manager is assigned one or more entities and a branch location.
 *
 * Usage:
 *   php artisan mito:seed-mpr-requestors
 *   php artisan mito:seed-mpr-requestors --force
 */
class SeedMprRequestorsCommand extends Command
{
    protected $signature = 'mito:seed-mpr-requestors
        {--force : Overwrite existing requestors}';

    protected $description = 'Seed default MPR Requestor (Manpower) accounts into the mpr_requestor sheet';

    /**
     * Default requestor accounts to seed.
     * entity: comma-separated string of entity codes (MSI, SPI, PII, MEP)
     * branch: physical location of the manager
     */
    protected array $requestors = [
        [
            'email'    => 'manager.msi@mito.co.id',
            'username' => 'manager.msi',
            'name'     => 'Manager MSI',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'manager.spi@mito.co.id',
            'username' => 'manager.spi',
            'name'     => 'Manager SPI',
            'entity'   => 'SPI',
            'branch'   => 'Jakarta',
        ],
        [
            'email'    => 'manager.pii@mito.co.id',
            'username' => 'manager.pii',
            'name'     => 'Manager PII',
            'entity'   => 'PII',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'manager.mep@mito.co.id',
            'username' => 'manager.mep',
            'name'     => 'Manager MEP',
            'entity'   => 'MEP',
            'branch'   => 'Jakarta',
        ],
        [
            // Example: Manager with multiple entities
            'email'    => 'manager.multi@mito.co.id',
            'username' => 'manager.multi',
            'name'     => 'Manager Multi Entity',
            'entity'   => 'MSI, SPI, MEP',
            'branch'   => 'Bandung',
        ],
    ];

    public function handle(MprRequestorRepositoryInterface $requestorRepo): int
    {
        $this->info('Seeding MPR Requestor (Manager) accounts...');
        $this->line('Sheet: mpr_requestor (independent from Users)');
        $this->newLine();

        $password      = 'Mahakarya2026'; // default password for all seeded requestors
        $force         = $this->option('force');
        $createdCount  = 0;
        $skippedCount  = 0;

        foreach ($this->requestors as $data) {
            $existing = $requestorRepo->findByEmail($data['email']);

            if ($existing && !$force) {
                $this->warn("  [SKIP] {$data['email']} — already exists. Use --force to overwrite.");
                $skippedCount++;
                continue;
            }

            $payload = [
                'username'     => $data['username'],
                'fullName'     => $data['name'],
                'role'         => 'Manpower',
                'status'       => 'Active',
                'passwordHash' => Hash::make($password),
                'entity'       => $data['entity'],
                'branch'       => $data['branch'],
                'createdBy'    => 'seed-command',
            ];

            if ($existing) {
                $requestorRepo->updateByEmail($data['email'], $payload);
                $this->info("  [UPDATED] {$data['email']} — Entity: {$data['entity']}, Branch: {$data['branch']}");
            } else {
                $requestorRepo->create(array_merge(['email' => $data['email']], $payload));
                $this->info("  [CREATED] {$data['email']} — Entity: {$data['entity']}, Branch: {$data['branch']}");
                $createdCount++;
            }
        }

        $this->newLine();
        $this->info('MPR Requestor seeding complete.');

        if ($createdCount > 0) {
            $this->line("  Default password : <comment>{$password}</comment>");
            $this->line('  Change passwords before deploying to production!');
        }

        if ($skippedCount > 0) {
            $this->line("  Skipped {$skippedCount} existing account(s). Use --force to overwrite.");
        }

        $this->newLine();
        $this->line('Accounts seeded:');
        foreach ($this->requestors as $r) {
            $this->line("  {$r['email']}  |  Entity: {$r['entity']}  |  Branch: {$r['branch']}");
        }

        return Command::SUCCESS;
    }
}
