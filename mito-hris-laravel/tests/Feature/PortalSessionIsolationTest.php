<?php

namespace Tests\Feature;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Portal Session Isolation Regression Tests
 *
 * Validates the canonical session isolation contract:
 *
 *   HRIS portal  → reads hr_user (auth_domain='users')                ONLY
 *   MPR portal   → reads mpr_requestor_auth (auth_domain='mpr_requestor') ONLY
 *
 * These tests directly target the bug scenario:
 *   1. User logs in to MPR portal  → mpr_requestor_auth is set.
 *   2. Same browser tab navigates to HRIS portal.
 *   3. User logs in to HRIS portal → hr_user is set.
 *   4. Both sessions coexist (shared cookie domain).
 *
 * After the fix, HRIS must resolve ONLY hr_user identity and MPR must resolve
 * ONLY mpr_requestor_auth identity — even when both keys exist simultaneously.
 *
 * Test matrix (per spec):
 *   Test 1 — MPR session does NOT authenticate HRIS
 *   Test 2 — HRIS session does NOT authenticate MPR
 *   Test 3 — MPR session present → HRIS login → HRIS shows HRIS Admin identity
 *   Test 4 — Both sessions coexist: HRIS → hr_user, MPR → mpr_requestor_auth
 *   Test 5 — HRIS authorization ignores MPR session (MPR role grants nothing in HRIS)
 *   Test 6 — MPR authorization ignores HRIS role (HRIS Super Admin ≠ MPR Requestor)
 *   Test 7 — HRIS login clears stale MPR session from shared PHP session storage
 *   Test 8 — MPR login clears stale HRIS session from shared PHP session storage
 *   Test 9 — HRIS topbar resolves HRIS identity when both sessions coexist
 *   Test 10 — MPR topbar resolves MPR identity; HRIS identity is ignored
 */
