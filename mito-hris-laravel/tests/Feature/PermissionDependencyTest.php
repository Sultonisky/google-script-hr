<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PermissionResolver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Permission Dependency Regression Tests
 *
 * Validates that feature permissions cannot be granted without their
 * required portal access permissions, and that the Permission Management
 * API normalizes dependencies automatically.
 */
class PermissionDependencyTest extends TestCase
{
    private function makeUserRow(array $overrides = []): array
    {
        return array_merge([
            'Email'         => 'marie@example.com',
            'Username'      => 'marie.yosefina',
            'Full Name'     => 'Marie Yosefina',
            'Role'          => 'User',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('test-password'),
        ], $overrides);
    }

    private function actingAsSuperAdmin(): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email' => 'admin@example.com',
            'fullName' => 'Admin Test',
            'role' => 'Super Admin',
            'permissions' => config('hris.auth.role_permissions.Super Admin', []),
            'auth_domain' => 'users',
        ]));
    }

    private function mockUserRepo(?array $user): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        $repo->shouldReceive('findByEmail')->andReturn($user);
        $repo->shouldReceive('getAll')->andReturn($user ? [$user] : []);
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    private function setPermission(string $email, string $key, bool $granted): void
    {
        $repo = app(UserPermissionRepositoryInterface::class);
        $repo->upsert($email, $key, $granted, 'test');
        app(PermissionResolver::class)->forget($email);
    }

    private function setMariePermissions(array $permissions): void
    {
        $email = 'marie@example.com';
        foreach ($permissions as $key => $granted) {
            $this->setPermission($email, $key, $granted);
        }
    }

    // =========================================================================
    // PermissionResolver::dependenciesFor()
    // =========================================================================

    #[Test]
    public function assets_view_requires_assets_access(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['assets.access'], $resolver->dependenciesFor('assets.view'));
    }

    #[Test]
    public function assets_create_requires_assets_access_and_view(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['assets.access'], $resolver->dependenciesFor('assets.create'));
    }

    #[Test]
    public function assets_generate_code_requires_only_assets_access(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['assets.access'], $resolver->dependenciesFor('assets.generate_code'));
    }

    #[Test]
    public function certificates_view_requires_certificates_access(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['certificates.access'], $resolver->dependenciesFor('certificates.view'));
    }

    #[Test]
    public function certificates_delete_requires_certificates_access_and_view(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['certificates.access'], $resolver->dependenciesFor('certificates.delete'));
    }

    #[Test]
    public function certificates_generate_code_requires_only_certificates_access(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals(['certificates.access'], $resolver->dependenciesFor('certificates.generate_code'));
    }

    #[Test]
    public function hris_permissions_have_no_dependencies(): void
    {
        $resolver = app(PermissionResolver::class);
        $this->assertEquals([], $resolver->dependenciesFor('manage_recruitment'));
        $this->assertEquals([], $resolver->dependenciesFor('view_employees'));
        $this->assertEquals([], $resolver->dependenciesFor('view_reports'));
        $this->assertEquals([], $resolver->dependenciesFor('manage_settings'));
    }

    // =========================================================================
    // PermissionResolver::normalizeDependencies()
    // =========================================================================

    #[Test]
    public function normalize_adds_missing_assets_access_for_assets_view(): void
    {
        $resolver = app(PermissionResolver::class);
        $normalized = $resolver->normalizeDependencies(['assets.view' => true]);
        $this->assertTrue($normalized['assets.access']);
        $this->assertTrue($normalized['assets.view']);
    }

    #[Test]
    public function normalize_adds_missing_certificates_access_for_certificates_create(): void
    {
        $resolver = app(PermissionResolver::class);
        $normalized = $resolver->normalizeDependencies(['certificates.create' => true]);
        $this->assertTrue($normalized['certificates.access']);
        $this->assertTrue($normalized['certificates.create']);
        $this->assertArrayNotHasKey('certificates.view', $normalized);
    }

    #[Test]
    public function normalize_does_not_duplicate_existing_dependencies(): void
    {
        $resolver = app(PermissionResolver::class);
        $normalized = $resolver->normalizeDependencies([
            'assets.access' => true,
            'assets.view'   => true,
            'assets.create' => true,
        ]);
        $this->assertCount(3, $normalized);
        $this->assertTrue($normalized['assets.access']);
        $this->assertTrue($normalized['assets.view']);
        $this->assertTrue($normalized['assets.create']);
    }

    #[Test]
    public function normalize_preserves_false_permissions(): void
    {
        $resolver = app(PermissionResolver::class);
        $normalized = $resolver->normalizeDependencies([
            'assets.view'    => true,
            'assets.delete' => false,
        ]);
        $this->assertTrue($normalized['assets.access']);
        $this->assertTrue($normalized['assets.view']);
        $this->assertFalse($normalized['assets.delete']);
    }

    #[Test]
    public function normalize_does_not_add_dependencies_for_non_feature_permissions(): void
    {
        $resolver = app(PermissionResolver::class);
        $normalized = $resolver->normalizeDependencies([
            'manage_recruitment' => true,
            'view_reports'       => true,
        ]);
        $this->assertCount(2, $normalized);
        $this->assertTrue($normalized['manage_recruitment']);
        $this->assertTrue($normalized['view_reports']);
    }

    // =========================================================================
    // PermissionController::update() normalizes dependencies
    // =========================================================================

    #[Test]
    public function permission_management_auto_enables_assets_access_when_assets_view_is_selected(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['assets.view'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.access'));
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.view'));
    }

    #[Test]
    public function permission_management_auto_enables_certificates_access_when_certificates_create_is_selected(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['certificates.create'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'certificates.access'));
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'certificates.create'));
    }

    #[Test]
    public function permission_management_does_not_auto_enable_for_hris_feature_permissions(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['view_recruitment'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'view_recruitment'));
        $this->assertFalse(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.access'));
    }

    #[Test]
    public function permission_management_does_not_duplicate_existing_dependencies(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'assets.access' => true,
            'assets.view'   => true,
        ]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['assets.access', 'assets.view', 'assets.create'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.create'));
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.access'));
    }

    // =========================================================================
    // Super Admin bypass
    // =========================================================================

    #[Test]
    public function super_admin_permission_save_does_not_validate_dependencies(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['assets.view'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        // Super Admin can save anything; dependencies are not enforced for them
        $this->assertTrue(app(PermissionResolver::class)->allows(['email' => 'marie@example.com', 'role' => 'User'], 'assets.view'));
    }

    // =========================================================================
    // Marie regression with dependencies
    // =========================================================================

    #[Test]
    public function marie_assets_workflow_preserves_dependencies(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'assets.access'        => true,
            'assets.view'          => true,
            'assets.create'        => true,
            'assets.update'        => true,
            'assets.delete'        => true,
            'assets.assign'        => true,
            'assets.return'        => true,
            'assets.generate_code' => true,
            'view_asset'           => true,
            'edit_asset'           => true,
            'lookup_employee'      => false,
        ]);

        $resolver = app(PermissionResolver::class);
        $user = ['email' => 'marie@example.com', 'role' => 'User'];

        $this->assertTrue($resolver->allows($user, 'assets.access'));
        $this->assertTrue($resolver->allows($user, 'assets.view'));
        $this->assertTrue($resolver->allows($user, 'assets.create'));
        $this->assertFalse($resolver->allows($user, 'lookup_employee'));
    }

    // =========================================================================
    // Invalid permission keys are still rejected
    // =========================================================================

    #[Test]
    public function permission_management_rejects_unknown_permission_keys(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([]);

        $response = $this->putJson('/hr/permissions/marie@example.com', [
            'permissions' => ['unknown.permission'],
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // lookup_employee explicit deny precedence
    // =========================================================================

    #[Test]
    public function lookup_employee_true_is_allowed(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions(['lookup_employee' => true]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    #[Test]
    public function lookup_employee_false_with_view_asset_true_is_denied(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'lookup_employee' => false,
            'view_asset'      => true,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertFalse($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    #[Test]
    public function lookup_employee_false_with_view_employees_true_is_denied(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'lookup_employee' => false,
            'view_employees'  => true,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertFalse($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    #[Test]
    public function lookup_employee_false_with_view_certification_true_is_denied(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'lookup_employee'     => false,
            'view_certification'  => true,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertFalse($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    #[Test]
    public function lookup_employee_true_with_all_related_false_is_allowed(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'lookup_employee'    => true,
            'view_employees'     => false,
            'view_asset'         => false,
            'view_certification' => false,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    #[Test]
    public function lookup_employee_false_falls_back_to_related_permissions_when_not_explicitly_set(): void
    {
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions([
            'view_asset' => true,
        ]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->allows(['email' => 'marie@example.com', 'role' => 'User'], 'lookup_employee'));
    }

    // =========================================================================
    // Super Admin bypass remains unrestricted
    // =========================================================================

    #[Test]
    public function super_admin_lookup_employee_remains_unrestricted(): void
    {
        $this->actingAsSuperAdmin();
        $this->mockUserRepo($this->makeUserRow());
        $this->setMariePermissions(['lookup_employee' => false]);

        $resolver = app(PermissionResolver::class);
        $this->assertTrue($resolver->allows(['email' => 'marie@example.com', 'role' => 'Super Admin'], 'lookup_employee'));
    }
}
