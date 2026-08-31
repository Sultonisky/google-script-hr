<?php

namespace Tests\Feature;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class MprDedicatedLoginTest extends TestCase
{
    private function makeRequestorRow(array $overrides = []): array
    {
        return array_merge([
            'Requestor ID' => 'MPR-REQ-001',
            'Email' => 'manager@mito.co.id',
            'Username' => 'manager.test',
            'Full Name' => 'John Manager',
            'Role' => 'Manpower',
            'Status' => 'Active',
            'Password Hash' => Hash::make('password123'),
            'Entity' => 'MSI',
            'Branch' => 'Bandung',
            'Last Login' => '',
            'Created At' => '2026-08-01 09:00:00',
            'Updated At' => '2026-08-01 09:00:00',
            'Created By' => 'seed-command',
        ], $overrides);
    }

    public function test_dedicated_mpr_portal_page_is_accessible(): void
    {
        $response = $this->get(route('mpr.auth.portal'));

        $response->assertOk();
        $response->assertViewIs('auth.mpr-portal');
    }

    public function test_dedicated_mpr_login_page_is_accessible(): void
    {
        $response = $this->get(route('mpr.auth.login'));

        $response->assertOk();
        $response->assertViewIs('auth.mpr-auth');
    }

    public function test_valid_requestor_can_login_to_mpr_session(): void
    {
        $requestor = $this->makeRequestorRow();

        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')
            ->once()
            ->with('manager@mito.co.id')
            ->andReturn($requestor);

        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => '  MANAGER@MITO.CO.ID  ',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('mpr.auth.request'));
        $this->assertTrue(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
        $this->assertSame('manager@mito.co.id', session(config('mpr.session_key', 'mpr_requestor_auth'))['email']);
    }

    public function test_valid_requestor_can_login_with_username_or_email(): void
    {
        $requestor = $this->makeRequestorRow();

        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')
            ->once()
            ->with('manager.test')
            ->andReturn($requestor);

        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'manager.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('mpr.auth.request'));
        $this->assertTrue(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
        $this->assertSame('manager@mito.co.id', session(config('mpr.session_key', 'mpr_requestor_auth'))['email']);
    }

    public function test_wrong_password_is_rejected_for_mpr_requestor(): void
    {
        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')
            ->once()
            ->with('manager@mito.co.id')
            ->andReturn($this->makeRequestorRow());

        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);

        $response = $this->from(route('mpr.auth.login'))->post(route('mpr.auth.login.post'), [
            'identifier' => 'manager@mito.co.id',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
    }

    public function test_inactive_requestor_is_rejected_generically(): void
    {
        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')
            ->once()
            ->with('inactive@mito.co.id')
            ->andReturn($this->makeRequestorRow([
                'Email' => 'inactive@mito.co.id',
                'Status' => 'Inactive',
                'Role' => 'Manager',
                'Role' => 'Manpower',
            ]));

        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);

        $response = $this->from(route('mpr.auth.login'))->post(route('mpr.auth.login.post'), [
            'identifier' => 'inactive@mito.co.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
    }

    public function test_mpr_routes_require_mpr_session(): void
    {
        $response = $this->get(route('mpr.auth.request'));

        $response->assertRedirect(route('mpr.auth.login'));
    }

    public function test_dedicated_mpr_session_can_open_create_form_without_hr_session(): void
    {
        $this->withSession([
            config('mpr.session_key', 'mpr_requestor_auth') => [
                'email' => 'manager@mito.co.id',
                'fullName' => 'John Manager',
                'role' => 'Manpower',
                'auth_domain' => 'mpr_requestor',
                'entities' => ['MSI'],
                'branch' => 'Bandung',
            ],
        ])->get(route('mpr.auth.request'))->assertOk()->assertViewIs('hr.mpr.create');
    }

    public function test_mpr_requestor_topbar_uses_dedicated_logout_route(): void
    {
        $this->withSession([
            config('mpr.session_key', 'mpr_requestor_auth') => [
                'email' => 'manager@mito.co.id',
                'fullName' => 'John Manager',
                'role' => 'Manpower',
                'auth_domain' => 'mpr_requestor',
                'entities' => ['MSI'],
                'branch' => 'Bandung',
            ],
        ])->get(route('mpr.auth.request'))
            ->assertOk()
            ->assertSee(route('mpr.auth.logout'))
            ->assertDontSee(route('logout'));
    }
}
