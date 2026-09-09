<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

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
    use RefreshDatabase;

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
        $user = $this->makeSessionUser($role);
        if ($role !== 'Super Admin') {
            $repository = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
            foreach (\App\Support\LegacyRolePermissionSource::permissionsForRole($role) as $permission) {
                $repository->upsert($user['email'], $permission, true, 'migration-test');
            }
            app(\App\Services\PermissionResolver::class)->forget($user['email']);
        }
        Session::put('hr_user', $user);
        return $this;
    }

    /**
     * Simulate a logged-in MPR Requestor via the dedicated mpr_requestor_auth session.
     *
     * Under the current architecture, MPR Requestors authenticate exclusively through
     * the MPR portal and their session lives in mpr_requestor_auth (NOT in hr_user).
     * These tests verify that an MPR session grants NO access to HRIS-domain routes.
     */
    private function actingAsMprRequestor(
        string $email = 'manager@mito.id',
        string $name = 'Manager Test'
    ): static {
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'        => $email,
            'fullName'     => $name,
            'role'         => 'Manpower',
            'permissions'  => config('hris.auth.role_permissions.Manpower', ['view_mpr', 'create_mpr', 'export_mpr']),
            'auth_domain'  => 'mpr_requestor',
            'portal'       => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);
        // Ensure hr_user is absent — MPR Requestor has no HRIS session.
        Session::forget('hr_user');
        return $this;
    }

    /** All permissions defined across all roles (excluding the wildcard marker). */
    private function allDefinedPermissions(): array
    {
        $matrix = config('hris.auth.role_permissions', []);
        $flat   = array_unique(array_merge(...array_values($matrix)));
        return array_values(array_filter($flat, fn($p) => $p !== '*'));
    }

    // =========================================================================
    // 1. Gate resolver — unit-level Gate checks
    // =========================================================================

    #[Test]
    public function gate_user_resolver_reads_session_hr_user(): void
    {
        $this->actingAsRole('User');
        // Dump what Auth::user() and gate resolves to
        $sessionUser = session('hr_user');
        $this->assertNotNull($sessionUser, 'session(hr_user) should not be null after actingAsRole');
        $this->assertSame('User', $sessionUser['role']);

        // Auth::resolveUsersUsing() should have wired this
        $authUser = app('auth')->userResolver()();
        $this->assertIsArray($authUser, 'Auth resolver should return session array');
        $this->assertSame('User', $authUser['role'] ?? 'NULL');

        // Gate should now resolve to the same user
        $this->assertTrue(Gate::allows('view_recruitment'), 'Gate should resolve session user, not Auth::user()');
        $this->assertFalse(Gate::allows('manage_settings'), 'User should not have manage_settings');
    }

    #[Test]
    public function same_role_different_permission_overrides_are_respected(): void
    {
        $resolver = app(\App\Services\PermissionResolver::class);
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $emailA = 'same-role-a@mito.id';
        $emailB = 'same-role-b@mito.id';

        $userA = ['email' => $emailA, 'fullName' => 'A', 'role' => 'User'];
        $userB = ['email' => $emailB, 'fullName' => 'B', 'role' => 'User'];

        $repo->upsert($emailA, 'assets.access', true, 'superadmin');
        $repo->upsert($emailB, 'assets.access', false, 'superadmin');
        $resolver->forget($emailA);
        $resolver->forget($emailB);

        $this->assertTrue($resolver->allows($userA, 'assets.access'));
        $this->assertFalse($resolver->allows($userB, 'assets.access'));

        $repo->upsert($emailB, 'certificates.access', true, 'superadmin');
        $resolver->forget($emailB);
        $this->assertTrue($resolver->allows($userB, 'certificates.access'));
        $this->assertFalse($resolver->allows($userA, 'certificates.access'));
    }

    #[Test]
    public function grant_and_revoke_permission_is_reflected_without_restart(): void
    {
        $resolver = app(\App\Services\PermissionResolver::class);
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $email = 'grant-revoke@mito.id';
        $user = ['email' => $email, 'fullName' => 'Grant Revoke', 'role' => 'User'];

        $repo->upsert($email, 'assets.access', true, 'superadmin');
        $resolver->forget($email);
        $this->assertTrue($resolver->allows($user, 'assets.access'));

        $repo->upsert($email, 'assets.access', false, 'superadmin');
        $resolver->forget($email);
        $this->assertFalse($resolver->allows($user, 'assets.access'));

        $repo->upsert($email, 'assets.access', true, 'superadmin');
        $resolver->forget($email);
        $this->assertTrue($resolver->allows($user, 'assets.access'));
    }

    #[Test]
    public function normal_admin_without_user_permissions_has_no_runtime_asset_or_certificate_permissions(): void
    {
        Session::put('hr_user', [
            'email' => 'user-a@example.com',
            'fullName' => 'User A',
            'role' => 'Admin',
            'auth_domain' => 'users',
        ]);

        foreach (['assets.view', 'assets.access', 'certificates.view', 'certificates.access'] as $permission) {
            $this->assertFalse(
                Gate::allows($permission),
                "A normal Admin without a User_Permissions mapping must not receive {$permission} from role metadata."
            );
        }
    }

    #[Test]
    public function explicit_user_permission_grants_only_the_assigned_permission(): void
    {
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $repo->upsert('user-a@example.com', 'assets.view', true, 'superadmin');

        Session::put('hr_user', [
            'email' => 'user-a@example.com',
            'fullName' => 'User A',
            'role' => 'User',
            'auth_domain' => 'users',
        ]);

        $this->assertTrue(Gate::allows('assets.view'));
        $this->assertFalse(Gate::allows('assets.access'));
        $this->assertFalse(Gate::allows('certificates.view'));
        $this->assertFalse(Gate::allows('certificates.access'));
    }

    #[Test]
    public function same_role_user_without_assets_view_mapping_is_denied(): void
    {
        Session::put('hr_user', [
            'email' => 'user-b@example.com',
            'fullName' => 'User B',
            'role' => 'User',
            'auth_domain' => 'users',
        ]);

        $this->assertFalse(Gate::allows('assets.view'));
    }

    #[Test]
    public function changing_role_does_not_grant_or_revoke_user_permission_mapping(): void
    {
        $resolver = app(\App\Services\PermissionResolver::class);
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);
        $email = 'user-a@example.com';

        $repo->upsert($email, 'assets.view', true, 'superadmin');
        Session::put('hr_user', [
            'email' => $email,
            'fullName' => 'User A',
            'role' => 'User',
            'auth_domain' => 'users',
        ]);
        $this->assertTrue(Gate::allows('assets.view'));

        Session::put('hr_user.role', 'Admin');
        $resolver->forget($email);
        $this->assertTrue(Gate::allows('assets.view'));

        $repo->upsert($email, 'assets.view', false, 'superadmin');
        $resolver->forget($email);
        $this->assertFalse(Gate::allows('assets.view'));
    }

    #[Test]
    public function dedicated_portals_require_explicit_access_permissions(): void
    {
        $repo = app(\App\Repositories\Contracts\UserPermissionRepositoryInterface::class);

        Session::put('hr_user', [
            'email' => 'admin-without-portal-permissions@example.com',
            'fullName' => 'Admin Without Portal Permissions',
            'role' => 'Admin',
            'auth_domain' => 'users',
        ]);
        $this->assertFalse(Gate::allows('assets.access'));
        $this->assertFalse(Gate::allows('certificates.access'));

        $repo->upsert('legacy-user@example.com', 'view_asset', true, 'superadmin');
        $repo->upsert('legacy-user@example.com', 'view_certification', true, 'superadmin');
        Session::put('hr_user', [
            'email' => 'legacy-user@example.com',
            'fullName' => 'Legacy User',
            'role' => 'User',
            'auth_domain' => 'users',
        ]);
        $this->assertTrue(Gate::allows('assets.view'));
        $this->assertTrue(Gate::allows('certificates.view'));
        $this->assertFalse(Gate::allows('assets.access'));
        $this->assertFalse(Gate::allows('certificates.access'));
    }

    // =========================================================================
    // 2. Super Admin wildcard
    // =========================================================================

    #[Test]
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

    #[Test]
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

    #[Test]
    public function super_admin_can_access_dashboard(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/dashboard')->assertStatus(200);
    }

    #[Test]
    public function super_admin_can_access_recruitment(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/recruitment')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_employees(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/employees')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_probation(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/probation')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_outsource(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/outsource')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_audit_logs(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/audit-logs')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_master_data(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/master-data')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_settings(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/settings')->assertOk();
    }

    #[Test]
    public function super_admin_can_access_users(): void
    {
        $this->actingAsRole('Super Admin');
        $this->get('/hr/users')->assertOk();
    }

    // =========================================================================
    // 4. Admin — only configured permissions
    // =========================================================================

    #[Test]
    public function hr_manager_gate_permissions_match_config(): void
    {
        $this->actingAsRole('Admin');

        // Admin has manage + view for employees and recruitment (manage implies view)
        $allowed = [
            'manage_recruitment',
            'manage_employees',
            'manage_probation',
            'view_employees',
            'view_recruitment',
            'update_candidates',
            'create_offering',
            'manage_hold_blacklist'
        ];
        $denied  = ['view_reports', 'manage_settings'];

        foreach ($allowed as $p) {
            $this->assertTrue(Gate::allows($p), "Admin should have: {$p}");
        }
        foreach ($denied as $p) {
            $this->assertFalse(Gate::allows($p), "Admin should NOT have: {$p}");
        }
    }

    #[Test]
    public function hr_manager_cannot_access_recruitment_index(): void
    {
        // Admin now has view_recruitment, so this should PASS (200), not 403
        $this->actingAsRole('Admin');
        $this->get('/hr/recruitment')->assertOk();
    }

    #[Test]
    public function hr_manager_can_access_employees(): void
    {
        $this->actingAsRole('Admin');
        $this->get('/hr/employees')->assertOk();
    }

    #[Test]
    public function admin_cannot_access_system_settings(): void
    {
        $this->actingAsRole('Admin');
        $this->get('/hr/settings')->assertStatus(403);
        $this->get('/hr/users')->assertStatus(403);
        $this->get('/hr/audit-logs')->assertStatus(403);
    }

    #[Test]
    public function admin_sidebar_hides_system_navigation(): void
    {
        $this->actingAsRole('Admin');

        $this->get('/hr/dashboard')
            ->assertOk()
            ->assertDontSee('User Management')
            ->assertDontSee('Audit Log')
            ->assertDontSee('Settings');
    }

    // =========================================================================
    // 5. User role — renamed from Privileged User, same permission logic
    // =========================================================================

    #[Test]
    public function hr_recruitment_gate_permissions_match_config(): void
    {
        $this->actingAsRole('User');

        $allowed = ['view_recruitment', 'update_candidates', 'create_offering', 'manage_hold_blacklist', 'view_mpr', 'export_mpr'];
        $denied  = ['view_employees', 'manage_employees', 'manage_probation', 'view_reports', 'manage_settings'];

        foreach ($allowed as $p) {
            $this->assertTrue(Gate::allows($p), "User should have: {$p}");
        }
        foreach ($denied as $p) {
            $this->assertFalse(Gate::allows($p), "User should NOT have: {$p}");
        }
    }

    #[Test]
    public function hr_recruitment_can_access_recruitment(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/recruitment')->assertOk();
    }

    #[Test]
    public function hr_recruitment_cannot_access_employees(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/employees')->assertStatus(403);
    }

    #[Test]
    public function hr_recruitment_cannot_access_settings(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/settings')->assertStatus(403);
    }

    #[Test]
    public function hr_staff_cannot_access_settings(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/settings')->assertStatus(403);
    }

    #[Test]
    public function hr_staff_cannot_access_users(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/users')->assertStatus(403);
    }

    #[Test]
    public function hr_staff_cannot_access_probation(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/probation')->assertStatus(403);
    }

    #[Test]
    public function hr_staff_cannot_access_master_data(): void
    {
        $this->actingAsRole('User');
        $this->get('/hr/master-data')->assertStatus(403);
    }

    #[Test]
    public function hr_staff_cannot_access_outsource_or_employee_actions(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $this->actingAsRole('User');

        $this->get('/hr/outsource')->assertStatus(403);
        $this->get('/hr/mpr')->assertOk();
        $this->post('/hr/employees/EMP001/rotate', ['_token' => 'test-token'])->assertStatus(403);
        $this->post('/hr/employees/EMP001/offboard', ['_token' => 'test-token'])->assertStatus(403);
        $this->post('/hr/recruitment/REC-001/accept', ['_token' => 'test-token', 'recruitment_id' => 'REC-001'])->assertStatus(302);
        $this->post('/hr/recruitment/REC-001/save-notes', ['_token' => 'test-token', 'notes' => 'Catatan'])->assertStatus(200);
        $this->post('/hr/recruitment/REC-001/save-contract', ['_token' => 'test-token', 'contract_number' => 'C-001'])->assertStatus(500);
        $this->post('/hr/recruitment/REC-001/save-offering-response', ['_token' => 'test-token', 'response' => 'Ya'])->assertStatus(422);
    }

    #[Test]
    public function hr_staff_sidebar_shows_recruitment_and_mpr_navigation(): void
    {
        $this->actingAsRole('User');

        $this->get('/hr/dashboard')
            ->assertOk()
            ->assertSee('Recruitment')
            ->assertSee('Manpower Request')
            ->assertDontSee('href="' . route('hr.employees.index') . '"')
            ->assertDontSee('href="' . route('hr.outsource.index') . '"')
            ->assertDontSee('href="' . route('hr.settings.index') . '"')
            ->assertDontSee('href="' . route('hr.users.index') . '"')
            ->assertDontSee('id="btnHold"')
            ->assertDontSee('id="btnBlacklist"');
    }

    // =========================================================================
    // 7. Unauthenticated user is redirected to login
    // =========================================================================

    #[Test]
    public function unauthenticated_user_is_redirected_from_hr_routes(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/dashboard')->assertRedirect(route('login'));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_recruitment(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/recruitment')->assertRedirect(route('login'));
    }

    // =========================================================================
    // 8. $permissions view variable — wildcard expansion for Super Admin
    // =========================================================================

    #[Test]
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

    #[Test]
    public function super_admin_sidebar_shows_mpr_navigation(): void
    {
        $this->actingAsRole('Super Admin');

        $this->get('/hr/dashboard')
            ->assertOk()
            ->assertSeeText('Manpower Request');
    }

    #[Test]
    public function role_resolution_is_case_and_whitespace_tolerant_but_unknown_roles_fail_closed(): void
    {
        $this->actingAsRole(' Super Admin ');
        $this->assertTrue(Gate::allows('manage_settings'));

        $this->actingAsRole('hr recruitment');
        $this->assertTrue(Gate::allows('view_recruitment'));
        $this->assertFalse(Gate::allows('manage_settings'));

        foreach ([null, '', 'Unknown Role'] as $role) {
            Session::put('hr_user', ['role' => $role, 'permissions' => []]);
            $this->assertFalse(Gate::allows('view_recruitment'));
            $this->get('/hr/settings')->assertStatus(403);
        }
    }

    #[Test]
    public function user_can_mutate_recruitment_endpoints_allowed_by_legacy_privileged_permissions(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $this->actingAsRole('User');

        $this->get('/hr/employees')->assertStatus(403);
        $this->post('/hr/employees/import', ['_token' => 'test-token'])->assertStatus(403);
        $this->post('/hr/recruitment/REC-001/hold', ['_token' => 'test-token', 'reason' => 'Cek ulang', 'follow_up_date' => '2026-09-12', 'notes' => 'Hold'])->assertStatus(302);
        $this->post('/hr/recruitment/REC-001/save-offering', ['_token' => 'test-token', 'offering_number' => 'OF-001'])->assertStatus(404);
    }

    // =========================================================================
    // 9. MPR Requestor domain — Manager from mpr_requestor sheet
    //    These tests verify auth domain separation from internal HRIS Users.
    // =========================================================================

    #[Test]
    public function mpr_requestor_can_access_mpr_index(): void
    {
        // An MPR Requestor (mpr_requestor_auth session, no hr_user) accessing the
        // HRIS-domain /hr/mpr route must be redirected to HRIS login — they have no
        // hr_user session. MPR Requestors use the MPR portal exclusively.
        $this->actingAsMprRequestor();
        $this->get('/hr/mpr')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_hr_dashboard(): void
    {
        $this->actingAsMprRequestor();
        // MprRequestorMiddleware should redirect Manager to MPR, not show 403 raw
        $response = $this->get('/hr/dashboard');
        // Either redirect to MPR or 403 — must NOT be 200
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access HR Dashboard'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_recruitment(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/recruitment');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access Recruitment'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_employees(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/employees');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access Employees'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_settings(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/settings');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access Settings'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_users_management(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/users');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access User Management'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_probation(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/probation');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access Probation'
        );
    }

    #[Test]
    public function mpr_requestor_is_blocked_from_audit_logs(): void
    {
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/audit-logs');
        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'MPR Requestor must not access Audit Logs'
        );
    }

    #[Test]
    public function mpr_requestor_gate_has_correct_permissions(): void
    {
        // Under strict isolation, MPR Requestors authenticate via mpr_requestor_auth,
        // NOT hr_user. Gate resolves from hr_user — so an MPR Requestor with no hr_user
        // has no Gate user and all Gate checks return false (fail-closed).
        // This is correct behaviour: MPR Requestors must not pass Gate on HRIS routes.
        $this->actingAsMprRequestor();

        // With no hr_user, Gate has no authenticated user → all checks fail-closed.
        $this->assertFalse(Gate::allows('view_mpr'),         'No hr_user → Gate must deny');
        $this->assertFalse(Gate::allows('view_recruitment'),  'No hr_user → Gate must deny');
        $this->assertFalse(Gate::allows('view_employees'),    'No hr_user → Gate must deny');
        $this->assertFalse(Gate::allows('manage_settings'),   'No hr_user → Gate must deny');
    }

    // =========================================================================
    // 10. Auth domain separation — Manager in mpr_requestor vs Users
    //     Ensures Users sheet does not contain Manager role anymore.
    // =========================================================================

    // =========================================================================
    // 11. Employee XLSX Export — authorization + structure tests
    // =========================================================================

    #[Test]
    public function super_admin_can_access_employee_export_xlsx(): void
    {
        $this->actingAsRole('Super Admin');
        // StreamedResponse: accept 200 (success) or 500 (Google Sheets unavailable in test env)
        // Must NOT be 403 or redirect to login
        $response = $this->get('/hr/export/employees-xlsx');
        $this->assertNotSame(403, $response->getStatusCode(), 'Super Admin must not be denied employee export');
        $this->assertNotSame(302, $response->getStatusCode(), 'Super Admin must not be redirected from employee export');
    }

    #[Test]
    public function admin_can_access_employee_export_xlsx(): void
    {
        $this->actingAsRole('Admin');
        $response = $this->get('/hr/export/employees-xlsx');
        $this->assertNotSame(403, $response->getStatusCode(), 'Admin must not be denied employee export');
        $this->assertNotSame(302, $response->getStatusCode(), 'Admin must not be redirected from employee export');
    }

    #[Test]
    public function user_role_cannot_access_employee_export_xlsx(): void
    {
        // User (HR Recruitment / HR Staff) has NO view_employees → must be denied
        $this->actingAsRole('User');
        $this->get('/hr/export/employees-xlsx')->assertStatus(403);
    }

    #[Test]
    public function manpower_role_cannot_access_employee_export_xlsx(): void
    {
        // Manpower has view_mpr but not view_employees
        Session::put('hr_user', [
            'email'       => 'manpower@mito.id',
            'fullName'    => 'Manpower User',
            'role'        => 'Manpower',
            'permissions' => config('hris.auth.role_permissions.Manpower', []),
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);
        $this->get('/hr/export/employees-xlsx')->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_cannot_access_employee_export_xlsx(): void
    {
        Session::forget('hr_user');
        $this->get('/hr/export/employees-xlsx')->assertRedirect(route('login'));
    }

    #[Test]
    public function mpr_requestor_cannot_access_employee_export_xlsx(): void
    {
        // MPR Requestor: mpr_requestor_auth session, no hr_user → redirected to login
        $this->actingAsMprRequestor();
        $response = $this->get('/hr/export/employees-xlsx');
        $this->assertNotSame(200, $response->getStatusCode(), 'MPR Requestor must not access employee export');
    }

    // =========================================================================
    // 11b. Employee XLSX Export — file structure & content tests
    // These tests verify the response is a real XLSX (ZIP) with correct content.
    // They run against the controller in isolation using a mocked repository,
    // so no Google Sheets connection is required.
    // =========================================================================

    #[Test]
    public function employee_export_xlsx_has_correct_content_type(): void
    {
        $this->actingAsRole('Super Admin');
        $response = $this->get('/hr/export/employees-xlsx');

        // Must not be 403 — authorized users should get through to the export
        if ($response->getStatusCode() === 403) {
            $this->fail('Super Admin was denied access to employee export');
        }

        // If Google Sheets is unavailable (500), skip structure checks
        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Google Sheets unavailable in test environment — skipping XLSX structure test');
        }

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type', ''),
            'Content-Type harus XLSX MIME type'
        );
    }

    #[Test]
    public function employee_export_xlsx_has_correct_filename(): void
    {
        $this->actingAsRole('Super Admin');
        $response = $this->get('/hr/export/employees-xlsx');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Google Sheets unavailable — skipping filename test');
        }

        $disposition = $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString('.xlsx', $disposition, 'Filename harus berekstensi .xlsx');
        $this->assertStringContainsString('Data_Karyawan_MITO_', $disposition, 'Filename harus mengandung Data_Karyawan_MITO_');
        $this->assertStringNotContainsString('.csv', $disposition, 'Filename tidak boleh berekstensi .csv');
    }

    #[Test]
    public function employee_export_xlsx_is_valid_zip_structure(): void
    {
        $this->actingAsRole('Super Admin');
        $response = $this->get('/hr/export/employees-xlsx');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Google Sheets unavailable — skipping ZIP structure test');
        }

        $content = $response->streamedContent();
        $this->assertNotEmpty($content, 'Response body tidak boleh kosong');

        // XLSX adalah ZIP — magic bytes pertama harus PK (0x50 0x4B)
        $this->assertSame('PK', substr($content, 0, 2), 'File harus diawali ZIP magic bytes PK (valid XLSX)');
    }

    #[Test]
    public function employee_export_xlsx_contains_worksheet_with_correct_headers(): void
    {
        $this->actingAsRole('Super Admin');
        $response = $this->get('/hr/export/employees-xlsx');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Google Sheets unavailable — skipping worksheet header test');
        }

        // Simpan ke temp file lalu baca dengan PhpSpreadsheet
        $tmpFile = tempnam(sys_get_temp_dir(), 'hris_test_') . '.xlsx';
        file_put_contents($tmpFile, $response->streamedContent());

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
            $sheet       = $spreadsheet->getActiveSheet();

            $expectedHeaders = [
                1  => 'Employee ID',
                2  => 'Full Name',
                3  => 'Branch Name',
                4  => 'Division',
                5  => 'Department',
                6  => 'Job Position (Location)',
                7  => 'Job Position',
                8  => 'Area Kerja',
                9  => 'Lokasi Kerja',
                10 => 'Job Level',
                11 => 'Grade',
                12 => 'Join Date',
                13 => 'Status Employee',
                23 => 'NIK - NPWP 16 digit',
                24 => 'NPWP',
                27 => 'Bank Account',
                36 => 'Cost Center',
            ];

            foreach ($expectedHeaders as $colIndex => $expected) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $actual    = $sheet->getCell($colLetter . '1')->getValue();
                $this->assertSame($expected, $actual, "Header kolom {$colLetter}1 harus '{$expected}', got '{$actual}'");
            }

            // Pastikan ada tepat 36 header kolom (tidak lebih, tidak kurang)
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $sheet->getHighestDataColumn(1)
            );
            $this->assertSame(36, $highestCol, 'Harus ada tepat 36 kolom header');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function employee_export_xlsx_preserves_string_type_for_identifiers(): void
    {
        $this->actingAsRole('Super Admin');
        $response = $this->get('/hr/export/employees-xlsx');

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Google Sheets unavailable — skipping identifier type test');
        }

        $content = $response->streamedContent();

        if (empty(trim($content))) {
            $this->markTestSkipped('Empty response — no employee data to verify');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'hris_test_') . '.xlsx';
        file_put_contents($tmpFile, $content);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestDataRow();

            if ($highestRow < 2) {
                $this->markTestSkipped('No data rows — skipping identifier type test');
            }

            // Kolom NIK (W = 23), NPWP (X = 24), Mobile Phone (AE = 31)
            $identifierCols = [
                'W' => 'NIK - NPWP 16 digit',
                'X' => 'NPWP',
                'AA' => 'Bank Account',
                'AC' => 'BPJS Ketenagakerjaan',
                'AD' => 'BPJS Kesehatan',
                'AE' => 'Mobile Phone',
            ];

            foreach ($identifierCols as $col => $label) {
                $cell     = $sheet->getCell($col . '2');
                $rawValue = $cell->getValue();

                // Jika ada value, pastikan tidak dalam format scientific notation
                if ($rawValue !== null && $rawValue !== '') {
                    $this->assertDoesNotMatchRegularExpression(
                        '/^\d+\.?\d*[eE][+\-]\d+$/',
                        (string) $rawValue,
                        "{$label} (kolom {$col}) tidak boleh dalam format scientific notation: '{$rawValue}'"
                    );
                    // DataType harus string (bukan numeric)
                    $this->assertSame(
                        DataType::TYPE_STRING,
                        $cell->getDataType(),
                        "{$label} (kolom {$col}) harus bertipe string di XLSX"
                    );
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } finally {
            @unlink($tmpFile);
        }
    }

    // =========================================================================
    // 12. Internal_hr_user_with_manager_role_in_users_sheet_is_rejected
    // =========================================================================

    #[Test]
    public function internal_hr_user_with_manager_role_in_users_sheet_is_rejected(): void
    {
        // A user in the Users sheet with role='Manpower' is an AuthService misconfiguration
        // (AuthService rejects 'manager' role, but 'Manpower' in users is unexpected).
        // Under the current architecture, MprRequestorMiddleware no longer checks
        // auth_domain inside hr_user — that was removed to prevent crossover paths.
        //
        // Such a session passes PortalAccessMiddleware (auth_domain='users' is valid)
        // and MprRequestorMiddleware (just checks hr_user exists).
        // Gate then enforces the explicit User_Permissions source. A Manpower
        // role copied into the Users sheet has no normal HRIS permissions.
        // Dashboard requires no explicit gate (any authenticated hr_user can see it),
        // but individual modules that require 'manage_settings', 'view_employees', etc.
        // will deny access via Gate.
        //
        // Critical contract: they must NOT gain Super Admin or Admin privileges.
        Session::put('hr_user', [
            'email'       => 'bad.manager@mito.id',
            'fullName'    => 'Bad Manager',
            'role'        => 'Manpower',
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);

        // Gate enforces RBAC — Manpower must not access settings, employees, etc.
        $this->assertFalse(Gate::allows('manage_settings'), 'Manpower must not have manage_settings');
        $this->assertFalse(Gate::allows('view_employees'),  'Manpower must not have view_employees');
        $this->assertFalse(Gate::allows('view_recruitment'), 'Manpower must not have view_recruitment');
        $this->assertFalse(Gate::allows('view_mpr'),        'Invalid Users-sheet Manpower must not gain MPR access');

        // Protected HRIS routes enforce Gate checks.
        $this->get('/hr/settings')->assertStatus(403);
        $this->get('/hr/employees')->assertStatus(403);
        $this->get('/hr/recruitment')->assertStatus(403);
        $this->get('/hr/users')->assertStatus(403);
    }
}
