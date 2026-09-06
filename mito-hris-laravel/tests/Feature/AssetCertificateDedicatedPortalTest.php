<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssetCertificateDedicatedPortalTest extends TestCase
{
    private function mockUser(string $role, string $identifier, string $password): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByIdentifier')->with($identifier)->andReturn([
            'Email' => $identifier,
            'Full Name' => $role . ' User',
            'Username' => $identifier,
            'Role' => $role,
            'Status' => 'Active',
            'Password Hash' => Hash::make($password),
        ]);
        $repository->shouldReceive('updateLastLogin')->once();
        $this->app->instance(UserRepositoryInterface::class, $repository);
    }

    public function test_asset_login_creates_only_asset_session(): void
    {
        $this->mockUser('Admin', 'asset@mito.id', 'asset-password');
        Session::put('hr_user', ['auth_domain' => 'users']);
        $loginPath = app()->environment('local') ? '/assets/login' : '/login';

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('assets')
            ->postJson($loginPath, [
                'identifier' => 'asset@mito.id',
                'password' => 'asset-password',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertSessionHas('asset_auth.auth_domain', 'assets');
        $response->assertSessionHas('hr_user.auth_domain', 'users');
    }

    public function test_certificate_login_creates_only_certificate_session(): void
    {
        $this->mockUser('Admin', 'certificate@mito.id', 'certificate-password');
        Session::put('hr_user', ['auth_domain' => 'users']);
        $loginPath = app()->environment('local') ? '/certifications/login' : '/login';

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson($loginPath, [
                'identifier' => 'certificate@mito.id',
                'password' => 'certificate-password',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertSessionHas('certificate_auth.auth_domain', 'certificates');
        $response->assertSessionHas('hr_user.auth_domain', 'users');
    }

    public function test_super_admin_can_login_to_both_dedicated_portals(): void
    {
        $this->mockUser('Super Admin', 'superadmin@mito.id', 'portal-password');
        $assetLoginPath = app()->environment('local') ? '/assets/login' : '/login';

        $assetResponse = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('assets')
            ->postJson($assetLoginPath, [
                'identifier' => 'superadmin@mito.id',
                'password' => 'portal-password',
            ]);

        $assetResponse->assertOk()->assertJsonPath('success', true);

        Session::forget('asset_auth');
        $this->mockUser('Super Admin', 'superadmin@mito.id', 'portal-password');
        $certificateLoginPath = app()->environment('local') ? '/certifications/login' : '/login';

        $certificateResponse = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson($certificateLoginPath, [
                'identifier' => 'superadmin@mito.id',
                'password' => 'portal-password',
            ]);

        $certificateResponse->assertOk()->assertJsonPath('success', true);
    }

    #[DataProvider('deniedPortalRoles')]
    public function test_non_admin_roles_are_denied_from_assets(string $role): void
    {
        $identifier = strtolower(str_replace(' ', '.', $role)) . '@mito.id';
        $this->mockUser($role, $identifier, 'portal-password');
        $loginPath = app()->environment('local') ? '/assets/login' : '/login';

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('assets')
            ->postJson($loginPath, [
                'identifier' => $identifier,
                'password' => 'portal-password',
            ]);

        $response->assertForbidden();
        $response->assertSessionMissing('asset_auth');
    }

    #[DataProvider('deniedPortalRoles')]
    public function test_non_admin_roles_are_denied_from_certificates(string $role): void
    {
        $identifier = strtolower(str_replace(' ', '.', $role)) . '@mito.id';
        $this->mockUser($role, $identifier, 'portal-password');
        $loginPath = app()->environment('local') ? '/certifications/login' : '/login';

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->onDomain('certificates')
            ->postJson($loginPath, [
                'identifier' => $identifier,
                'password' => 'portal-password',
            ]);

        $response->assertForbidden();
        $response->assertSessionMissing('certificate_auth');
    }

    public static function deniedPortalRoles(): array
    {
        return [
            'HR Manager' => ['HR Manager'],
            'HR Recruitment' => ['HR Recruitment'],
            'HR Staff' => ['HR Staff'],
            'Unknown role' => ['Unknown'],
        ];
    }

    public function test_asset_logout_preserves_other_portal_sessions(): void
    {
        Session::put('asset_auth', ['email' => 'asset@mito.id', 'auth_domain' => 'assets']);
        Session::put('certificate_auth', ['email' => 'certificate@mito.id', 'auth_domain' => 'certificates']);
        Session::put('hr_user', ['email' => 'hr@mito.id', 'auth_domain' => 'users']);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('assets.logout'));

        $response->assertRedirect(route('assets.login'));
        $response->assertSessionMissing('asset_auth');
        $response->assertSessionHas('certificate_auth.auth_domain', 'certificates');
        $response->assertSessionHas('hr_user.auth_domain', 'users');
    }

    public function test_certificate_logout_preserves_other_portal_sessions(): void
    {
        Session::put('asset_auth', ['email' => 'asset@mito.id', 'auth_domain' => 'assets']);
        Session::put('certificate_auth', ['email' => 'certificate@mito.id', 'auth_domain' => 'certificates']);
        Session::put('hr_user', ['email' => 'hr@mito.id', 'auth_domain' => 'users']);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('certificates.logout'));

        $response->assertRedirect(route('certificates.login'));
        $response->assertSessionMissing('certificate_auth');
        $response->assertSessionHas('asset_auth.auth_domain', 'assets');
        $response->assertSessionHas('hr_user.auth_domain', 'users');
    }

    public function test_asset_domain_requires_dedicated_asset_session(): void
    {
        Session::forget('asset_auth');
        Session::forget('hr_user');

        $response = $this->get('http://assets.hrismitogroup.web.id/assets');

        $response->assertRedirect(route('assets.login'));
        $this->assertFalse(session()->has('asset_auth'));
    }

    public function test_hris_session_does_not_grant_asset_access(): void
    {
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Admin',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);
        Session::forget('asset_auth');

        $response = $this->get('http://assets.hrismitogroup.web.id/assets');

        $response->assertRedirect(route('assets.login'));
        $this->assertFalse(session()->has('asset_auth'));
    }

    public function test_authenticated_non_admin_cannot_access_asset_json_directly(): void
    {
        Session::put('asset_auth', [
            'email' => 'staff@mito.id',
            'role' => 'HR Staff',
            'source_role' => 'HR Staff',
            'auth_domain' => 'assets',
        ]);

        $response = $this->get('http://assets.hrismitogroup.web.id/assets/1/json');

        $response->assertForbidden();
    }

    public function test_certificate_domain_requires_dedicated_certificate_session(): void
    {
        Session::forget('certificate_auth');
        Session::forget('hr_user');

        $response = $this->get('http://certificates.hrismitogroup.web.id/certifications');

        $response->assertRedirect(route('certificates.login'));
        $this->assertFalse(session()->has('certificate_auth'));
    }

    public function test_hris_session_does_not_grant_certificate_access(): void
    {
        Session::put('hr_user', [
            'email' => 'legal@mito.id',
            'role' => 'LEGAL',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);
        Session::forget('certificate_auth');

        $response = $this->get('http://certificates.hrismitogroup.web.id/certifications');

        $response->assertRedirect(route('certificates.login'));
        $this->assertFalse(session()->has('certificate_auth'));
    }

    public function test_authenticated_non_admin_cannot_access_certificate_json_directly(): void
    {
        Session::put('certificate_auth', [
            'email' => 'staff@mito.id',
            'role' => 'HR Staff',
            'source_role' => 'HR Staff',
            'auth_domain' => 'certificates',
        ]);

        $response = $this->get('http://certificates.hrismitogroup.web.id/certifications/1/json');

        $response->assertForbidden();
    }
}
