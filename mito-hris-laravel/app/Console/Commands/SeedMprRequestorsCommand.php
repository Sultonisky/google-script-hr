<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Seed default MPR Requestor (Manpower) accounts into the mpr_requestor sheet.
 *
 * These accounts are completely separate from the internal HRIS Users sheet.
 * Each Manager is assigned a job position.
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
     *
     * The "job_position" field is retained for compatibility with
     * the existing repository/schema, but its value now contains
     * the Job Position.
     */
    protected array $requestors = [
        [
            'email'    => 'achmad.rusdianto@mito.co.id',
            'username' => 'achmad.rusdianto',
            'name'     => 'Achmad Rusdianto',
            'job_position'   => 'Regional Sales Manager (Kalimantan & Sulawesi)',
        ],
        [
            'email'    => 'adhytia.aprisada@mito.co.id',
            'username' => 'adhytia.aprisada',
            'name'     => 'Adhytia Aprisada',
            'job_position'   => 'Manager (Semarang)',
        ],
        [
            'email'    => 'aditya.adipradhana@mito.co.id',
            'username' => 'aditya.adipradhana',
            'name'     => 'Aditya Adipradhana',
            'job_position'   => 'Marketing Head',
        ],
        [
            'email'    => 'andrew.andreson@mito.co.id',
            'username' => 'andrew.andreson',
            'name'     => 'Andrew Andreson',
            'job_position'   => 'Purchasing Expert',
        ],
        [
            'email'    => 'awanda.rhadifa@mito.co.id',
            'username' => 'awanda.rhadifa',
            'name'     => 'Awanda Rhadifa',
            'job_position'   => 'Product Marketing Manager',
        ],
        [
            'email'    => 'azka.daulika@mito.co.id',
            'username' => 'azka.daulika',
            'name'     => 'Azka Daulika',
            'job_position'   => 'Creative Manager',
        ],
        [
            'email'    => 'dedy.ishak.ibrahim@mito.co.id',
            'username' => 'dedy.ishak.ibrahim',
            'name'     => 'Dedy Ishak Ibrahim',
            'job_position'   => 'Manager (Makassar)',
        ],
        [
            'email'    => 'deryan.triarya.toetoeko@mito.co.id',
            'username' => 'deryan.triarya.toetoeko',
            'name'     => 'Deryan Triarya Toetoeko',
            'job_position'   => 'Brand Marketing Manager',
        ],
        [
            'email'    => 'destyani.wijaya@mito.co.id',
            'username' => 'destyani.wijaya',
            'name'     => 'Destyani Wijaya',
            'job_position'   => 'Finance, Accounting, & Tax Head',
        ],
        [
            'email'    => 'dionysius.kurnia.apriliawan@mito.co.id',
            'username' => 'dionysius.kurnia.apriliawan',
            'name'     => 'Dionysius Kurnia Apriliawan',
            'job_position'   => 'Manager (Bandung)',
        ],
        [
            'email'    => 'edwin.sumargo@mito.co.id',
            'username' => 'edwin.sumargo',
            'name'     => 'Edwin Sumargo',
            'job_position'   => 'Manufacture Manager',
        ],
        [
            'email'    => 'erly.viwajayati.yulius@mito.co.id',
            'username' => 'erly.viwajayati.yulius',
            'name'     => 'Erly Viwajayati Yulius',
            'job_position'   => 'Digital Marketing Assistant Manager',
        ],
        [
            'email'    => 'fabiola.aihwa@mito.co.id',
            'username' => 'fabiola.aihwa',
            'name'     => 'Fabiola Aihwa',
            'job_position'   => 'Finance AP & GA Asisstant Manager',
        ],
        [
            'email'    => 'harmen.remon@mito.co.id',
            'username' => 'harmen.remon',
            'name'     => 'Harmen Remon',
            'job_position'   => 'Manager (Palembang)',
        ],
        [
            'email'    => 'hisar.hesti@mito.co.id',
            'username' => 'hisar.hesti',
            'name'     => 'Hisar Hesti',
            'job_position'   => 'HR & Legal Manager',
        ],
        [
            'email'    => 'irenceva@mito.co.id',
            'username' => 'irenceva',
            'name'     => 'Irenceva',
            'job_position'   => 'Sales Head',
        ],
        [
            'email'    => 'karya.rezki.mulia.umida@mito.co.id',
            'username' => 'karya.rezki.mulia.umida',
            'name'     => 'Karya Rezki Mulia Umida',
            'job_position'   => 'Manager (Medan)',
        ],
        [
            'email'    => 'lydia.oktavia.kurniawan@mito.co.id',
            'username' => 'lydia.oktavia.kurniawan',
            'name'     => 'Lydia Oktavia Kurniawan',
            'job_position'   => 'Accounting Manager',
        ],
        [
            'email'    => 'm.sigit.trisetyo@mito.co.id',
            'username' => 'm.sigit.trisetyo',
            'name'     => 'M.Sigit Trisetyo',
            'job_position'   => 'Manager (Lampung)',
        ],
        [
            'email'    => 'mardiansyah.matondang@mito.co.id',
            'username' => 'mardiansyah.matondang',
            'name'     => 'Mardiansyah Matondang',
            'job_position'   => 'Manager (Jabo)',
        ],
        [
            'email'    => 'mia.meidiana@mito.co.id',
            'username' => 'mia.meidiana',
            'name'     => 'Mia Meidiana',
            'job_position'   => 'E-COMMERCE MANAGER',
        ],
        [
            'email'    => 'nadia.witaningtyas@mito.co.id',
            'username' => 'nadia.witaningtyas',
            'name'     => 'Nadia Witaningtyas',
            'job_position'   => 'Marketing Head',
        ],
        [
            'email'    => 'natalia.peregrina@mito.co.id',
            'username' => 'natalia.peregrina',
            'name'     => 'Natalia Peregrina',
            'job_position'   => 'Marketing Communication & Activation Manager',
        ],
        [
            'email'    => 'nungky.kendi.astini@mito.co.id',
            'username' => 'nungky.kendi.astini',
            'name'     => 'Nungky Kendi Astini',
            'job_position'   => 'Import Assistant Manager',
        ],
        [
            'email'    => 'permana.dewa.putra@mito.co.id',
            'username' => 'permana.dewa.putra',
            'name'     => 'Permana Dewa Putra',
            'job_position'   => 'Manager (Surabaya)',
        ],
        [
            'email'    => 'reginald.hirawan@mito.co.id',
            'username' => 'reginald.hirawan',
            'name'     => 'Reginald Hirawan',
            'job_position'   => 'IT Manager',
        ],
        [
            'email'    => 'retno.hardiani@mito.co.id',
            'username' => 'retno.hardiani',
            'name'     => 'Retno Hardiani',
            'job_position'   => 'Key Account Assistant Manager',
        ],
        [
            'email'    => 'romanus.pandu.wibisono@mito.co.id',
            'username' => 'romanus.pandu.wibisono',
            'name'     => 'Romanus Pandu Wibisono',
            'job_position'   => 'Regional Sales Manager (Java Island)',
        ],
        [
            'email'    => 'saraswening.purbawihayu@mito.co.id',
            'username' => 'saraswening.purbawihayu',
            'name'     => 'Saraswening Purbawihayu',
            'job_position'   => 'Trade Marketing Manager',
        ],
        [
            'email'    => 'sinta.jumiati@mito.co.id',
            'username' => 'sinta.jumiati',
            'name'     => 'Sinta Jumiati',
            'job_position'   => 'RnD & Aftersales Manager',
        ],
        [
            'email'    => 'sisilia.chandra.halim@mito.co.id',
            'username' => 'sisilia.chandra.halim',
            'name'     => 'Sisilia Chandra Halim',
            'job_position'   => 'Finance AR Manager',
        ],
        [
            'email'    => 'sri.rahayu.ayu@mito.co.id',
            'username' => 'sri.rahayu.ayu',
            'name'     => 'Sri Rahayu (Ayu)',
            'job_position'   => 'Manager (Jabo)',
        ],
        [
            'email'    => 'wahana.noerhib@mito.co.id',
            'username' => 'wahana.noerhib',
            'name'     => 'Wahana Noerhib',
            'job_position'   => 'Manager (Samarinda)',
        ],
    ];

    public function handle(MprRequestorRepositoryInterface $requestorRepo): int
    {
        $this->info('Seeding MPR Requestor (Manpower) accounts...');
        $this->line('Sheet: mpr_requestor (independent from Users)');
        $this->newLine();

        $password     = 'Mahakarya2026';
        $force        = $this->option('force');
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($this->requestors as $data) {
            $existing = $requestorRepo->findByEmail($data['email']);

            if ($existing && !$force) {
                $this->warn(
                    "  [SKIP] {$data['email']} — already exists. Use --force to overwrite."
                );

                $skippedCount++;
                continue;
            }

            $payload = [
                'username'     => $data['username'],
                'fullName'     => $data['name'],
                'jobPosition'        => $data['job_position'],
                'role'         => 'Manpower',
                'status'       => 'Active',
                'passwordHash' => Hash::make($password),
                'createdBy'    => 'seed-command',
            ];

            if ($existing) {
                $requestorRepo->updateByEmail($data['email'], $payload);
                $updatedCount++;
            } else {
                $requestorRepo->create(
                    array_merge(
                        ['email' => $data['email']],
                        $payload
                    )
                );

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
            $this->line(
                "  Default password : <comment>{$password}</comment>"
            );

            $this->line(
                '  Change passwords before deploying to production!'
            );
        }

        if ($skippedCount > 0 && !$force) {
            $this->line(
                '  Use --force to overwrite skipped accounts.'
            );
        }

        return Command::SUCCESS;
    }
}
