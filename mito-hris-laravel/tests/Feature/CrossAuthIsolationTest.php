<?php

namespace Tests\Feature;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cross-Authentication Isolation Regression Tests
 *
 * Verifies the canonical architecture rule:
 *
 *   IDENTICAL username or email across users (HRIS) and mpr_requestors (MPR)
 *   must NEVER cause authentication crossover.
 *
 * Architecture contract:
 *   HRIS domain → users sheet only → hr_user session only → HRIS destination
 *   MPR domain  → mpr_requestors sheet only → mpr_requestor_auth only → MPR destination
 *
 * Full validation matrix (from task specification):
 *   HRIS login | exists in users=Y | exists in mpr_requestors=N → HRIS ✓
 *   HRIS login | exists in users=N | exists in mpr_requestors=Y → Login fails ✓
 *   HRIS login | exists in users=Y | exists in mpr_requestors=Y → HRIS ✓
 *   MPR login  | exists in users=N | exists in mpr_requestors=Y → MPR ✓
 *   MPR login  | exists in users=Y | exists in mpr_requestors=N → Login fails ✓
 *   MPR login  | exists in users=Y | exists in mpr_requestors=Y → MPR ✓
 */
class CrossAuthIsolationTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUserRow(array $overrides = []): array
    {
        return array_merge([
            'Email'         => 'john@example.com',
            'Username'      => 'john',
            'Full Name'     => 'John HRIS',
            'Role'          => 'Admin',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('hris-password'),
        ], $overrides);
    }

    private function makeMprRequestorRow(array $overrides = []): array
    {
        return array_merge([
            'Requestor ID'  => 'MPR-REQ-001',
            'Email'         => 'john@example.com',
            'Username'      => 'john',
            'Full Name'     => 'John MPR',
            'Job Position'  => 'Manager',
            'Role'          => 'Manpower',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('mpr-password'),
            'Last Login'    => '',
            'Created At'    => '2026-01-01 00:00:00',
            'Updated At'    => '2026-01-01 00:00:00',
            'Created By'    => 'system',
        ], $overrides);
    }

    private function mockUserRepo(?array $user, bool $expectLastLogin = false): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null && $expectLastLogin) {
            $repo->shouldReceive('updateLastLogin')->once();
        }
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    private function grantHrisPermission(string $email): void
    {
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $repo->upsert($email, 'view_recruitment', true, 'test');
        app(\App\Services\PermissionResolver::class)->forget($email);
    }

    private function mockMprRequestorRepo(?array $requestor, bool $expectLastLogin = false): void
    {
        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($requestor);
        // Note: MprAuthController does NOT call updateLastLogin (unlike MprRequestorAuthService).
        // Do not set expectations on it here.
        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);
    }

    // =========================================================================
    // Matrix Row 1: HRIS login | users=Y | mpr_requestors=N → HRIS
    // =========================================================================

    #[Test]
    public function hris_login_succeeds_when_account_exists_only_in_users(): void
    {
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->grantHrisPermission('john@example.com');

        $response = $this->postJson('/login', [
            'identifier' => 'john@example.com',
            'password'   => 'hris-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('hr_user'));
        $this->assertSame('users', session('hr_user')['auth_domain']);
        $this->assertSame('hris', session('hr_user')['portal']);
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'HRIS login must never create mpr_requestor_auth'
        );
    }

    // =========================================================================
    // Matrix Row 2: HRIS login | users=N | mpr_requestors=Y → Login fails
    // =========================================================================

    #[Test]
    public function hris_login_fails_when_account_exists_only_in_mpr_requestors(): void
    {
        // AuthService queries ONLY users. Returns null → login fails.
        // Must NOT fall back to mpr_requestors.
        $this->mockUserRepo(null);

        $response = $this->postJson('/login', [
            'identifier' => 'john@example.com',
            'password'   => 'mpr-password',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertFalse(session()->has('hr_user'), 'No hr_user session on HRIS login failure');
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'No mpr_requestor_auth session must be created by a failed HRIS login'
        );
    }

    // =========================================================================
    // Matrix Row 3: HRIS login | users=Y | mpr_requestors=Y → HRIS
    //
    // This is the PRIMARY regression case — same username/email exists in BOTH.
    // HRIS login must authenticate against users only and stay in HRIS.
    // =========================================================================

    #[Test]
    public function hris_login_stays_hris_when_same_email_exists_in_both_stores(): void
    {
        // Same email 'john@example.com' is in BOTH users and mpr_requestors.
        // HRIS login must use users only, create hr_user, redirect to HRIS dashboard.
        $this->mockUserRepo($this->makeUserRow(), true);
        $this->grantHrisPermission('john@example.com');
        // The mpr_requestors repo must NOT be queried during HRIS login.
        // We do not mock it — if it is queried, the test will fail with a binding error.

        $response = $this->postJson('/login', [
            'identifier' => 'john@example.com',
            'password'   => 'hris-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // Must create hr_user with HRIS domain marker.
        $this->assertTrue(session()->has('hr_user'));
        $this->assertSame('users',      session('hr_user')['auth_domain']);
        $this->assertSame('hris',       session('hr_user')['portal']);
        $this->assertSame('Admin',      session('hr_user')['role']);
        $this->assertSame('john@example.com', session('hr_user')['email']);

        // Must NOT create mpr_requestor_auth.
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'HRIS login must never create mpr_requestor_auth even if same email exists in MPR'
        );

        // Redirect must be to HRIS dashboard.
        $this->assertSame(route('hr.dashboard'), $response->json('redirect'));
    }

    #[Test]
    public function hris_login_stays_hris_when_same_username_exists_in_both_stores(): void
    {
        $this->mockUserRepo($this->makeUserRow(['Username' => 'john']), true);
        $this->grantHrisPermission('john@example.com');

        $response = $this->postJson('/login', [
            'identifier' => 'john',  // login by username
            'password'   => 'hris-password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(session()->has('hr_user'));
        $this->assertSame('users', session('hr_user')['auth_domain']);
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'Username-based HRIS login must never create mpr_requestor_auth'
        );
    }

    #[Test]
    public function hris_dashboard_accessible_when_both_sessions_exist_simultaneously(): void
    {
        // Both sessions exist (duplicate account). HRIS dashboard must be accessible
        // using hr_user — PortalAccessMiddleware must not redirect to MPR.
        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $response = $this->get('/hr/dashboard');

        $response->assertOk();
        // hr_user session must remain intact.
        $this->assertTrue(session()->has('hr_user'));
        $this->assertSame('users', session('hr_user')['auth_domain']);
    }

    #[Test]
    public function hris_protected_routes_not_redirected_to_mpr_when_stale_mpr_session_present(): void
    {
        // hr_user session has expired. mpr_requestor_auth is still alive.
        // Accessing any HRIS-protected route must redirect to HRIS login — NOT to MPR.
        Session::forget('hr_user');
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $routes = [
            '/hr/dashboard',
            '/hr/recruitment',
            '/hr/employees',
            '/hr/mpr',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(
                route('login'),
                "Route {$route} must redirect to HRIS login, not MPR, when hr_user is absent"
            );
        }
    }

    // =========================================================================
    // Matrix Row 4: MPR login | users=N | mpr_requestors=Y → MPR
    // =========================================================================

    #[Test]
    public function mpr_login_succeeds_when_account_exists_only_in_mpr_requestors(): void
    {
        $this->mockMprRequestorRepo($this->makeMprRequestorRow());

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john@example.com',
            'password'   => 'mpr-password',
        ]);

        $response->assertRedirect(route('mpr.auth.request'));
        $this->assertTrue(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
        $this->assertSame('mpr_requestor', session(config('mpr.session_key', 'mpr_requestor_auth'))['auth_domain']);
        $this->assertFalse(session()->has('hr_user'), 'MPR login must never create hr_user');
    }

    // =========================================================================
    // Matrix Row 5: MPR login | users=Y | mpr_requestors=N → Login fails
    // =========================================================================

    #[Test]
    public function mpr_login_fails_when_account_exists_only_in_users(): void
    {
        // MprAuthController queries ONLY mpr_requestors. Returns null → login fails.
        // Must NOT fall back to users sheet.
        $this->mockMprRequestorRepo(null);

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john@example.com',
            'password'   => 'hris-password',
        ]);

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'No mpr_requestor_auth on MPR login failure'
        );
        $this->assertFalse(session()->has('hr_user'), 'MPR login must never create hr_user');
    }

    // =========================================================================
    // Matrix Row 6: MPR login | users=Y | mpr_requestors=Y → MPR
    //
    // Same username/email exists in BOTH. MPR login must use mpr_requestors only.
    // =========================================================================

    #[Test]
    public function mpr_login_stays_mpr_when_same_email_exists_in_both_stores(): void
    {
        $this->mockMprRequestorRepo($this->makeMprRequestorRow());
        // The users repo must NOT be queried during MPR login.
        // We do not mock it — if it is queried, the test will fail with a binding error.

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john@example.com',
            'password'   => 'mpr-password',
        ]);

        $response->assertRedirect(route('mpr.auth.request'));

        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $this->assertTrue(session()->has($sessionKey));
        $this->assertSame('mpr_requestor', session($sessionKey)['auth_domain']);
        $this->assertSame('mpr',           session($sessionKey)['portal']);
        $this->assertSame('john@example.com', session($sessionKey)['email']);

        // Must NOT create hr_user.
        $this->assertFalse(session()->has('hr_user'), 'MPR login must never create hr_user');
    }

    #[Test]
    public function mpr_login_stays_mpr_when_same_username_exists_in_both_stores(): void
    {
        $this->mockMprRequestorRepo($this->makeMprRequestorRow(['Username' => 'john']));

        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john',  // login by username
            'password'   => 'mpr-password',
        ]);

        $response->assertRedirect(route('mpr.auth.request'));
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        $this->assertTrue(session()->has($sessionKey));
        $this->assertSame('mpr_requestor', session($sessionKey)['auth_domain']);
        $this->assertFalse(session()->has('hr_user'), 'Username-based MPR login must never create hr_user');
    }

    #[Test]
    public function mpr_portal_accessible_when_both_sessions_exist_simultaneously(): void
    {
        // Both sessions exist (duplicate account). MPR portal must be accessible
        // using mpr_requestor_auth — PortalAccessMiddleware must not redirect to HRIS.
        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $response = $this->get(route('mpr.auth.request'));

        $response->assertOk();
        $response->assertViewIs('hr.mpr.create');
    }

    // =========================================================================
    // Session isolation guarantees
    // =========================================================================

    #[Test]
    public function hris_login_never_queries_mpr_requestors_repository(): void
    {
        // AuthService (HRIS login) must never call MprRequestorRepositoryInterface.
        // If it does, this test will fail because the binding is a strict mock.
        $strictMpr = Mockery::mock(MprRequestorRepositoryInterface::class);
        $strictMpr->shouldNotReceive('findByIdentifier');
        $strictMpr->shouldNotReceive('findByEmail');
        $this->app->instance(MprRequestorRepositoryInterface::class, $strictMpr);

        $this->mockUserRepo($this->makeUserRow(), true);
        $this->grantHrisPermission('john@example.com');

        $this->postJson('/login', [
            'identifier' => 'john@example.com',
            'password'   => 'hris-password',
        ])->assertOk();
    }

    #[Test]
    public function mpr_login_never_queries_users_repository(): void
    {
        // MprAuthController must never call UserRepositoryInterface.
        // If it does, this test will fail.
        $strictUsers = Mockery::mock(UserRepositoryInterface::class);
        $strictUsers->shouldNotReceive('findByIdentifier');
        $strictUsers->shouldNotReceive('findByEmail');
        $this->app->instance(UserRepositoryInterface::class, $strictUsers);

        $this->mockMprRequestorRepo($this->makeMprRequestorRow());

        $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john@example.com',
            'password'   => 'mpr-password',
        ])->assertRedirect(route('mpr.auth.request'));
    }

    #[Test]
    public function hris_session_key_and_mpr_session_key_are_different(): void
    {
        // Sanity check: the two session keys must be distinct so sessions can
        // coexist without overwriting each other.
        $mprKey = config('mpr.session_key', 'mpr_requestor_auth');
        $this->assertNotSame('hr_user', $mprKey);
    }

    #[Test]
    public function hris_login_uses_password_from_users_sheet_not_mpr_requestors_sheet(): void
    {
        // Even when the same email exists in both, HRIS login uses the users sheet
        // password hash. Using the MPR password must fail.
        $this->mockUserRepo(
            $this->makeUserRow(['Password Hash' => Hash::make('hris-password')])
        );

        // Attempt with MPR password — must fail even if same email exists in MPR with this password.
        $response = $this->postJson('/login', [
            'identifier' => 'john@example.com',
            'password'   => 'mpr-password',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertFalse(session()->has('hr_user'));
    }

    #[Test]
    public function mpr_login_uses_password_from_mpr_requestors_sheet_not_users_sheet(): void
    {
        // Even when the same email exists in both, MPR login uses the mpr_requestors sheet
        // password hash. Using the HRIS password must fail.
        $this->mockMprRequestorRepo(
            $this->makeMprRequestorRow(['Password Hash' => Hash::make('mpr-password')])
        );

        // Attempt with HRIS password — must fail.
        $response = $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'john@example.com',
            'password'   => 'hris-password',
        ]);

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
    }
}
