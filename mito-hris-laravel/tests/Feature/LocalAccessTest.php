<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalAccessTest extends TestCase
{
    use RefreshDatabase;

    private const GA_IT_EMAIL = 'ga.it@mitogroup.local';
    private const GA_IT_PASS  = 'ga_it_secret';
    private const LEGAL_EMAIL = 'legal@mitogroup.local';
    private const LEGAL_PASS  = 'legal_secret';

    private function seedUsers(): void
    {
        User::create([
            'name' => 'GA IT User', 'email' => self::GA_IT_EMAIL,
            'password' => Hash::make(self::GA_IT_PASS), 'role' => 'GA_IT', 'status' => 'Active',
        ]);
        User::create([
            'name' => 'Legal User', 'email' => self::LEGAL_EMAIL,
            'password' => Hash::make(self::LEGAL_PASS), 'role' => 'LEGAL', 'status' => 'Active',
        ]);
    }

    private function actingAsRole(string $role): static
    {
        $emailMap = ['GA_IT' => self::GA_IT_EMAIL, 'LEGAL' => self::LEGAL_EMAIL];
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

    // ── GA_IT gates ──

    #[Test] public function ga_it_has_view_asset(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertTrue(Gate::allows('view_asset'));
    }

    #[Test] public function ga_it_has_edit_asset(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertTrue(Gate::allows('edit_asset'));
    }

    #[Test] public function ga_it_denied_view_certification(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertFalse(Gate::allows('view_certification'));
    }

    #[Test] public function ga_it_denied_manage_certification(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertFalse(Gate::allows('manage_certification'));
    }

    // ── LEGAL gates ──

    #[Test] public function legal_has_view_certification(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertTrue(Gate::allows('view_certification'));
    }

    #[Test] public function legal_has_manage_certification(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertTrue(Gate::allows('manage_certification'));
    }

    #[Test] public function legal_denied_view_asset(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertFalse(Gate::allows('view_asset'));
    }

    #[Test] public function legal_denied_edit_asset(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertFalse(Gate::allows('edit_asset'));
    }

    // ── DB user creation ──

    #[Test] public function ga_it_user_exists_in_database(): void
    {
        $this->seedUsers();
        $this->assertDatabaseHas('users', ['email' => self::GA_IT_EMAIL, 'role' => 'GA_IT']);
    }

    #[Test] public function legal_user_exists_in_database(): void
    {
        $this->seedUsers();
        $this->assertDatabaseHas('users', ['email' => self::LEGAL_EMAIL, 'role' => 'LEGAL']);
    }

    #[Test] public function ga_it_password_is_bcrypt(): void
    {
        $this->seedUsers();
        $user = User::where('email', self::GA_IT_EMAIL)->first();
        $this->assertTrue(Hash::check(self::GA_IT_PASS, $user->password));
    }

    #[Test] public function legal_password_is_bcrypt(): void
    {
        $this->seedUsers();
        $user = User::where('email', self::LEGAL_EMAIL)->first();
        $this->assertTrue(Hash::check(self::LEGAL_PASS, $user->password));
    }

    // ── Auth service DB backed ──

    #[Test] public function auth_service_finds_db_user_by_identifier(): void
    {
        $this->seedUsers();
        $repo = app(\App\Repositories\Contracts\UserRepositoryInterface::class);

        $user = $repo->findByIdentifier(self::GA_IT_EMAIL);
        $this->assertNotNull($user);
        $this->assertSame('GA_IT', $user['Role']);

        $user = $repo->findByIdentifier(self::LEGAL_EMAIL);
        $this->assertNotNull($user);
        $this->assertSame('LEGAL', $user['Role']);
    }

    #[Test] public function auth_service_returns_null_for_unknown_identifier(): void
    {
        $this->seedUsers();
        $repo = app(\App\Repositories\Contracts\UserRepositoryInterface::class);
        $this->assertNull($repo->findByIdentifier('nobody@example.com'));
    }

    // ── Config role mapping ──

    #[Test] public function ga_it_is_valid_internal_role(): void
    {
        $this->assertContains('GA_IT', config('hris.auth.valid_roles_internal'));
    }

    #[Test] public function legal_is_valid_internal_role(): void
    {
        $this->assertContains('LEGAL', config('hris.auth.valid_roles_internal'));
    }

    #[Test] public function ga_it_permission_mapping(): void
    {
        $perms = config('hris.auth.role_permissions.GA_IT');
        $this->assertContains('view_asset', $perms);
        $this->assertContains('edit_asset', $perms);
        $this->assertNotContains('view_certification', $perms);
        $this->assertNotContains('manage_certification', $perms);
    }

    #[Test] public function legal_permission_mapping(): void
    {
        $perms = config('hris.auth.role_permissions.LEGAL');
        $this->assertContains('view_certification', $perms);
        $this->assertContains('manage_certification', $perms);
        $this->assertNotContains('view_asset', $perms);
        $this->assertNotContains('edit_asset', $perms);
    }

    // ── Unauthenticated ──

    #[Test] public function unauthenticated_assets_redirects_to_login(): void
    {
        $this->get('/hr/assets')->assertRedirect();
    }

    #[Test] public function unauthenticated_certifications_redirects_to_login(): void
    {
        $this->get('/hr/certifications')->assertRedirect();
    }

    #[Test] public function login_route_exists_and_reaches_controller(): void
    {
        // Vite manifest may be missing in test env (pre-existing); use expectsJson to skip view rendering.
        $response = $this->postJson('/login', ['identifier' => 'nobody@test.com', 'password' => 'x']);
        // Route resolves — either validation error or login failure (both are correct).
        $this->assertContains($response->status(), [422, 302, 419]);
    }

    // ── RBAC normalization ──

    #[Test] public function rbac_normalizes_ga_it_role(): void
    {
        $this->assertSame('GA_IT', \App\Support\Rbac::normalizeRole('GA_IT'));
    }

    #[Test] public function rbac_normalizes_legal_role(): void
    {
        $this->assertSame('LEGAL', \App\Support\Rbac::normalizeRole('LEGAL'));
    }

    #[Test] public function migrated_ga_it_user_allows_view_asset(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertTrue(Gate::allows('view_asset'));
    }

    #[Test] public function migrated_legal_user_allows_manage_certification(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertTrue(Gate::allows('manage_certification'));
    }

    #[Test] public function migrated_ga_it_user_denies_manage_certification(): void
    {
        $this->actingAsRole('GA_IT');
        $this->assertFalse(Gate::allows('manage_certification'));
    }

    #[Test] public function migrated_legal_user_denies_view_asset(): void
    {
        $this->actingAsRole('LEGAL');
        $this->assertFalse(Gate::allows('view_asset'));
    }
}
