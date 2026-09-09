<?php

namespace Tests\Feature;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Local\LocalEmployeeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Role-based post-login landing + sidebar visibility.
 *
 *   All HRIS users → /hr/dashboard; the HRIS sidebar never exposes dedicated portals.
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

        Session::put('hr_user', $this->migratedTestUser([
            'email'       => $emailMap[$role] ?? strtolower($role) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]));

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

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)->postJson('/login', [
            'identifier' => 'admin@mito.id',
            'password'   => 'admin-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.dashboard', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    #[Test]
    public function ga_it_login_stays_in_hris(): void
    {
        $this->mockUserDomain('GA_IT', 'ga.it@mitogroup.local', 'ga_it_secret');

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)->postJson('/login', [
            'identifier' => 'ga.it@mitogroup.local',
            'password'   => 'ga_it_secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.dashboard', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    #[Test]
    public function legal_login_stays_in_hris(): void
    {
        $this->mockUserDomain('LEGAL', 'legal@mitogroup.local', 'legal_secret');

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)->postJson('/login', [
            'identifier' => 'legal@mitogroup.local',
            'password'   => 'legal_secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(parse_url(route('hr.dashboard', [], false), PHP_URL_PATH), $this->redirectPath($response->json()));
    }

    // ── Sidebar visibility ────────────────────────────────────────────────
    // Render the sidebar component directly. Avoids full-page rendering and
    // its dependencies (DB / external repos / Vite), isolating the blade change.

    #[Test]
    public function admin_sidebar_shows_dashboard_without_dedicated_portals(): void
    {
        $this->actingAsRole('Admin');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('/hr/dashboard', $rendered);          // Dashboard link present
        $this->assertStringContainsString('bi-grid-1x2-fill', $rendered);      // Dashboard icon present
        $this->assertStringNotContainsString('Asset Management', $rendered);
        $this->assertStringNotContainsString('Certification Management', $rendered);
    }

    #[Test]
    public function ga_it_sidebar_stays_hris_only(): void
    {
        $this->actingAsRole('GA_IT');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('bi-grid-1x2-fill', $rendered);
        $this->assertStringContainsString('/hr/dashboard', $rendered);
        $this->assertStringNotContainsString('Asset Management', $rendered);
        $this->assertStringNotContainsString('Certification Management', $rendered);
    }

    #[Test]
    public function legal_sidebar_stays_hris_only(): void
    {
        $this->actingAsRole('LEGAL');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('bi-grid-1x2-fill', $rendered);
        $this->assertStringContainsString('/hr/dashboard', $rendered);
        $this->assertStringNotContainsString('Asset Management', $rendered);
        $this->assertStringNotContainsString('Certification Management', $rendered);
    }

    // ── Admin regression guard ────────────────────────────────────────────

    #[Test]
    public function admin_retains_dashboard_without_dedicated_portal_entries(): void
    {
        $this->actingAsRole('Admin');

        $rendered = (string) $this->view('components.hr-sidebar');

        $this->assertStringContainsString('/hr/dashboard', $rendered);
        $this->assertStringNotContainsString('/hr/assets', $rendered);
        $this->assertStringNotContainsString('/hr/certifications', $rendered);
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