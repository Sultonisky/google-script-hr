<?php

namespace Database\Seeders;

use App\Enums\CertStatus;
use App\Enums\CertType;
use App\Models\Certification;
use App\Services\CertificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Idempotent demo data for Certification Management.
 *
 * Employee references (employee_id / employee_name / division / department)
 * are denormalized dummy values matching the existing Employee master-data
 * shape (date-based Employee ID, division/department labels). The Google
 * Spreadsheet-backed employee master data is NOT touched by this seeder.
 *
 * Re-running updates existing seeded rows keyed by their cert_code
 * (matching code + employee_id + name); it never duplicates records.
 * New codes are always issued through CertificationService::generateCode()
 * so the existing SRT-xxxxx sequence logic stays the single source of truth.
 */
class CertificationDummySeeder extends Seeder
{
    private const CREATOR = 'DummySeeder';

    /**
     * One distinct dummy PDF per seeded certification record, keyed by the
     * stable SRT-xxxxx index (0-based) so re-seeding never duplicates or
     * renames files. Records that already carry an explicit attachment_path
     * keep it; the missing eight are filled from this map.
     */
    private const ATTACHMENT_FILES = [
        'iso-9001-siti-rahayu.pdf',
        'iso-14001-budi-santoso.pdf',
        'welder-smaw-agus-salim.pdf',
        'skt-tenaga-teknik-agus-salim.pdf',
        'sim-b1-andi-wijaya.pdf',
        'sim-b2-andi-wijaya.pdf',
        'sio-forklift-hendra-gunawan.pdf',
        'training-smk3-dewi-lestari.pdf',
        'fire-ert-budi-santoso.pdf',
        'auditor-iso45001-maya-anggraini.pdf',
        'seminar-k3-rudi-hartono.pdf',
        'ppgd-joko-susilo.pdf',
        'k3-umum-rudi-hartono.pdf',
        'auditor-smk3-siti-rahayu.pdf',
        'skck-joko-susilo.pdf',
    ];

