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
        [
            'email'    => 'achmad.rusdianto@mito.co.id',
            'username' => 'achmad.rusdianto',
            'name'     => 'Achmad Rusdianto',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'adhytia.aprisada@mito.co.id',
            'username' => 'adhytia.aprisada',
            'name'     => 'Adhytia Aprisada',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'aditya.adipradhana@mito.co.id',
            'username' => 'aditya.adipradhana',
            'name'     => 'Aditya Adipradhana',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'andrew.andreson@mito.co.id',
            'username' => 'andrew.andreson',
            'name'     => 'Andrew Andreson',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'awanda.rhadifa@mito.co.id',
            'username' => 'awanda.rhadifa',
            'name'     => 'Awanda Rhadifa',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'azka.daulika@mito.co.id',
            'username' => 'azka.daulika',
            'name'     => 'Azka Daulika',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'dedy.ishak.ibrahim@mito.co.id',
            'username' => 'dedy.ishak.ibrahim',
            'name'     => 'Dedy Ishak Ibrahim',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'deryan.triarya.toetoeko@mito.co.id',
            'username' => 'deryan.triarya.toetoeko',
            'name'     => 'Deryan Triarya Toetoeko',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'destyani.wijaya@mito.co.id',
            'username' => 'destyani.wijaya',
            'name'     => 'Destyani Wijaya',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'dionysius.kurnia.apriliawan@mito.co.id',
            'username' => 'dionysius.kurnia.apriliawan',
            'name'     => 'Dionysius Kurnia Apriliawan',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'edwin.sumargo@mito.co.id',
            'username' => 'edwin.sumargo',
            'name'     => 'Edwin Sumargo',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'erly.viwajayati.yulius@mito.co.id',
            'username' => 'erly.viwajayati.yulius',
            'name'     => 'Erly Viwajayati Yulius',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'fabiola.aihwa@mito.co.id',
            'username' => 'fabiola.aihwa',
            'name'     => 'Fabiola Aihwa',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'harmen.remon@mito.co.id',
            'username' => 'harmen.remon',
            'name'     => 'Harmen Remon',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'hisar.hesti@mito.co.id',
            'username' => 'hisar.hesti',
            'name'     => 'Hisar Hesti',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'irenceva@mito.co.id',
            'username' => 'irenceva',
            'name'     => 'Irenceva',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'karya.rezki.mulia.umida@mito.co.id',
            'username' => 'karya.rezki.mulia.umida',
            'name'     => 'Karya Rezki Mulia Umida',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'lydia.oktavia.kurniawan@mito.co.id',
            'username' => 'lydia.oktavia.kurniawan',
            'name'     => 'Lydia Oktavia Kurniawan',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'm.sigit.trisetyo@mito.co.id',
            'username' => 'm.sigit.trisetyo',
            'name'     => 'M.Sigit Trisetyo',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'mardiansyah.matondang@mito.co.id',
            'username' => 'mardiansyah.matondang',
            'name'     => 'Mardiansyah Matondang',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'mia.meidiana@mito.co.id',
            'username' => 'mia.meidiana',
            'name'     => 'Mia Meidiana',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'nadia.witaningtyas@mito.co.id',
            'username' => 'nadia.witaningtyas',
            'name'     => 'Nadia Witaningtyas',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'natalia.peregrina@mito.co.id',
            'username' => 'natalia.peregrina',
            'name'     => 'Natalia Peregrina',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'nungky.kendi.astini@mito.co.id',
            'username' => 'nungky.kendi.astini',
            'name'     => 'Nungky Kendi Astini',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'permana.dewa.putra@mito.co.id',
            'username' => 'permana.dewa.putra',
            'name'     => 'Permana Dewa Putra',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'reginald.hirawan@mito.co.id',
            'username' => 'reginald.hirawan',
            'name'     => 'Reginald Hirawan',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'retno.hardiani@mito.co.id',
            'username' => 'retno.hardiani',
            'name'     => 'Retno Hardiani',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'romanus.pandu.wibisono@mito.co.id',
            'username' => 'romanus.pandu.wibisono',
            'name'     => 'Romanus Pandu Wibisono',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'saraswening.purbawihayu@mito.co.id',
            'username' => 'saraswening.purbawihayu',
            'name'     => 'Saraswening Purbawihayu',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'sinta.jumiati@mito.co.id',
            'username' => 'sinta.jumiati',
            'name'     => 'Sinta Jumiati',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'sisilia.chandra.halim@mito.co.id',
            'username' => 'sisilia.chandra.halim',
            'name'     => 'Sisilia Chandra Halim',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'sri.rahayu.ayu@mito.co.id',
            'username' => 'sri.rahayu.ayu',
            'name'     => 'Sri Rahayu (Ayu)',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
        [
            'email'    => 'wahana.noerhib@mito.co.id',
            'username' => 'wahana.noerhib',
            'name'     => 'Wahana Noerhib',
            'entity'   => 'MSI',
            'branch'   => 'Tangerang',
        ],
    ];

    public function handle(MprRequestorRepositoryInterface $requestorRepo): int
    {
        $this->info('Seeding MPR Requestor (Manpower) accounts...');
        $this->line('Sheet: mpr_requestor (independent from Users)');
        $this->newLine();

        $password      = 'Mahakarya2026'; // default password for all seeded requestors
        $force         = $this->option('force');
        $createdCount  = 0;
        $updatedCount  = 0;
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
                $updatedCount++;
            } else {
                $requestorRepo->create(array_merge(['email' => $data['email']], $payload));
                $createdCount++;
            }
        }

        $this->newLine();
        $this->info('MPR Requestor seeding complete.');
        $this->line("  Created : {$createdCount}");
        $this->line("  Updated : {$updatedCount}");
        $this->line("  Skipped : {$skippedCount}");
        $this->line('  Total   : ' . count($this->requestors));

        if ($createdCount > 0) {
            $this->line("  Default password : <comment>{$password}</comment>");
            $this->line('  Change passwords before deploying to production!');
        }

        if ($skippedCount > 0 && !$force) {
            $this->line('  Use --force to overwrite skipped accounts.');
        }

        return Command::SUCCESS;
    }
}
