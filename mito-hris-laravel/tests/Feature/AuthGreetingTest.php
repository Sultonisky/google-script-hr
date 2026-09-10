<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Authentication Greeting Message Test
 *
 * Verifies that welcome greeting messages appear on successful login.
 * Greeting includes user's full name or username as fallback.
 */
class AuthGreetingTest extends TestCase
{
    private function makeUser(array $overrides = []): array
    {
        return array_merge([
            'Email'         => 'marie@example.com',
            'Username'      => 'marie',
            'Full Name'     => 'Marie Yosefina',
            'Role'          => 'User',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('test-password'),
        ], $overrides);
    }

    private function mockUserRepo(?array $user): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null) {
            $repo->shouldReceive('updateLastLogin')->once();
        }
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    #[Test]
    public function hris_login_returns_greeting_message(): void
    {
        $this->mockUserRepo($this->makeUser());

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Selamat datang, Marie Yosefina! Anda telah berhasil masuk ke Sistem HRIS.');
    }

    #[Test]
    public function hris_login_includes_name_in_greeting(): void
    {
        $this->mockUserRepo($this->makeUser(['Full Name' => 'Budi Santoso']));

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Selamat datang, Budi Santoso! Anda telah berhasil masuk ke Sistem HRIS.');
    }

    #[Test]
    public function greeting_personalizes_message_with_user_name(): void
    {
        $this->mockUserRepo($this->makeUser([
            'Full Name' => 'Siti Nurhaliza',
            'Username'  => 'siti',
        ]));

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Selamat datang, Siti Nurhaliza! Anda telah berhasil masuk ke Sistem HRIS.');
    }

    #[Test]
    public function hris_login_includes_correct_domain_reference_in_greeting(): void
    {
        $this->mockUserRepo($this->makeUser());

        $response = $this->postJson('/login', [
            'identifier' => 'marie@example.com',
            'password'   => 'test-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Selamat datang, Marie Yosefina! Anda telah berhasil masuk ke Sistem HRIS.');
    }
}