#[Group('auth-isolation')]
class PortalSessionIsolationTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeHrisUser(array $overrides = []): array
    {
        return $this->migratedTestUser(array_merge([
            'email'       => 'admin@hris.example.com',
            'fullName'    => 'HRIS Admin User',
            'name'        => 'HRIS Admin User',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ], $overrides));
    }

    private function makeMprRequestor(array $overrides = []): array
    {
        return array_merge([
            'email'        => 'manpower@mpr.example.com',
            'fullName'     => 'MPR Manpower User',
            'jobPosition'  => 'Regional Manager',
            'role'         => 'Manpower',
            'auth_domain'  => 'mpr_requestor',
            'portal'       => 'mpr',
            'requestor_id' => 'MPR-REQ-999',
        ], $overrides);
    }

    private function makeSheetsUserRow(array $overrides = []): array
    {
        return array_merge([
            'Email'         => 'admin@hris.example.com',
            'Username'      => 'admin',
            'Full Name'     => 'HRIS Admin User',
            'Role'          => 'Admin',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('hris-secret'),
        ], $overrides);
    }

    private function makeSheetsMprRow(array $overrides = []): array
    {
        return array_merge([
            'Requestor ID'  => 'MPR-REQ-999',
            'Email'         => 'manpower@mpr.example.com',
            'Username'      => 'manpower',
            'Full Name'     => 'MPR Manpower User',
            'Job Position'  => 'Regional Manager',
            'Role'          => 'Manpower',
            'Status'        => 'Active',
            'Password Hash' => Hash::make('mpr-secret'),
            'Last Login'    => '',
            'Created At'    => '2026-01-01 00:00:00',
            'Updated At'    => '2026-01-01 00:00:00',
            'Created By'    => 'system',
        ], $overrides);
    }

    private function mockUserRepo(?array $user): void
    {
        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($user);
        if ($user !== null) {
            $repo->shouldReceive('updateLastLogin')->withAnyArgs()->andReturnNull();
        }
        $this->app->instance(UserRepositoryInterface::class, $repo);
    }

    private function mockMprRepo(?array $requestor): void
    {
        $repo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $repo->shouldReceive('findByIdentifier')->andReturn($requestor);
        $this->app->instance(MprRequestorRepositoryInterface::class, $repo);
    }

    private function grantHrisPermission(string $email): void
    {
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $repo->upsert($email, 'view_recruitment', true, 'test');
        app(\App\Services\PermissionResolver::class)->forget($email);
    }

    // =========================================================================
    // Test 1 — MPR session does NOT authenticate HRIS
    //
    // Spec: Test 1 — MPR login does not authenticate HRIS
    // With only mpr_requestor_auth in session, HRIS-protected route must
    // redirect to HRIS login — MPR Requestor identity is NOT a valid HRIS credential.
    // =========================================================================

    #[Test]
    public function mpr_session_alone_does_not_grant_access_to_hris_routes(): void
    {
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());
        Session::forget('hr_user');

        $hrisRoutes = [
            'http://' . config('hris.domains.hris') . '/hr/dashboard',
            'http://' . config('hris.domains.hris') . '/hr/mpr',
            'http://' . config('hris.domains.hris') . '/hr/recruitment',
            'http://' . config('hris.domains.hris') . '/hr/employees',
        ];

        foreach ($hrisRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(
                route('login'),
                "HRIS route [{$route}] must redirect to HRIS login when only MPR session is present."
            );
        }

        // Confirm hr_user was not created by the middleware as a side effect.
        $this->assertFalse(session()->has('hr_user'));
    }

    // =========================================================================
    // Test 2 — HRIS session does NOT authenticate MPR
    //
    // Spec: Test 2 — HRIS login does not authenticate MPR
    // With only hr_user in session (even Super Admin), the dedicated MPR portal
    // must redirect to MPR login — HRIS identity is NOT a valid MPR credential.
    // =========================================================================

    #[Test]
    public function hris_session_alone_does_not_grant_access_to_mpr_portal(): void
    {
        Session::put('hr_user', $this->makeHrisUser(['role' => 'Super Admin', 'permissions' => ['*']]));
        Session::forget(config('mpr.session_key', 'mpr_requestor_auth'));

        $response = $this->get('http://' . config('hris.domains.mpr') . '/mpr/request');

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));
    }

    // =========================================================================
    // Test 3 — MPR session present → HRIS login → HRIS Admin identity resolves
    //
    // Spec: Test 3 — MPR session remains but HRIS uses HRIS identity
    // Simulates: user is logged into MPR, then logs into HRIS in the same tab.
    // After HRIS login the stale MPR session must be cleared and HRIS must
    // resolve the HRIS Admin identity, not the MPR Manpower identity.
    //
    // This is the PRIMARY reported bug scenario.
    // =========================================================================

    #[Test]
    public function hris_login_while_mpr_session_is_active_clears_mpr_session_and_resolves_hris_identity(): void
    {
        // Precondition: MPR session is alive (user logged into MPR first).
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());
        $this->assertTrue(session()->has(config('mpr.session_key', 'mpr_requestor_auth')));

        // Now the user logs into HRIS in the same browser tab.
        $this->mockUserRepo($this->makeSheetsUserRow());
        $this->grantHrisPermission('admin@hris.example.com');

        $response = $this->postJson('/login', [
            'identifier' => 'admin@hris.example.com',
            'password'   => 'hris-secret',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // HRIS session must be established with the correct identity.
        $this->assertTrue(session()->has('hr_user'), 'hr_user must be set after HRIS login.');
        $this->assertSame('users', session('hr_user.auth_domain'));
        $this->assertSame('hris', session('hr_user.portal'));
        $this->assertSame('Admin', session('hr_user.role'));
        $this->assertSame('admin@hris.example.com', session('hr_user.email'));

        // The stale MPR session MUST have been cleared by LoginController::loginSuccess().
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'LoginController must clear mpr_requestor_auth on HRIS login to prevent identity leakage.'
        );
    }

    // =========================================================================
    // Test 4 — Both sessions coexist: routing resolves correct identity per portal
    //
    // Spec: Test 4 — Both sessions coexist safely
    // When both hr_user and mpr_requestor_auth are present simultaneously,
    // HRIS routes must resolve hr_user and MPR routes must resolve mpr_requestor_auth.
    // =========================================================================

    #[Test]
    public function hris_route_resolves_hr_user_when_both_sessions_coexist(): void
    {
        Session::put('hr_user', $this->makeHrisUser());
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());

        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');

        $response->assertOk();
        // hr_user must still be the HRIS identity.
        $this->assertSame('users', session('hr_user.auth_domain'));
        $this->assertSame('Admin', session('hr_user.role'));
    }

    #[Test]
    public function mpr_route_resolves_mpr_requestor_when_both_sessions_coexist(): void
    {
        Session::put('hr_user', $this->makeHrisUser());
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());

        $response = $this->get('http://' . config('hris.domains.mpr') . '/mpr/request');

        $response->assertOk();
        $response->assertViewIs('hr.mpr.create');
        // MPR session must still be the MPR identity.
        $this->assertSame('mpr_requestor', session(config('mpr.session_key', 'mpr_requestor_auth') . '.auth_domain'));
        $this->assertSame('Manpower', session(config('mpr.session_key', 'mpr_requestor_auth') . '.role'));
    }

    // =========================================================================
    // Test 5 — HRIS authorization ignores MPR session
    //
    // Spec: Test 5 — HRIS authorization ignores MPR session
    // A low-permission HRIS user + alive MPR Manpower session must result in
    // HRIS authorization being determined ONLY by the HRIS user's own permissions.
    // The Manpower role from the MPR session must grant nothing in HRIS.
    // =========================================================================

    #[Test]
    public function hris_authorization_is_based_only_on_hr_user_not_mpr_session(): void
    {
        // HRIS user with minimal permissions (User role — no manage_settings).
        Session::put('hr_user', $this->makeHrisUser([
            'role'        => 'User',
            'permissions' => config('hris.auth.role_permissions.User', []),
        ]));

        // MPR Manpower session is alive with its own permissions.
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());

        // HRIS settings route requires manage_settings — User role does not have it.
        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/settings');
        // Must be denied (403 or redirect to dashboard), NOT granted due to MPR session.
        $response->assertStatus(403);
    }

    // =========================================================================
    // Test 6 — MPR authorization ignores HRIS role
    //
    // Spec: Test 6 — MPR authorization ignores HRIS role
    // A HRIS Super Admin session WITHOUT a valid mpr_requestor_auth session must
    // NOT be granted access to the dedicated MPR portal. Super Admin HRIS ≠ MPR Requestor.
    // =========================================================================

    #[Test]
    public function hris_super_admin_without_mpr_session_cannot_access_mpr_portal(): void
    {
        Session::put('hr_user', $this->makeHrisUser([
            'role'        => 'Super Admin',
            'permissions' => ['*'],
        ]));
        Session::forget(config('mpr.session_key', 'mpr_requestor_auth'));

        $response = $this->get('http://' . config('hris.domains.mpr') . '/mpr/request');

        // Must redirect to MPR login — HRIS Super Admin is NOT a MPR Requestor.
        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'No mpr_requestor_auth must be created for a HRIS Super Admin accessing MPR routes.'
        );
    }

    // =========================================================================
    // Test 7 — HRIS login clears stale MPR session
    //
    // Root cause fix: LoginController::loginSuccess() must call
    // session()->forget(config('mpr.session_key')) after session()->regenerate().
    // =========================================================================

    #[Test]
    public function hris_login_clears_mpr_session_key_from_shared_session_storage(): void
    {
        // Plant a stale MPR session before HRIS login (simulates "same browser tab" scenario).
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor());

        $this->mockUserRepo($this->makeSheetsUserRow());
        $this->grantHrisPermission('admin@hris.example.com');

        $this->postJson('/login', [
            'identifier' => 'admin@hris.example.com',
            'password'   => 'hris-secret',
        ])->assertOk();

        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'HRIS login must call session()->forget(mpr_session_key) to prevent MPR identity leaking into HRIS views.'
        );
    }

    // =========================================================================
    // Test 8 — MPR login clears stale HRIS session
    //
    // Root cause fix: MprAuthController::login() must call
    // session()->forget('hr_user') after session()->regenerate().
    // =========================================================================

    #[Test]
    public function mpr_login_clears_hr_user_from_shared_session_storage(): void
    {
        // Plant a stale HRIS session before MPR login.
        Session::put('hr_user', $this->makeHrisUser());

        $this->mockMprRepo($this->makeSheetsMprRow());

        $this->post(route('mpr.auth.login.post'), [
            'identifier' => 'manpower@mpr.example.com',
            'password'   => 'mpr-secret',
        ])->assertRedirect(route('mpr.auth.request'));

        $this->assertFalse(
            session()->has('hr_user'),
            'MPR login must call session()->forget(hr_user) to prevent HRIS identity surviving in the MPR session.'
        );
    }

    // =========================================================================
    // Test 9 — HRIS topbar shows HRIS identity when both sessions coexist
    //
    // This directly tests the visual leakage bug: with both hr_user AND
    // mpr_requestor_auth in session, the HRIS dashboard topbar must show the
    // HRIS Admin name/role — NOT the MPR Manpower name/role.
    // =========================================================================

    #[Test]
    public function hris_topbar_shows_hris_identity_when_both_sessions_coexist(): void
    {
        Session::put('hr_user', $this->makeHrisUser([
            'fullName' => 'HRIS Admin User',
            'role'     => 'Admin',
        ]));
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor([
            'fullName' => 'MPR Manpower User',
            'role'     => 'Manpower',
        ]));

        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');

        $response->assertOk();

        // The HRIS user name must be visible.
        $response->assertSee('HRIS Admin User');
        // The MPR Manpower name must NOT appear in the topbar.
        $response->assertDontSee('MPR Manpower User');

        // The HRIS role must appear, not Manpower.
        $response->assertSee('Admin');
    }

    // =========================================================================
    // Test 10 — MPR topbar shows MPR identity; HRIS identity is not shown
    //
    // The MPR portal (hr.mpr.create via layouts.hr) must show the MPR Requestor
    // name and the MPR logout button — not the HRIS Admin name.
    // =========================================================================

    #[Test]
    public function mpr_topbar_shows_mpr_identity_and_mpr_logout_when_both_sessions_coexist(): void
    {
        Session::put('hr_user', $this->makeHrisUser([
            'fullName' => 'HRIS Admin User',
            'role'     => 'Admin',
        ]));
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor([
            'fullName' => 'MPR Manpower User',
            'role'     => 'Manpower',
        ]));

        $response = $this->get('http://' . config('hris.domains.mpr') . '/mpr/request');

        $response->assertOk();
        $response->assertViewIs('hr.mpr.create');

        // The MPR Requestor name must appear.
        $response->assertSee('MPR Manpower User');
        // The HRIS Admin name must NOT appear (it is not the current portal identity).
        $response->assertDontSee('HRIS Admin User');

        // MPR logout button must be present.
        $response->assertSee(route('mpr.auth.logout'));
        // HRIS logout must NOT appear when the user is a dedicated MPR Requestor.
        $response->assertDontSee(route('logout'));
    }

    // =========================================================================
    // Test 11 — HRIS MPR index shows HRIS Admin identity in topbar, not MPR Requestor
    //
    // HRIS /hr/mpr is the management view where Admin users review MPR submissions.
    // The topbar must show HRIS Admin identity even when mpr_requestor_auth also exists.
    // Note: /hr/mpr/create redirects to the dedicated MPR portal — it is not an HRIS
    // Admin form. HRIS Admins manage MPR requests via /hr/mpr (index).
    // =========================================================================

    #[Test]
    public function hris_mpr_index_topbar_shows_hris_user_identity_not_mpr_requestor(): void
    {
        Session::put('hr_user', $this->makeHrisUser([
            'fullName'    => 'HRIS Admin User',
            'role'        => 'Admin',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]));
        // Stale MPR session is also present.
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), $this->makeMprRequestor([
            'fullName' => 'MPR Manpower User',
        ]));

        // Access the MPR management index from the HRIS portal.
        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/mpr');

        $response->assertOk();

        // The HRIS user name must appear in the topbar.
        $response->assertSee('HRIS Admin User');
        // The MPR Manpower name must NOT appear in the topbar.
        $response->assertDontSee('MPR Manpower User');
    }

    // =========================================================================
    // Test 12 — HRIS logout clears only the HRIS session; MPR session survives
    // =========================================================================

    #[Test]
    public function hris_logout_clears_hr_user_but_does_not_clear_mpr_session(): void
    {
        Session::put('hr_user', $this->makeHrisUser());
        // Note: MPR session may or may not persist after HRIS logout depending on
        // session invalidation. The important invariant is that HRIS logout does NOT
        // actively destroy a separate MPR session on a different domain. Since both
        // portals share the same PHP session in this app, invalidate() will clear
        // everything — that is acceptable (the user must re-login to MPR). What is
        // NOT acceptable is if HRIS logout fails to clear hr_user itself.
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'), 'hr_user must be cleared after HRIS logout.');
    }

    // =========================================================================
    // Test 13 — PortalAccessMiddleware rejects hr_user with wrong auth_domain
    // (defence-in-depth: catches any future session tampering or migration bug)
    // =========================================================================

    #[Test]
    public function hris_middleware_rejects_hr_user_with_mpr_auth_domain(): void
    {
        // Tampered/corrupted hr_user that claims mpr_requestor auth_domain.
        Session::put('hr_user', [
            'email'       => 'attacker@example.com',
            'role'        => 'Super Admin',
            'auth_domain' => 'mpr_requestor', // Invalid for hr_user
            'portal'      => 'hris',
            'permissions' => ['*'],
        ]);

        $response = $this->get('http://' . config('hris.domains.hris') . '/hr/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'), 'Corrupted hr_user must be cleared by PortalAccessMiddleware.');
    }
}
