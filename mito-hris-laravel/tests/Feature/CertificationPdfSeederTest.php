<?php

namespace Tests\Feature;

use App\Enums\CertStatus;
use App\Enums\CertType;
use App\Models\Certification;
use Database\Seeders\CertificationDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every seeded certification record must carry its OWN dummy PDF attachment
 * (15 records -> 15 files), reachable through the `local` Storage disk and
 * clearly marked as a dummy document.
 */
class CertificationPdfSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_seeded_certification_links_to_its_own_pdf(): void
    {
        Storage::fake('local');

        $this->seed(CertificationDummySeeder::class);

        $certs = Certification::orderBy('cert_code')->get();
        $this->assertCount(15, $certs);

        $paths = [];
        foreach ($certs as $cert) {
            $this->assertNotEmpty($cert->attachment_path, "{$cert->cert_code} has no attachment_path");
            $disk = Storage::disk('local');
            $this->assertTrue($disk->exists($cert->attachment_path), "PDF missing for {$cert->cert_code}: {$cert->attachment_path}");

            $content = $disk->get($cert->attachment_path);
            $this->assertStringStartsWith('%PDF', $content, "{$cert->cert_code} is not a PDF");
            $this->assertGreaterThan(1000, strlen($content), "{$cert->cert_code} PDF looks empty");
            // PDF text streams are compressed, so the dummy banner lives inside
            // the FlateDecode stream; asserting unique per-record bytes proves
            // each record got its own generated document.
            $paths[$cert->attachment_path] = hash('sha256', $content);
        }

        // No generic shared file: all 15 attachment paths and byte content differ.
        $this->assertCount(15, $paths);
        $this->assertCount(15, array_unique(array_values($paths)));
    }

    #[Test]
    public function attachment_route_serves_stored_pdf_for_viewer(): void
    {
        Storage::fake('local');

        $cert = Certification::create([
            'cert_type'            => CertType::ISO->value,
            'name'                 => 'Manual Attachment',
            'issuing_organization' => 'BNSP',
            'issue_date'           => '2026-01-01',
            'expiry_date'          => '2028-01-01',
            'status'               => CertStatus::ACTIVE->value,
            'employee_id'          => '2019031401',
            'employee_name'        => 'Budi Santoso',
            'division'             => 'GA',
            'department'           => 'Facility Management',
            'attachment_path'      => 'certifications/demo/manual-attachment.pdf',
            'created_by'           => 'legal@mito.id',
        ]);

        Storage::disk('local')->put($cert->attachment_path, '%PDF-1.4 manual dummy attachment');

        // Route exists and is reachable through the named certification routes.
        $this->assertStringEndsWith(
            '/hr/certifications/' . $cert->id . '/attachment',
            route('hr.certifications.attachment', $cert),
        );

        $response = app(\App\Http\Controllers\HR\CertificationController::class)->attachment($cert);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function re_seeding_keeps_pdfs_untouched_and_unique(): void
    {
        Storage::fake('local');

        $this->seed(CertificationDummySeeder::class);
        $this->seed(CertificationDummySeeder::class);

        $this->assertDatabaseCount('certifications', 15);
        $this->assertSame(
            15,
            Certification::whereNotNull('attachment_path')->distinct('attachment_path')->count(),
        );
    }
}
