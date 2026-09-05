<?php

namespace Tests\Feature;

use App\Enums\CertStatus;
use App\Enums\CertType;
use App\Models\Certification;
use Database\Seeders\CertificationDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificationDummySeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedTwice(): void
    {
        $this->seed(CertificationDummySeeder::class);
        $this->seed(CertificationDummySeeder::class);
    }

    #[Test]
    public function certification_dummy_seeder_is_idempotent_and_produces_15_rows(): void
    {
        $this->seedTwice();

        $this->assertDatabaseCount('certifications', 15);

        // Codes are unique and follow the SRT-xxxxx generator format.
        $codes = Certification::pluck('cert_code')->all();
        $this->assertCount(15, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^SRT-\d{5}$/', $code);
        }

        // Every CertType value is covered.
        $expectedTypes = [
            CertType::PROFESSIONAL->value       => 4,
            CertType::LICENSE->value            => 3,
            CertType::INTERNAL_TRAINING->value  => 2,
            CertType::EXTERNAL_TRAINING->value  => 3,
            CertType::COMPLIANCE->value         => 2,
            CertType::OTHER->value              => 1,
        ];
        foreach ($expectedTypes as $typeValue => $count) {
            $this->assertSame($count, Certification::ofType($typeValue)->count(), "Unexpected count for {$typeValue}");
        }

        // Every status is covered.
        $expectedStatuses = [
            CertStatus::ACTIVE->value    => 11,
            CertStatus::EXPIRED->value   => 2,
            CertStatus::SUSPENDED->value => 1,
            CertStatus::REVOKED->value   => 1,
        ];
        foreach ($expectedStatuses as $statusValue => $count) {
            $this->assertSame($count, Certification::ofStatus($statusValue)->count(), "Unexpected count for {$statusValue}");
        }

        $today = now()->toDateString();

        // Expired (past expiry date) and no-expiry scenarios.
        $this->assertSame(3, Certification::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count());
        $this->assertSame(3, Certification::whereNull('expiry_date')->count());

        // Active certificates expiring within 30 and within 90 days.
        $this->assertSame(1, Certification::where('status', CertStatus::ACTIVE->value)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->count());
        $this->assertSame(1, Certification::where('status', CertStatus::ACTIVE->value)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now()->addDays(30)->toDateString())
            ->where('expiry_date', '<=', now()->addDays(90)->toDateString())
            ->count());
    }

    #[Test]
    public function certification_index_renders_seeded_data_for_legal(): void
    {
        Session::put('hr_user', [
            'email'       => 'legal@mito.id',
            'fullName'    => 'Legal User',
            'role'        => 'LEGAL',
            'permissions' => config('hris.auth.role_permissions')['LEGAL'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);

        $this->seed(CertificationDummySeeder::class);

        $this->get('/hr/certifications')
            ->assertOk()
            ->assertSee('Daftar Sertifikasi')
            ->assertSee('Sertifikat ISO 9001:2015 Quality Management System')
            ->assertSee('Sertifikat ISO 14001:2015 Environmental Management System')
            ->assertSee('Sertifikat Operator Forklift (SIO)')
            ->assertSee('Expiring Soon (30 hari)');
    }
}
