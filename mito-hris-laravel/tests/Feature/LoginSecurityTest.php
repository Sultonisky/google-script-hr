<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Local\ArrayUserPermissionRepository;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    private function mockUserDomain(?array $user, bool $expectLastLogin = false): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null && $expectLastLogin) {
            $repository->shouldReceive('updateLastLogin')->once();
        }
        $this->app->instance(UserRepositoryInterface::class, $repository);
    }

    private function activeUser(string $password = 'correct-password'): array
    {
        return [
            'Email' => 'admin@example.test',
            'Full Name' => 'Admin Test',
            'Username' => 'admin',
            'Role' => 'Admin',
            'Status' => 'Active',
            'Password Hash' => Hash::make($password),
        ];
    }

    public function test_successful_login_creates_session_without_returning_password(): void
    {
        $this->mockUserDomain($this->activeUser(), true);
        $permissions = new ArrayUserPermissionRepository();
        $permissions->upsert('admin@example.test', 'view_recruitment', true, 'test');
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $response = $this->postJson('/login', [
            'identifier' => 'admin@example.test',
            'password' => 'correct-password',
            'rememberMe' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('user.password');
        $this->assertStringNotContainsString('correct-password', $response->getContent());
        $this->assertTrue($this->app['session']->has('hr_user'));
        $this->assertTrue($this->app['session']->get('hris_remember'));
    }

    public function test_assets_only_user_cannot_login_to_hris_dashboard(): void
    {
        $this->mockUserDomain($this->activeUser(), true);
        $permissions = new ArrayUserPermissionRepository();
        $permissions->upsert('admin@example.test', 'assets.access', true, 'test');
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $response = $this->postJson('/login', [
            'identifier' => 'admin@example.test',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Anda tidak memiliki akses ke HRIS Portal. Akun Anda belum diberikan izin untuk mengakses portal ini. Silakan hubungi administrator jika Anda membutuhkan akses.')
            ->assertJsonPath('code', 'PORTAL_ACCESS_DENIED');
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    public function test_failed_login_uses_generic_error_without_echoing_password(): void
    {
        $this->mockUserDomain($this->activeUser());

        $response = $this->postJson('/login', [
            'identifier' => 'admin@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Email atau password salah.');
        $this->assertStringNotContainsString('wrong-password', $response->getContent());
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    public function test_unknown_identifier_has_same_generic_error(): void
    {
        $this->mockUserDomain(null);

        $response = $this->postJson('/login', [
            'identifier' => 'unknown-' . uniqid() . '@example.test',
            'password' => 'any-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Email atau password salah.');
    }

    public function test_username_login_is_rejected(): void
    {
        $this->mockUserDomain($this->activeUser());

        $response = $this->postJson('/login', [
            'identifier' => 'admin',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
        $this->assertFalse($this->app['session']->has('hr_user'));
    }

    public function test_login_is_rate_limited_per_identifier_and_ip(): void
    {
        $this->mockUserDomain(null);
        $identifier = 'limited-' . uniqid() . '@example.test';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', [
                'identifier' => $identifier,
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/login', [
            'identifier' => $identifier,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
