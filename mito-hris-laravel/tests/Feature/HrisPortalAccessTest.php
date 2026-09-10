<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * HRIS Portal Access & Strict Domain Isolation Regression Tests
 *
 * Validates that the HRIS portal enforces explicit portal access at the
 * authentication boundary, and that dedicated portal sessions cannot leak
 * into HRIS or other dedicated portals.
 */
class HrisPortalAccessTest extends TestCase
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

    private function mockUserRepo(?array $user, bool $expectLastLogin = false): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null && $expectLastLogin) {
            $repo->shouldReceive('updateLastLogin')->andReturnNull();
        }
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    private function setPermission(string $email, string $key, bool $granted): void
    {
        $repo = app(UserPermissionRepositoryInterface::class);
        $repo->upsert($email, $key, $granted, 'test');
        app(\App\Services\PermissionResolver::class)->forget($email);
    }

    private function setMariePermissions(array $permissions): void
    {
        $email = 'marie@example.com';
        foreach ($permissions as $key => $granted) {
            $this->setPermission($email, $key, $granted);
        }
    }

    // =========================================================================
    // HRIS portal access: dedicated-only users must be denied
    // =========================================================================

    #[Test]
    public function user_with_only_dedicated_portal_permissions_is_denied_hris_access(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'assets.access'       => true,
            'assets.view'         => true,
            'assets.create'       => true,
            'assets.update'       => true,
            'assets.delete'       => true,
            'assets.assign'       => true,
            'assets.return'       => true,
            'assets.generate_code' => true,
            'view_asset'          => true,
            'edit_asset'          => true,
        ]);

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertFalse(session()->has('hr_user'));
    }

    #[Test]
    public function user_with_only_certificate_dedicated_permissions_is_denied_hris_access(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'certificates.access'       => true,
            'certificates.view'         => true,
            'certificates.create'       => true,
            'certificates.update'       => true,
            'certificates.delete'       => true,
            'certificates.generate_code' => true,
            'view_certification'        => true,
            'manage_certification'      => true,
        ]);

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertFalse(session()->has('hr_user'));
    }

    // =========================================================================
    // HRIS portal access: users with non-dedicated permissions are allowed
    // =========================================================================

    #[Test]
    public function user_with_non_dedicated_permissions_is_allowed_hris_access(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'assets.access'       => true,
            'view_recruitment'    => true,
        ]);

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('hr_user'));
    }

    // =========================================================================
    // HRIS portal access: backward compatibility for users with no mappings
    // =========================================================================

    #[Test]
    public function user_with_no_permission_mappings_is_allowed_hris_access(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('hr_user'));
    }

    // =========================================================================
    // Super Admin bypass
    // =========================================================================

    #[Test]
    public function super_admin_is_always_allowed_hris_access_even_with_denied_permissions(): void
    {
        $this->mockUserRepo($this->makeUserRow(['Role' => 'Super Admin']), true);
        $this->setMariePermissions([
            'assets.access'    => false,
            'view_recruitment' => false,
        ]);

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('hr_user'));
    }

    // =========================================================================
    // Marie regression: exact permission state from the task
    // =========================================================================

    #[Test]
    public function marie_with_assets_only_permissions_is_denied_hris_and_certificates(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'assets.access'        => true,
            'assets.view'          => true,
            'assets.create'        => true,
            'assets.update'        => true,
            'assets.delete'        => true,
            'assets.assign'        => true,
            'assets.return'        => true,
            'assets.generate_code' => true,
            'certificates.access'  => false,
            'certificates.view'    => false,
            'certificates.create'  => false,
            'certificates.update'  => false,
            'certificates.delete'  => false,
            'certificates.generate_code' => false,
            'manage_recruitment'   => false,
            'manage_employees'     => false,
            'view_employees'       => false,
            'view_recruitment'     => false,
            'update_candidates'    => false,
            'create_offering'      => false,
            'manage_hold_blacklist'=> false,
            'manage_probation'     => false,
            'view_mpr'             => false,
            'create_mpr'           => false,
            'update_mpr'           => false,
            'export_mpr'           => false,
            'manage_settings'      => false,
            'manage_permissions'   => false,
            'view_reports'         => false,
            'view_asset'           => true,
            'edit_asset'           => true,
            'view_certification'   => false,
            'manage_certification' => false,
            'lookup_employee'      => false,
        ]);

        // HRIS login must be denied.
        $hrisResponse = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);
        $hrisResponse->assertStatus(422)->assertJsonPath('success', false);
        $this->assertFalse(session()->has('hr_user'));

        // Certificates login must be denied (certificates.access = FALSE).
        $certResponse = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson('/login', [
                'identifier' => 'marie@example.com',
                'password'   => 'test-password',
            ]);
        $certResponse->assertForbidden();
        $this->assertFalse(session()->has('certificate_auth'));
    }

    // =========================================================================
    // Cross-session isolation: dedicated portal sessions must not authenticate HRIS
    // =========================================================================

    #[Test]
    public function assets_session_does_not_authenticate_hris(): void
    {
        Session::put('asset_auth', [
            'email'       => 'marie@example.com',
            'role'        => 'User',
            'auth_domain' => 'assets',
            'portal'      => 'assets',
        ]);
        Session::forget('hr_user');

        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'));
    }

    #[Test]
    public function certificates_session_does_not_authenticate_hris(): void
    {
        Session::put('certificate_auth', [
            'email'       => 'marie@example.com',
            'role'        => 'User',
            'auth_domain' => 'certificates',
            'portal'      => 'certificates',
        ]);
        Session::forget('hr_user');

        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'));
    }

    #[Test]
    public function hris_session_does_not_authenticate_assets(): void
    {
        Session::put('hr_user', [
            'email'       => 'marie@example.com',
            'role'        => 'User',
            'auth_domain' => 'users',
            'portal'      => 'hris',
        ]);
        Session::forget('asset_auth');

        $response = $this->get('http://' . config('hris.domains.assets') . '/');

        $response->assertRedirect(route('assets.login'));
        $this->assertFalse(session()->has('asset_auth'));
    }

    #[Test]
    public function hris_session_does_not_authenticate_certificates(): void
    {
        Session::put('hr_user', [
            'email'       => 'marie@example.com',
            'role'        => 'User',
            'auth_domain' => 'users',
            'portal'      => 'hris',
        ]);
        Session::forget('certificate_auth');

        $response = $this->get('http://' . config('hris.domains.certificates') . '/certifications');

        $response->assertRedirect(route('certificates.login'));
        $this->assertFalse(session()->has('certificate_auth'));
    }

    #[Test]
    public function direct_hris_private_routes_require_authentication(): void
    {
        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');
        $response->assertRedirect(route('login'));
    }

    // =========================================================================
    // Assets portal: assets.access = TRUE grants access, FALSE denies
    // =========================================================================

    #[Test]
    public function user_with_assets_access_true_can_access_assets_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'assets.access' => true,
        ]);

        $loginPath = app()->environment('local') ? '/assets/login' : '/login';

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->onDomain('assets')
            ->postJson($loginPath, [
                'identifier' => 'marie@example.com',
                'password'   => 'test-password',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('asset_auth'));
    }

    #[Test]
    public function user_with_assets_access_false_is_denied_assets_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'assets.access' => false,
        ]);

        $loginPath = app()->environment('local') ? '/assets/login' : '/login';

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->onDomain('assets')
            ->postJson($loginPath, [
                'identifier' => 'marie@example.com',
                'password'   => 'test-password',
            ]);

        $response->assertForbidden();
        $this->assertFalse(session()->has('asset_auth'));
    }

    // =========================================================================
    // Certificates portal: certificates.access = TRUE grants, FALSE denies
    // =========================================================================

    #[Test]
    public function user_with_certificates_access_true_can_access_certificates_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'certificates.access' => true,
        ]);

        $loginPath = app()->environment('local') ? '/certifications/login' : '/login';

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson($loginPath, [
                'identifier' => 'marie@example.com',
                'password'   => 'test-password',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('certificate_auth'));
    }

    #[Test]
    public function user_with_certificates_access_false_is_denied_certificates_portal(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->setMariePermissions([
            'certificates.access' => false,
        ]);

        $loginPath = app()->environment('local') ? '/certifications/login' : '/login';

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson($loginPath, [
                'identifier' => 'marie@example.com',
                'password'   => 'test-password',
            ]);

        $response->assertForbidden();
        $this->assertFalse(session()->has('certificate_auth'));
    }
}
