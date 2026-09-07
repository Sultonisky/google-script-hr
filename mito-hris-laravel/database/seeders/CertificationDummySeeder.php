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
        'sni-rice-cooker-mito.pdf',
        'sni-air-fryer-mito.pdf',
        'sni-electric-oven-mito.pdf',
        'sni-fry-pan-stein.pdf',
        'sni-helm-keselamatan-andi-wijaya.pdf',
        'sni-kabel-listrik-andi-wijaya.pdf',
        'k3-forklift-hendra-gunawan.pdf',
        'k3-dasar-dewi-lestari.pdf',
        'fire-ert-budi-santoso.pdf',
        'auditor-iso45001-maya-anggraini.pdf',
        'iso-27001-rudi-hartono.pdf',
        'iso-22000-joko-susilo.pdf',
        'k3-umum-rudi-hartono.pdf',
        'auditor-smk3-siti-rahayu.pdf',
        'sni-apar-joko-susilo.pdf',
    ];

    public function run(): void
    {
        $certService = app(CertificationService::class);

        // Relative dates so the demo always keeps "expiring soon" states.
        $fireErExpiry = now()->addDays(120);

        $rows = [
            // ── SNI (5) ───────────────────────────────────────────────
            [
                'cert_type'             => CertType::SNI->value,
                'name'                  => 'Sertifikat SNI Produk Elektronik',
                'product_scope'         => 'Rice Cooker',
                'brand'                 => 'MITO',
                'description'           => 'Sertifikasi kesesuaian produk rice cooker terhadap standar nasional Indonesia.',
                'issuing_organization'  => 'BSN / Lembaga Sertifikasi Produk',
                'certificate_number'    => 'SNI-RC-MITO-001',
                'issue_date'            => '2025-02-15',
                'expiry_date'           => '2030-02-14',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021072301',
                'employee_name'         => 'Siti Rahayu',
                'division'              => 'Quality Control',
                'department'            => 'Quality Management System',
                'notes'                 => 'Berlaku untuk produk rice cooker brand MITO.',
                'attachment_path'       => 'certifications/demo/sni-rice-cooker-mito.pdf',
            ],
            [
                'cert_type'             => CertType::SNI->value,
                'name'                  => 'Sertifikat SNI Produk Elektronik',
                'product_scope'         => 'Air Fryer',
                'brand'                 => 'MITO',
                'description'           => 'Sertifikasi kesesuaian produk air fryer terhadap standar nasional Indonesia.',
                'issuing_organization'  => 'BSN / Lembaga Sertifikasi Produk',
                'certificate_number'    => 'SNI-AF-MITO-002',
                'issue_date'            => '2025-05-20',
                'expiry_date'           => '2030-05-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019031401',
                'employee_name'         => 'Budi Santoso',
                'division'              => 'GA',
                'department'            => 'Facility Management',
                'notes'                 => 'Berlaku untuk produk air fryer brand MITO.',
                'attachment_path'       => 'certifications/demo/sni-air-fryer-mito.pdf',
            ],
            [
                'cert_type'             => CertType::SNI->value,
                'name'                  => 'Sertifikat SNI Produk Elektronik',
                'product_scope'         => 'Electric Oven',
                'brand'                 => 'MITO',
                'description'           => 'Sertifikasi kesesuaian produk electric oven terhadap standar nasional Indonesia.',
                'issuing_organization'  => 'BSN / Lembaga Sertifikasi Produk',
                'certificate_number'    => 'SNI-EO-MITO-003',
                'issue_date'            => '2025-09-18',
                'expiry_date'           => '2030-09-17',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019061201',
                'employee_name'         => 'Agus Salim',
                'division'              => 'Engineering',
                'department'            => 'Maintenance Engineering',
                'notes'                 => 'Berlaku untuk produk electric oven brand MITO.',
                'attachment_path'       => 'certifications/demo/sni-electric-oven-mito.pdf',
            ],
            [
                'cert_type'             => CertType::SNI->value,
                'name'                  => 'Sertifikat SNI Cookware',
                'product_scope'         => 'Fry Pan',
                'brand'                 => 'STEIN',
                'description'           => 'Sertifikasi kesesuaian produk cookware fry pan terhadap standar nasional Indonesia.',
                'issuing_organization'  => 'BSN / Lembaga Sertifikasi Produk',
                'certificate_number'    => 'SNI-FP-STEIN-004',
                'issue_date'            => '2024-04-22',
                'expiry_date'           => '2029-04-21',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019061201',
                'employee_name'         => 'Agus Salim',
                'division'              => 'Engineering',
                'department'            => 'Maintenance Engineering',
                'notes'                 => 'Berlaku untuk produk fry pan brand STEIN.',
                'attachment_path'       => 'certifications/demo/sni-fry-pan-stein.pdf',
            ],

            // ── SNI (5) ───────────────────────────────────────────────
            [
                'cert_type'             => CertType::SNI->value,
                'name'                  => 'Sertifikat SNI Cookware',
                'product_scope'         => 'Sauce Pan',
                'brand'                 => 'STEIN',
                'description'           => 'Sertifikasi kesesuaian produk cookware sauce pan terhadap standar nasional Indonesia.',
                'issuing_organization'  => 'BSN / Lembaga Sertifikasi Produk',
                'certificate_number'    => 'SNI-SP-STEIN-005',
                'issue_date'           => '2025-09-20',
                'expiry_date'          => '2030-09-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2022041801',
                'employee_name'         => 'Andi Wijaya',
                'division'              => 'Operations',
                'department'            => 'Logistics',
                'notes'                 => 'Berlaku untuk produk sauce pan brand STEIN.',
                'attachment_path'       => 'certifications/demo/sni-sauce-pan-stein.pdf',
            ],
            [
                'cert_type'             => CertType::ISO->value,
                'name'                  => 'Sertifikat ISO 9001:2015',
                'company_scope'         => 'Quality Management System',
                'brand'                 => 'MITO Group',
                'description'           => 'Sistem manajemen mutu perusahaan untuk memastikan konsistensi proses dan layanan.',
                'issuing_organization'  => 'TÜV Rheinland Indonesia',
                'certificate_number'    => 'ISO9001-MITO-006',
                'issue_date'            => '2025-10-05',
                'expiry_date'           => '2028-10-04',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2022041801',
                'employee_name'         => 'Andi Wijaya',
                'division'              => 'Operations',
                'department'            => 'Logistics',
                'notes'                 => 'Audit surveillance mutu perusahaan dilakukan secara berkala.',
                'attachment_path'       => 'certifications/demo/iso-9001-mito-group.pdf',
            ],
            [
                'cert_type'             => CertType::ISO->value,
                'name'                  => 'Sertifikat ISO 14001:2015',
                'company_scope'         => 'Environmental Management System',
                'brand'                 => 'MITO Group',
                'description'           => 'Sistem manajemen lingkungan perusahaan untuk pengendalian dampak operasional.',
                'issuing_organization'  => 'PT Sucofindo',
                'certificate_number'    => 'ISO14001-MITO-007',
                'issue_date'            => '2025-06-12',
                'expiry_date'           => '2028-06-11',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2022100701',
                'employee_name'         => 'Hendra Gunawan',
                'division'              => 'Warehouse',
                'department'            => 'Warehouse Operations',
                'notes'                 => 'Audit lingkungan dan surveillance perusahaan dilakukan setiap tahun.',
                'attachment_path'       => 'certifications/demo/iso-14001-mito-group.pdf',
            ],

            // ── ISO (3) ───────────────────────────────────────────────
            [
                'cert_type'             => CertType::ISO->value,
                'name'                  => 'Sertifikat ISO 45001:2018',
                'company_scope'         => 'Occupational Health & Safety',
                'brand'                 => 'MITO Group',
                'description'           => 'Sistem manajemen keselamatan dan kesehatan kerja perusahaan.',
                'issuing_organization'  => 'PT SGS Indonesia',
                'certificate_number'    => 'ISO45001-MITO-008',
                'issue_date'            => '2025-11-03',
                'expiry_date'           => '2028-11-02',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2020051101',
                'employee_name'         => 'Dewi Lestari',
                'division'              => 'Human Resources',
                'department'            => 'Learning & Development',
                'notes'                 => 'Audit surveillance tahunan memastikan sistem keselamatan perusahaan berjalan efektif.',
                'attachment_path'       => 'certifications/demo/iso-45001-mito-group.pdf',
            ],
            [
                'cert_type'             => CertType::K3->value,
                'name'                  => 'Sertifikat K3',
                'company_scope'         => 'Keselamatan & Kesehatan Kerja',
                'brand'                 => 'MITO Group',
                'description'           => 'Sertifikasi keselamatan dan kesehatan kerja untuk lingkungan operasional perusahaan.',
                'issuing_organization'  => 'PT Mahakarya Sukses Indonesia – General Affairs',
                'certificate_number'    => 'K3-MITO-009',
                'issue_date'           => $fireErExpiry->copy()->subYear()->toDateString(),
                'expiry_date'          => $fireErExpiry->toDateString(),
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2019031401',
                'employee_name'         => 'Budi Santoso',
                'division'              => 'GA',
                'department'            => 'Facility Management',
                'notes'                 => 'Sertifikat refresher tahunan; perpanjangan wajib dilakukan sebelum tanggal kedaluwarsa.',
                'attachment_path'       => 'certifications/demo/k3-mito-group.pdf',
            ],

            // ── Food Safety (2) ──────────────────────────────────────
            [
                'cert_type'             => CertType::FOOD_SAFETY->value,
                'name'                  => 'LFGB Certificate',
                'product_scope'         => 'Food Contact / Cookware',
                'brand'                 => 'STEIN',
                'description'           => 'Pengujian keamanan material cookware untuk kontak langsung dengan makanan.',
                'issuing_organization'  => 'Intertek Indonesia',
                'certificate_number'    => 'LFGB-STEIN-010',
                'issue_date'            => '2025-06-11',
                'expiry_date'           => '2028-06-10',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2020093001',
                'employee_name'         => 'Maya Anggraini',
                'division'              => 'Legal',
                'department'            => 'Legal Compliance',
                'notes'                 => 'Memastikan material cookware aman digunakan untuk kontak makanan.',
                'attachment_path'       => 'certifications/demo/lfgb-stein.pdf',
            ],
            [
                'cert_type'             => CertType::FOOD_SAFETY->value,
                'name'                  => 'FDA Compliance',
                'product_scope'         => 'Food Contact / Cookware',
                'brand'                 => 'STEIN',
                'description'           => 'Kepatuhan material cookware terhadap persyaratan keamanan pangan dan food contact.',
                'issuing_organization'  => 'FDA Compliance Laboratory',
                'certificate_number'    => 'FDA-STEIN-011',
                'issue_date'            => '2026-08-20',
                'expiry_date'           => '2029-08-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2018110201',
                'employee_name'         => 'Rudi Hartono',
                'division'              => 'Engineering',
                'department'            => 'Mechanical Engineering',
                'notes'                 => 'Dokumen kepatuhan untuk produk cookware yang bersentuhan dengan makanan.',
                'attachment_path'       => 'certifications/demo/fda-compliance-stein.pdf',
            ],
            [
                'cert_type'             => CertType::PRODUCT_SAFETY->value,
                'name'                  => 'Product Safety Certificate',
                'product_scope'         => 'Household Products',
                'brand'                 => 'KLAR',
                'description'           => 'Sertifikasi keselamatan produk rumah tangga untuk penggunaan konsumen.',
                'issuing_organization'  => 'TÜV Rheinland Indonesia',
                'certificate_number'    => 'PSC-KLAR-012',
                'issue_date'            => '2024-03-15',
                'expiry_date'           => '2027-03-14',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021121001',
                'employee_name'         => 'Joko Susilo',
                'division'              => 'GA',
                'department'            => 'Security',
                'notes'                 => 'Berlaku untuk lini household products brand KLAR.',
                'attachment_path'       => 'certifications/demo/product-safety-klar.pdf',
            ],

            // ── Product Safety (4) ────────────────────────────────────
            [
                'cert_type'             => CertType::PRODUCT_SAFETY->value,
                'name'                  => 'Product Compliance Certificate',
                'product_scope'         => 'Consumer Products',
                'brand'                 => 'BREX',
                'description'           => 'Kepatuhan produk konsumen terhadap persyaratan keselamatan dan regulasi yang berlaku.',
                'issuing_organization'  => 'Bureau Veritas Indonesia',
                'certificate_number'    => 'PCC-BREX-013',
                'issue_date'            => '2025-01-10',
                'expiry_date'           => '2028-01-09',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2018110201',
                'employee_name'         => 'Rudi Hartono',
                'division'              => 'Engineering',
                'department'            => 'Mechanical Engineering',
                'notes'                 => 'Berlaku untuk lini consumer products brand BREX.',
                'attachment_path'       => 'certifications/demo/product-compliance-brex.pdf',
            ],
            [
                'cert_type'             => CertType::PRODUCT_SAFETY->value,
                'name'                  => 'Material Safety / Compliance',
                'product_scope'         => 'Kitchen / Household Product',
                'brand'                 => 'KLAR',
                'description'           => 'Dokumen keselamatan material dan kepatuhan untuk produk kitchen dan household.',
                'issuing_organization'  => 'SGS Indonesia',
                'certificate_number'    => 'MSC-KLAR-014',
                'issue_date'            => '2025-11-20',
                'expiry_date'           => '2028-11-19',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021072301',
                'employee_name'         => 'Siti Rahayu',
                'division'              => 'Quality Control',
                'department'            => 'Quality Management System',
                'notes'                 => 'Mendukung verifikasi keamanan material pada produk brand KLAR.',
                'attachment_path'       => 'certifications/demo/material-safety-klar.pdf',
            ],

            [
                'cert_type'             => CertType::PRODUCT_SAFETY->value,
                'name'                  => 'Product Testing Certificate',
                'product_scope'         => 'Consumer Electronics',
                'brand'                 => 'MITO',
                'description'           => 'Hasil pengujian keselamatan dan performa produk elektronik konsumen.',
                'issuing_organization'  => 'Intertek Indonesia',
                'certificate_number'    => 'PTC-MITO-015',
                'issue_date'            => '2026-06-02',
                'expiry_date'           => '2029-06-01',
                'status'                => CertStatus::ACTIVE->value,
                'employee_id'           => '2021121001',
                'employee_name'         => 'Joko Susilo',
                'division'              => 'GA',
                'department'            => 'Security',
                'notes'                 => 'Dokumen pengujian untuk produk consumer electronics brand MITO.',
                'attachment_path'       => 'certifications/demo/product-testing-mito.pdf',
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
        $e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
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
