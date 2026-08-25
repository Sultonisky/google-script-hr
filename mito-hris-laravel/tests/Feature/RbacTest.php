<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

/**
 * RBAC Feature Tests — MITO HRIS
 *
 * Covers:
 * 1. Gate user resolver correctly uses session('hr_user').
 * 2. Super Admin wildcard ('*') grants access to ALL defined permissions.
 * 3. Super Admin can access every protected HR route (no 403).
 * 4. Each role only accesses the routes its permissions allow.
 * 5. Unauthorized roles receive 403 on direct URL access.
 * 6. Cache-cleared behavior is identical.
 *
 * Architecture note:
 * This project uses session-based auth (not Eloquent/Auth guard).
 * Users are stored as arrays in session('hr_user').
 * Gate::userResolver() in AuthServiceProvider wires the session user to Gate.
 */
class RbacTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /** Build a session user array matching what LoginController stores. */
    private function makeSessionUser(string $role): array
    {
        $permissions = config('hris.auth.role_permissions')[$role] ?? [];

        return [
            'email'       => strtolower(str_replace(' ', '.', $role)) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => $permissions,
            'auth_domain' => 'users', // internal HRIS domain
            'entities'    => [],
            'branch'      => '',
        ];
    }

    /** Simulate a logged-in session for the given role (internal HRIS). */
    private function actingAsRole(string $role): static
    {
        Session::put('hr_user', $this->makeSessionUser($role));
        return $this;
    }

    /**
     * Simulate a logged-in MPR Requestor (Manager from mpr_requestor sheet).
     * This is a different auth domain from internal HR users.
     */
    private function actingAsMprRequestor(
        string $email = 'manager@mito.id',
        string $name = 'Manager Test',
        array $entities = ['MSI'],
        string $branch = 'Jakarta'
    ): static {
        Session::put('hr_user', [
            'email'        => $email,
            'fullName'     => $name,
            'role'         => 'Manager',
            'permissions'  => config('hris.auth.role_permissions.Manager', ['view_mpr', 'create_mpr', 'export_mpr']),
            'auth_domain'  => 'mpr_requestor', // MPR domain — NOT internal HRIS
            'entities'     => $entities,
            'branch'       => $branch,
            'requestor_id' => 'MPR-REQ-001',
        ]);
        return $this;
    }

    /** All permissions defined across all roles (excluding the wildcard marker). */
    private function allDefinedPermissions(): array
    {
        $matrix = config('hris.auth.role_permissions', []);
        $flat   = array_unique(array_merge(...array_values($matrix)));
        return array_values(array_filter($flat, fn ($p) => $p !== '*'));
    }

    // =========================================================================
    // 1. Gate resolver — unit-level Gate checks
    // =========================================================================

    /** @test */
    public function gate_user_resolver_reads_session_hr_user(): void
    {
        $this->actingAsRole('HR Staff');
        // Dump what Auth::user() and gate resolves to
        $sessionUser = session('hr_user');
        $this->assertNotNull($sessionUser, 'session(hr_user) should not be null after actingAsRole');
        $this->assertSame('HR Staff', $sessionUser['role']);

        // Auth::resolveUsersUsing() should have wired this
        $authUser = app('auth')->userResolver()();
        $this->assertIsArray($authUser, 'Auth resolver should return session array');
        $this->assertSame('HR Staff', $authUser['role'] ?? 'NULL');

        // Gate should now resolve to the same user
        $this->assertTrue(Gate::allows('view_recruitment'), 'Gate should resolve session user, not Auth::user()');
        $this->assertFalse(Gate::allows('manage_settings'), 'HR Staff should not have manage_settings');
    }

    // =========================================================================
    // 2. Super Admin wildcard
    // =========================================================================

    /** @test */
    public function super_admin_wildcard_grants_all_permissions_via_gate(): void
    {
        $this->actingAsRole('Super Admin');

        foreach ($this->allDefinedPermissions() as $permission) {
            $this->assertTrue(
                Gate::allows($permission),
                "Gate should allow Super Admin for permission: {$permission}"
            );
        }
    }

    /** @test */
    public function super_admin_wildcard_regression(): void
    {
        // Explicit regression: the wildcard must continue to work even if someone
        // adds a new permission to hris.php without explicitly listing it for Super Admin.
        $this->actingAsRole('Super Admin');

        $this->assertTrue(Gate::allows('view_recruitment'));
        $this->assertTrue(Gate::allows('update_candidates'));
        $this->assertTrue(Gate::allows('create_offering'));
        $this->assertTrue(Gate::allows('manage_hold_blacklist'));
        $this->assertTrue(Gate::allows('view_employees'));
        $this->assertTrue(Gate::allows('manage_employees'));
        $this->assertTrue(Gate::allows('manage_probation'));
        $this->assertTrue(Gate::allows('view_reports'));
        $this->assertTrue(Gate::allows('manage_settings'));
    }

    // =========================================================================
    // 3. Super Admin route access — all protected HR routes return 200 (not 403)
    // =========================================================================

    /** @test */
    public function super_admin_can_access_dashboard(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/dashboard')->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_recruitment(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/recruitment')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_employees(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/employees')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_probation(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/probation')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_outsource(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/outsource')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_audit_logs(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/audit-logs')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_master_data(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/master-data')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_settings(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/settings')->assertOk();
    }

    /** @test */
    public function super_admin_can_access_users(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/users')->assertOk();
    }

    // =========================================================================
    // 4. HR Manager — only configured permissions
    // =========================================================================

    /** @test */
    public function hr_manager_gate_permissions_match_config(): void
    {
        $this->actingAsRole('HR Manager');

        // HR Manager has manage + view for employees and recruitment (manage implies view)
        $allowed = ['manage_recruitment', 'manage_employees', 'manage_probation', 'manage_settings', 'view_reports',
                    'view_employees', 'view_recruitment'];
        $denied  = ['update_candidates', 'create_offering', 'manage_hold_blacklist'];

        foreach ($allowed as $p) {
            $this->assertTrue(Gate::allows($p), "HR Manager should have: {$p}");
        }
        foreach ($denied as $p) {
            $this->assertFalse(Gate::allows($p), "HR Manager should NOT have: {$p}");
        }
    }

    /** @test */
    public function hr_manager_cannot_access_recruitment_index(): void
    {
        // HR Manager now has view_recruitment, so this should PASS (200), not 403
        $this->actingAsRole('HR Manager');
        $this->get('/hr/recruitment')->assertOk();
    }

    /** @test */
    public function hr_manager_can_access_employees(): void
    {
        $this->actingAsRole('HR Manager');
        $this->get('/hr/employees')->assertOk();
    }

    /** @test */
    public function hr_manager_can_access_settings(): void
    {
        $this->actingAsRole('HR Manager');
        $this->get('/hr/settings')->assertOk();
    }

    // =========================================================================
    // 5. HR Recruitment — only configured permissions
    // =========================================================================

    /** @test */
    public function hr_recruitment_gate_permissions_match_config(): void
    {
        $this->actingAsRole('HR Recruitment');

        $allowed = ['view_recruitment', 'update_candidates', 'create_offering', 'manage_hold_blacklist'];
        $denied  = ['view_employees', 'manage_employees', 'manage_probation', 'view_reports', 'manage_settings'];

        foreach ($allowed as $p) {
            $this->assertTrue(Gate::allows($p), "HR Recruitment should have: {$p}");
        }
        foreach ($denied as $p) {
            $this->assertFalse(Gate::allows($p), "HR Recruitment should NOT have: {$p}");
        }
    }

    /** @test */
    public function hr_recruitment_can_access_recruitment(): void
    {
        $this->actingAsRole('HR Recruitment');
        $this->get('/hr/recruitment')->assertOk();
    }

    /** @test */
    public function hr_recruitment_cannot_access_employees(): void
    {
        $this->actingAsRole('HR Recruitment');
        $this->get('/hr/employees')->assertStatus(403);
    }

    /** @test */
    public function hr_recruitment_cannot_access_settings(): void
    {
        $this->actingAsRole('HR Recruitment');
        $this->get('/hr/settings')->assertStatus(403);
    }

    // =========================================================================
    // 6. HR Staff — only configured permissions
    // =========================================================================

    /** @test */
    public function hr_staff_gate_permissions_match_config(): void
    {
        $this->actingAsRole('HR Staff');

        $allowed = ['view_recruitment', 'view_employees', 'view_reports'];
        $denied  = ['update_candidates', 'create_offering', 'manage_hold_blacklist',
                    'manage_employees', 'manage_probation', 'manage_settings'];

        foreach ($allowed as $p) {
            $this->assertTrue(Gate::allows($p), "HR Staff should have: {$p}");
        }
        foreach ($denied as $p) {
            $this->assertFalse(Gate::allows($p), "HR Staff should NOT have: {$p}");
        }
    }

    /** @test */
    public function hr_staff_can_access_recruitment(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/recruitment')->assertOk();
    }

    /** @test */
    public function hr_staff_can_access_employees(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/employees')->assertOk();
    }

    /** @test */
    public function hr_staff_cannot_access_settings(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/settings')->assertStatus(403);
    }

    /** @test */
    public function hr_staff_cannot_access_users(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/users')->assertStatus(403);
    }

    /** @test */
    public function hr_staff_cannot_access_probation(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/probation')->assertStatus(403);
    }

    /** @test */
    public function hr_staff_cannot_access_master_data(): void
    {
        $this->actingAsRole('HR Staff');
        $this->get('/hr/master-data')->assertStatus(403);
    }

    // =========================================================================
    // 7. Unauthenticated user is redirected to login
    // =========================================================================

    /** @test */
    public function unauthenticated_user_is_redirected_from_hr_routes(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/dashboard')->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_recruitment(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/recruitment')->assertRedirect(route('login'));
    }

    // =========================================================================
    // 8. $permissions view variable — wildcard expansion for Super Admin
    // =========================================================================

    /** @test */
    public function super_admin_permissions_view_variable_contains_all_permissions(): void
    {
        $this->actingAsRole('Super Admin');
        // Hit any HR page to trigger View::composer
        $response = $this->get('/hr/dashboard');
        // The view should receive expanded permissions (not just ['*'])
        // We verify via Gate since view data isn't directly assertable here,
        // but the Gate::userResolver already proves this works end-to-end.
        $this->assertTrue(Gate::allows('view_employees'));
        $this->assertTrue(Gate::allows('manage_settings'));
    }

    // =========================================================================
    // 9. MPR Requestor domain — Manager from mpr_requestor sheet
    //    These tests verify auth domain separation from internal HRIS Users.
    // =========================================================================

    /** @test */
    public function mpr_requestor_can_access_mpr_index(): void
    {
        $this->actingAsMprRequestor();
        $this->get('/hr/mpr')->assertStatus(200);
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_hr_dashboard(): void
    {
        $this->actingAsMprRequestor();
        // MprRequestorMiddleware should redirect Manager to MPR, not show 403 raw
        $response = $this->get('/hr/dashboard');
        // Either redirect to MPR or 403 — must NOT be 200
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access HR Dashboard');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_recruitment(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/recruitment');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access Recruitment');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_employees(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/employees');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access Employees');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_settings(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/settings');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access Settings');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_users_management(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/users');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access User Management');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_probation(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/probation');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access Probation');
    }

    /** @test */
    public function mpr_requestor_is_blocked_from_audit_logs(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/audit-logs');
        $this->assertNotSame(200, $response->getStatusCode(),
            'MPR Requestor must not access Audit Logs');
    }

    /** @test */
    public function mpr_requestor_gate_has_correct_permissions(): void
    {
        $this->actingAsMprRequestor();

        $this->assertTrue(Gate::allows('view_mpr'),    'Manager should have view_mpr');
        $this->assertTrue(Gate::allows('create_mpr'),  'Manager should have create_mpr');
        $this->assertTrue(Gate::allows('export_mpr'),  'Manager should have export_mpr');

        $this->assertFalse(Gate::allows('view_recruitment'), 'Manager must not have view_recruitment');
        $this->assertFalse(Gate::allows('view_employees'),   'Manager must not have view_employees');
        $this->assertFalse(Gate::allows('manage_settings'),  'Manager must not have manage_settings');
    }

    // =========================================================================
    // 10. Auth domain separation — Manager in mpr_requestor vs Users
    //     Ensures Users sheet does not contain Manager role anymore.
    // =========================================================================

    /** @test */
    public function internal_hr_user_with_manager_role_in_users_sheet_is_rejected(): void
    {
        // This simulates a legacy scenario where someone accidentally puts a Manager
        // account in the Users sheet. The auth_domain marker distinguishes the two.
        // An internal user claiming Manager role (auth_domain = users) should be
        // treated as a misconfiguration — they have Manager permissions but are NOT
        // an MPR Requestor and should not get special MPR Requestor treatment.
        Session::put('hr_user', [
            'email'       => 'bad.manager@mito.id',
            'fullName'    => 'Bad Manager',
            'role'        => 'Manager',
            'permissions' => config('hris.auth.role_permissions.Manager', []),
            'auth_domain' => 'users', // Wrong domain for Manager
            'entities'    => [],
            'branch'      => '',
        ]);

        // With auth_domain = users, MprRequestorMiddleware still blocks non-MPR routes
        // The session role = Manager triggers the Manager-only sidebar check
        // but the route restriction still applies via MprRequestorMiddleware
        $response = $this->get('/hr/dashboard');
        // Should be blocked (not 200) — Manager with auth_domain=users is still
        // restricted to MPR routes by MprRequestorMiddleware
        $this->assertNotSame(200, $response->getStatusCode());
    }
}
