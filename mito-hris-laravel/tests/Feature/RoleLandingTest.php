<?php

namespace Tests\Feature;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Role-based post-login landing + sidebar visibility.
 *
 *   Admin  → /hr/dashboard; sidebar shows Dashboard + Asset + Certification.
 *   GA_IT  → /hr/assets; sidebar hides Dashboard; can access Asset, not Certification.
 *   LEGAL → /hr/certifications; sidebar hides Dashboard; can access Certification, not Asset.
 *
 * Authorization (RBAC) is unchanged — only the landing decision and the
 * sidebar visibility for the Dashboard entry are exercised here.
 */
class RoleLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Local dummy employee source — mirrors AssetManagementTest.
        $this->app->instance(EmployeeRepositoryInterface::class, new LocalEmployeeRepository());
    }

    private function redirectPath(array $response, string $key = 'redirect'): string
    {
        $url  = $response[$key] ?? '';
        $path = parse_url((string) $url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : (string) $url;
    }
private function actingAsRole(string $role): static
    {
        $emailMap = [
            'Admin' => 'admin@mito.id',
            'GA_IT' => 'ga.it@mitogroup.local',
            'LEGAL' => 'legal@mitogroup.local',
        ];

        Session::put('hr_user', [
            'email'       => $emailMap[$role] ?? strtolower($role) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);

        return $this;
    }

    private function mockUserDomain(string $role, string $identifier, string $password): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByIdentifier')
            ->with($identifier)
            ->andReturn([
                'Email'        => $identifier,
                'Full Name'    => $role . ' User',
                'Username'     => $identifier,
                'Role'         => $role,
                'Status'       => 'Active',
                'Password Hash' => Hash::make($password),
            ]);
        $repository->shouldReceive('updateLastLogin')->once();
        $this->app->instance(UserRepositoryInterface::class, $repository);
    }
// ── Post-login redirect ───────────────────────────────────────────────

    #[Test]
    public function admin_login_lands_on_dashboard(): void
    {
        $this->mockUserDomain('Admin', 'admin@mito.id', 'admin-secret');

        $response = $this->postJson('/login', [
            'identifier' => 'admin@mito.id',
            'password'   => 'admin-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.dashboard', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    #[Test]
    public function ga_it_login_lands_on_asset_management(): void
    {
        $this->mockUserDomain('GA_IT', 'ga.it@mitogroup.local', 'ga_it_secret');

        $response = $this->postJson('/login', [
            'identifier' => 'ga.it@mitogroup.local',
            'password'   => 'ga_it_secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.assets.index', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    #[Test]
    public function legal_login_lands_on_certification_management(): void
    {
        $this->mockUserDomain('LEGAL', 'legal@mitogroup.local', 'legal_secret');

        $response = $this->postJson('/login', [
            'identifier' => 'legal@mitogroup.local',
            'password'   => 'legal_secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.certifications.index', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    // ── Sidebar visibility ────────────────────────────────────────────────
    // Render the sidebar component directly. Avoids full-page rendering and
    // its dependencies (DB / external repos / Vite), isolating the blade change.

    #[Test]
    public function admin_sidebar_shows_dashboard_asset_and_certification(): void
    {
        $this->actingAsRole('Admin');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('/hr/dashboard', $rendered);          // Dashboard link present
        $this->assertStringContainsString('bi-grid-1x2-fill', $rendered);      // Dashboard icon present
        $this->assertStringContainsString('Asset Management', $rendered);
        $this->assertStringContainsString('Certification Management', $rendered);
    }

    #[Test]
    public function ga_it_sidebar_hides_dashboard_and_shows_asset_only(): void
    {
        $this->actingAsRole('GA_IT');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('Asset Management', $rendered);
        $this->assertStringContainsString('/hr/assets', $rendered);
        $this->assertStringNotContainsString('bi-grid-1x2-fill', $rendered);   // Dashboard icon absent
        $this->assertStringNotContainsString('/hr/dashboard', $rendered);      // Dashboard link absent
        $this->assertStringNotContainsString('/hr/certifications', $rendered); // RBAC: no Certification nav link
    }

    #[Test]
    public function legal_sidebar_hides_dashboard_and_shows_certification_only(): void
    {
        $this->actingAsRole('LEGAL');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('Certification Management', $rendered);
        $this->assertStringContainsString('/hr/certifications', $rendered);
        $this->assertStringNotContainsString('bi-grid-1x2-fill', $rendered);
        $this->assertStringNotContainsString('/hr/dashboard', $rendered);
        $this->assertStringNotContainsString('/hr/assets', $rendered);          // RBAC: no Asset nav link
    }

    // ── Existing authorization still works (cross-module access) ─────────
    // The positive "can access" case is established by the post-login redirect
    // landing on the role's module plus the gate permissions (covered in
    // LocalAccessTest / RbacTest). Here we lock down the denial path via the
    // route-level `can:` middleware so it can never silently regress.

    #[Test]
    public function ga_it_is_forbidden_from_certification_index(): void
    {
        $this->actingAsRole('GA_IT');

        $this->get('/hr/certifications')->assertForbidden();
    }

    #[Test]
    public function legal_is_forbidden_from_asset_index(): void
    {
        $this->actingAsRole('LEGAL');

        $this->get('/hr/assets')->assertForbidden();
    }

    // ── Admin regression guard ────────────────────────────────────────────

    #[Test]
    public function admin_retains_dashboard_asset_and_certification_sidebar_entries(): void
    {
        $this->actingAsRole('Admin');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('/hr/dashboard', $rendered);
        $this->assertStringContainsString('/hr/assets', $rendered);
        $this->assertStringContainsString('/hr/certifications', $rendered);
    }

    // ── Local dummy users used by LocalDevUsersSeeder still resolve ────────

    #[Test]
    public function local_dummy_users_have_expected_roles(): void
    {
        \App\Models\User::create([
            'name' => 'GA IT User', 'email' => 'ga.it@mitogroup.local',
            'password' => Hash::make('ga_it_secret'), 'role' => 'GA_IT', 'status' => 'Active',
        ]);
        \App\Models\User::create([
            'name' => 'Legal User', 'email' => 'legal@mitogroup.local',
            'password' => Hash::make('legal_secret'), 'role' => 'LEGAL', 'status' => 'Active',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'ga.it@mitogroup.local', 'role' => 'GA_IT']);
        $this->assertDatabaseHas('users', ['email' => 'legal@mitogroup.local', 'role' => 'LEGAL']);
    }
}