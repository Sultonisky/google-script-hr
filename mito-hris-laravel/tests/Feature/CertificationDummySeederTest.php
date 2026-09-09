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

        // Every supported CertType value is covered.
        $expectedTypes = [
            CertType::SNI->value => 5,
            CertType::ISO->value => 3,
            CertType::K3->value  => 1,
            CertType::FOOD_SAFETY->value => 2,
            CertType::PRODUCT_SAFETY->value => 4,
        ];
        foreach ($expectedTypes as $typeValue => $count) {
            $this->assertSame($count, Certification::ofType($typeValue)->count(), "Unexpected count for {$typeValue}");
        }

        $this->assertDatabaseHas('certifications', [
            'name'          => 'Sertifikat SNI Produk Elektronik',
            'product_scope' => 'Rice Cooker',
            'brand'         => 'MITO',
        ]);
        $this->assertDatabaseHas('certifications', [
            'name'          => 'LFGB Certificate',
            'product_scope' => 'Food Contact / Cookware',
            'brand'         => 'STEIN',
        ]);

        // Every status is covered.
        $expectedStatuses = [CertStatus::ACTIVE->value => 15];
        foreach ($expectedStatuses as $statusValue => $count) {
            $this->assertSame($count, Certification::ofStatus($statusValue)->count(), "Unexpected count for {$statusValue}");
        }

        $today = now()->toDateString();

        // The supplied demo matrix is entirely active and currently valid.
        $this->assertSame(0, Certification::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count());
        $this->assertSame(0, Certification::whereNull('expiry_date')->count());

        // Active certificates expiring within 30 and within 90 days.
        $this->assertSame(0, Certification::where('status', CertStatus::ACTIVE->value)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->count());
        $this->assertSame(0, Certification::where('status', CertStatus::ACTIVE->value)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now()->addDays(30)->toDateString())
            ->where('expiry_date', '<=', now()->addDays(90)->toDateString())
            ->count());
    }

    #[Test]
    public function certification_index_renders_seeded_data_for_legal(): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email'       => 'legal@mito.id',
            'fullName'    => 'Legal User',
            'role'        => 'LEGAL',
            'permissions' => config('hris.auth.role_permissions')['LEGAL'] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

        $this->seed(CertificationDummySeeder::class);

        $this->get('/hr/certifications')
            ->assertOk()
            ->assertSee('Daftar Sertifikasi')
            ->assertSee('Sertifikat SNI Produk Elektronik')
            ->assertSee('Sertifikat ISO 9001:2015')
            ->assertSee('LFGB Certificate')
            ->assertSee('Product Testing Certificate');
    }
}