    public function run(): void
    {
        $certService = app(CertificationService::class);

        // Relative dates so the demo always keeps "expiring soon" states.
        $fireErExpiry = now()->addDays(14);       // expiring within 30 days
        $auditorExpiry = now()->addDays(77);      // expiring within 90 days

        $rows = [
            // ── Professional (4) ──────────────────────────────────────
            [
                'cert_type'             => CertType::PROFESSIONAL->value,
                'name'                  => 'Sertifikat ISO 9001:2015 Quality Management System',
                'description'           => 'Sertifikasi sistem manajemen mutu untuk proses quality control produksi dan gudang.',
                'issuing_organization'  => 'TÜV Rheinland Indonesia',
                'certificate_number'    => '01 104 2004567',
                'issue_date'            => '2023-02-15',
                'expiry_date'           => '2026-02-14',
                'status'                => CertStatus::EXPIRED->value,
                'employee_id'           => '2021072301',
                'employee_name'         => 'Siti Rahayu',
                'division'              => 'Quality Control',
                'department'            => 'Quality Management System',
                'notes'                 => 'Sertifikat sudah tidak berlaku; rencana perpanjangan lewat Sucofindo dimulai Q4 2026.',
                'attachment_path'       => 'certifications/demo/iso-9001-siti-rahayu.pdf',
            ],
            [
                'cert_type'             => CertType::PROFESSIONAL->value,
                'name'                  => 'Sertifikat ISO 14001:2015 Environmental Management System',
                'description'           => 'Sistem manajemen lingkungan untuk gedung kantor dan gudang logistik.',
                'issuing_organization'  => 'PT Sucofindo (Sucofindo International Certification Services)',
                'certificate_number'    => 'SICS/EMS/2024/00457',
                'issue_date'            => '2024-05-20',
                'expiry_date'           => '2027-05-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019031401',
                'employee_name'         => 'Budi Santoso',
                'division'              => 'GA',
                'department'            => 'Facility Management',
                'notes'                 => 'Audit surveillance tahunan oleh Sucofindo dilaksanakan setiap bulan April.',
                'attachment_path'       => 'certifications/demo/iso-14001-budi-santoso.pdf',
            ],
            [
                'cert_type'             => CertType::PROFESSIONAL->value,
                'name'                  => 'Sertifikat Kompetensi Welder SMAW 3G & 4G',
                'description'           => 'Uji kompetensi pengelasan posisi 3G dan 4G untuk pekerjaan maintenance.',
                'issuing_organization'  => 'BNSP – LSP Teknologi',
                'certificate_number'    => 'BNSP-IND/2023/66121-01482',
                'issue_date'            => '2023-09-18',
                'expiry_date'           => '2028-09-17',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019061201',
                'employee_name'         => 'Agus Salim',
                'division'              => 'Engineering',
                'department'            => 'Maintenance Engineering',
                'notes'                 => 'Kompetensi disarankan direfresh setiap 5 tahun mengikuti standar industri.',
                'attachment_path'       => 'certifications/demo/welder-smaw-agus-salim.pdf',
            ],
            [
                'cert_type'             => CertType::PROFESSIONAL->value,
                'name'                  => 'Sertifikat Kompetensi Tenaga Teknik Ketenagalistrikan',
                'description'           => 'Kompetensi bidang instalasi dan pemeliharaan listrik tegangan rendah.',
                'issuing_organization'  => 'Kementerian Energi dan Sumber Daya Mineral RI',
                'certificate_number'    => 'SKT-2024-04551',
                'issue_date'            => '2024-04-22',
                'expiry_date'           => '2029-04-21',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019061201',
                'employee_name'         => 'Agus Salim',
                'division'              => 'Engineering',
                'department'            => 'Maintenance Engineering',
                'notes'                 => 'Berlaku 5 tahun; perpanjangan via SKTT Ketenagalistrikan.',
            ],

            // ── License (3) ───────────────────────────────────────────
            [
                'cert_type'             => CertType::LICENSE->value,
                'name'                  => 'Surat Izin Mengemudi (SIM) B I Umum',
                'description'           => 'SIM untuk kendaraan penumpang dan barang operasional distribusi.',
                'issuing_organization'  => 'Kepolisian RI – Satpas SIM Polda Metro Jaya',
                'certificate_number'    => 'SIM-B1-2025-08317',
                'issue_date'            => '2025-09-20',
                'expiry_date'           => '2030-09-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2022041801',
                'employee_name'         => 'Andi Wijaya',
                'division'              => 'Operations',
                'department'            => 'Logistics',
                'notes'                 => 'SIM B I Umum untuk kendaraan operasional di bawah 3.500 kg.',
            ],
            [
                'cert_type'             => CertType::LICENSE->value,
                'name'                  => 'Surat Izin Mengemudi (SIM) B II Umum',
                'description'           => 'SIM untuk kendaraan angkutan barang di atas 3.500 kg.',
                'issuing_organization'  => 'Kepolisian RI – Satpas SIM Polda Metro Jaya',
                'certificate_number'    => 'SIM-B2-2020-55129',
                'issue_date'            => '2020-10-05',
                'expiry_date'           => '2025-10-04',
                'status'                => CertStatus::REVOKED->value,
                'employee_id'           => '2022041801',
                'employee_name'         => 'Andi Wijaya',
                'division'              => 'Operations',
                'department'            => 'Logistics',
                'notes'                 => 'Dicabut karena pelanggaran lalu lintas (UU No. 22 Tahun 2009). Pengajuan ulang menunggu proses hukum selesai.',
            ],
            [
                'cert_type'             => CertType::LICENSE->value,
                'name'                  => 'Sertifikat Operator Forklift (SIO)',
                'description'           => 'Surat izin operasi forklift kapasitas 1,5–3 ton untuk aktivitas gudang.',
                'issuing_organization'  => 'Kementerian Ketenagakerjaan RI',
                'certificate_number'    => 'SIO-FL/2023/00987',
                'issue_date'            => '2023-06-12',
                'expiry_date'           => '2026-12-11',
                'status'                => CertStatus::SUSPENDED->value,
                'employee_id'           => '2022100701',
                'employee_name'         => 'Hendra Gunawan',
                'division'              => 'Warehouse',
                'department'            => 'Warehouse Operations',
                'notes'                 => 'Ditangguhkan sementara pasca insiden operasional; menunggu evaluasi ulang pengawas K3.',
            ],

            // ── Internal Training (2) ─────────────────────────────────
            [
                'cert_type'             => CertType::INTERNAL_TRAINING->value,
                'name'                  => 'Pelatihan Internal: SMK3 & Budaya K3',
                'description'           => 'Internal training kesadaran Sistem Manajemen K3 sesuai PP No. 50 Tahun 2012.',
                'issuing_organization'  => 'PT Mahakarya Sukses Indonesia – Learning & Development',
                'certificate_number'    => 'MSI/TRN/2025/0117',
                'issue_date'            => '2025-11-03',
                'expiry_date'           => null,
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2020051101',
                'employee_name'         => 'Dewi Lestari',
                'division'              => 'Human Resources',
                'department'            => 'Learning & Development',
                'notes'                 => 'Tidak memiliki masa berlaku; tercatat pada riwayat training internal karyawan.',
            ],
            [
                'cert_type'             => CertType::INTERNAL_TRAINING->value,
                'name'                  => 'Pelatihan Tanggap Darurat Kebakaran (ERT)',
                'description'           => 'Fire warden dan penggunaan APAR untuk evakuasi gedung kantor.',
                'issuing_organization'  => 'PT Mahakarya Sukses Indonesia – General Affairs',
                'certificate_number'    => 'MSI/GA/2026/ERT-0142',
                'issue_date'           => $fireErExpiry->copy()->subYear()->toDateString(),
                'expiry_date'          => $fireErExpiry->toDateString(),
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019031401',
                'employee_name'         => 'Budi Santoso',
                'division'              => 'GA',
                'department'            => 'Facility Management',
                'notes'                 => 'Sertifikat refresher tahunan; perpanjangan wajib dilakukan sebelum tanggal kedaluwarsa.',
                'attachment_path'       => 'certifications/demo/fire-ert-budi-santoso.pdf',
            ],

            // ── External Training (3) ─────────────────────────────────
            [
                'cert_type'             => CertType::EXTERNAL_TRAINING->value,
                'name'                  => 'Training Sertifikasi Internal Auditor ISO 45001:2018',
                'description'           => 'Lead internal auditor OH&S; mencakup teknik audit SMK3.',
                'issuing_organization'  => 'PT Surveyor Indonesia',
                'certificate_number'    => 'SI/45K/2025/0522',
                'issue_date'            => '2025-06-11',
                'expiry_date'           => '2028-06-10',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2020093001',
                'employee_name'         => 'Maya Anggraini',
                'division'              => 'Legal',
                'department'            => 'Legal Compliance',
                'notes'                 => 'Kompetensi untuk mendukung sertifikasi ulang ISO 45001 perusahaan tahun 2028.',
            ],
            [
                'cert_type'             => CertType::EXTERNAL_TRAINING->value,
                'name'                  => 'Seminar Nasional K3 & Produktivitas 2026',
                'description'           => 'Seminar eksternal penerapan K3 di sektor manufaktur dan konstruksi.',
                'issuing_organization'  => 'HSE Forum Indonesia',
                'certificate_number'    => 'HSE-SEM/2026/0341',
                'issue_date'            => '2026-08-20',
                'expiry_date'           => null,
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2018110201',
                'employee_name'         => 'Rudi Hartono',
                'division'              => 'Engineering',
                'department'            => 'Mechanical Engineering',
                'notes'                 => 'Sertifikat kehadiran seminar; tanpa masa berlaku.',
            ],
            [
                'cert_type'             => CertType::EXTERNAL_TRAINING->value,
                'name'                  => 'Pelatihan Pertolongan Pertama pada Gawat Darurat (PPGD)',
                'description'           => 'Basic first aid dan CPR untuk petugas security dan GA.',
                'issuing_organization'  => 'PMI (Palang Merah Indonesia) – Jakarta',
                'certificate_number'    => 'PMI-PPGD/2024/0777',
                'issue_date'            => '2024-03-15',
                'expiry_date'           => '2026-03-14',
                'status'                => CertStatus::EXPIRED->value,
                'employee_id'           => '2021121001',
                'employee_name'         => 'Joko Susilo',
                'division'              => 'GA',
                'department'            => 'Security',
                'notes'                 => 'Perpanjangan dijadwalkan setelah rekrutmen petugas keamanan baru.',
            ],

            // ── Compliance (2) ────────────────────────────────────────
            [
                'cert_type'             => CertType::COMPLIANCE->value,
                'name'                  => 'Sertifikat Ahli K3 Umum',
                'description'           => 'Kompetensi K3 umum untuk pengawas lapangan dan safety officer.',
                'issuing_organization'  => 'Kementerian Ketenagakerjaan RI',
                'certificate_number'    => 'KEP.333/AK3U/2025/PN.02.01',
                'issue_date'            => '2025-01-10',
                'expiry_date'           => '2028-01-09',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2018110201',
                'employee_name'         => 'Rudi Hartono',
                'division'              => 'Engineering',
                'department'            => 'Mechanical Engineering',
                'notes'                 => 'Berlaku 3 tahun; wajib perpanjangan sebelum Januari 2028.',
                'attachment_path'       => 'certifications/demo/k3-umum-rudi-hartono.pdf',
            ],
            [
                'cert_type'             => CertType::COMPLIANCE->value,
                'name'                  => 'Sertifikasi Kompetensi Auditor SMK3',
                'description'           => 'Kompetensi auditor Sistem Manajemen K3 sesuai PP No. 50 Tahun 2012.',
                'issuing_organization'  => 'Kementerian Ketenagakerjaan RI',
                'certificate_number'    => 'KEP.551/AK3U-AUD/2023/SMK3.01',
                'issue_date'            => '2023-11-20',
                'expiry_date'           => $auditorExpiry->toDateString(),
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021072301',
                'employee_name'         => 'Siti Rahayu',
                'division'              => 'Quality Control',
                'department'            => 'Quality Management System',
                'notes'                 => 'Mendukung pelaksanaan audit internal SMK3 pabrik dan gudang.',
                'attachment_path'       => 'certifications/demo/auditor-smk3-siti-rahayu.pdf',
            ],

            // ── Other (1) ─────────────────────────────────────────────
            [
                'cert_type'             => CertType::OTHER->value,
                'name'                  => 'Surat Keterangan Catatan Kepolisian (SKCK)',
                'description'           => 'SKCK untuk keperluan perpanjangan izin operasional security.',
                'issuing_organization'  => 'Polri – Polres Metro Jakarta Pusat',
                'certificate_number'    => 'SKCK/B/112/VI/2026/1703',
                'issue_date'            => '2026-06-02',
                'expiry_date'           => null,
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021121001',
                'employee_name'         => 'Joko Susilo',
                'division'              => 'GA',
                'department'            => 'Security',
                'notes'                 => 'Tidak memiliki masa berlaku; diperbarui saat perpanjangan izin security.',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($rows as $index => $data) {
            $data['created_by'] = self::CREATOR;
            // Ensure every certification record links to its own dummy PDF.
            $data['attachment_path'] ??= 'certifications/demo/' . self::ATTACHMENT_FILES[$index];

            // Expected code is only the lookup key; on a fresh DB the code
            // sequence below yields exactly SRT-00001..SRT-00015.
            $expectedCode = sprintf('SRT-%05d', $index + 1);
            $existing = Certification::where('cert_code', $expectedCode)->first();

            if (
                $existing
                && $existing->created_by === self::CREATOR
                && $existing->employee_id === $data['employee_id']
                && $existing->name === $data['name']
            ) {
                $existing->update($data);
                $this->ensureAttachmentPdf($data['attachment_path'], $data);
                $updated++;
                continue;
            }

            // Do NOT hardcode codes: let the existing generator issue the next
            // free SRT-xxxxx so DB uniqueness + sequence stay intact.
            $data['cert_code'] = $certService->generateCode();
            Certification::create($data);
            $this->ensureAttachmentPdf($data['attachment_path'], $data);
            $created++;
        }

        $this->command?->info(sprintf(
            'CertificationDummySeeder: %d created, %d updated (total %d).',
            $created,
            $updated,
            Certification::count(),
        ));
    }

    /**
     * Create the dummy PDF attachment for a seeded record when missing.
     * Idempotent by design: existing files are never overwritten.
     * Files are written through the Laravel `local` Storage disk
     * (storage/app/private) so the DB keeps a relative path, never a
     * raw filesystem location, and the attachment endpoint serves them
     * only through the controller whitelist.
     */
    private function ensureAttachmentPdf(string $path, array $data): void
    {
        if (trim($path) === '') {
            return;
        }

        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return;
        }

        $html = $this->renderCertificatePdfHtml($data);
        $disk->put($path, Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output());
    }

    private function renderCertificatePdfHtml(array $d): string
    {
        $e = fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $type = CertType::tryFrom((string) ($d['cert_type'] ?? ''))?->label() ?? $e($d['cert_type'] ?? '');
        $status = (string) ($d['status'] ?? '');

        $rows = [
            'Nama Sertifikasi' => $d['name'] ?? '',
            'Nomor Sertifikat' => $d['certificate_number'] ?? '-',
            'Nama Karyawan'    => $d['employee_name'] ?? '-',
            'Employee ID'      => $d['employee_id'] ?? '-',
            'Division'         => $d['division'] ?? '-',
            'Department'       => $d['department'] ?? '-',
            'Lembaga Penerbit' => $d['issuing_organization'] ?? '-',
            'Tanggal Terbit'   => $d['issue_date'] ?? '-',
            'Tanggal Kedaluwarsa' => $d['expiry_date'] ?? '-',
            'Status'           => $status,
        ];

        $body = '';
        foreach ($rows as $label => $value) {
            $body .= '<tr><td style="padding:6px 10px;border:1px solid #cbd5e1;width:35%;font-weight:bold;">' . $e($label)
                . '</td><td style="padding:6px 10px;border:1px solid #cbd5e1;">' . $e($value) . '</td></tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:DejaVu Sans, sans-serif;color:#0f172a;margin:0;padding:0;">'
            . '<div style="text-align:center;padding:24px 0 8px;"><h1 style="margin:0;font-size:22px;">SERTIFIKAT</h1>'
            . '<div style="font-size:12px;color:#475569;">CERTIFICATE</div></div>'
            . '<div style="text-align:center;margin:8px 0 20px;"><span style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:6px 14px;font-size:11px;border-radius:999px;">DUMMY DOCUMENT - LOCAL DEVELOPMENT</span></div>'
            . '<p style="font-size:13px;margin:0 0 16px;">Jenis: ' . $e($type) . '</p>'
            . '<table style="border-collapse:collapse;width:100%;font-size:12px;">' . $body . '</table>'
            . '<p style="font-size:10px;color:#94a3b8;margin-top:24px;">Dokumen ini adalah berkas dummy untuk pengembangan lokal dan bukan dokumen resmi.</p>'
            . '</body></html>';
    }
}
