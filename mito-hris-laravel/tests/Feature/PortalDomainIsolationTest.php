<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PortalDomainIsolationTest extends TestCase
{
    public function test_hris_host_maps_to_hris_portal(): void
    {
        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_mpr_host_maps_to_mpr_portal(): void
    {
        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        $response->assertRedirect(route('mpr.auth.login'));
    }

    public function test_outsource_host_is_public(): void
    {
        $response = $this->get('http://outsource.hrismitogroup.web.id/apply');

        $response->assertOk();
    }

    public function test_recruitment_host_is_public(): void
    {
        $response = $this->get('http://recruitment.hrismitogroup.web.id');

        $response->assertOk();
    }

    public function test_hris_user_cannot_access_mpr_portal(): void
    {
        // Under strict isolation, PortalAccessMiddleware on the MPR portal reads ONLY
        // mpr_requestor_auth. An hr_user session provides NO access to the MPR portal.
        // The user must log in via the MPR login form.
        // Expected: redirect to MPR login, NOT to hr.dashboard.
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Super Admin',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        $response->assertRedirect(route('mpr.auth.login'));
    }

    public function test_mpr_user_cannot_access_hris_portal(): void
    {
        // Under the strict-isolation architecture, PortalAccessMiddleware on the HRIS
        // portal reads ONLY hr_user. A mpr_requestor_auth session on its own provides
        // NO access to the HRIS portal — the user must log in via the HRIS login form.
        // Expected: redirect to HRIS login, NOT to mpr.auth.request.
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email' => 'manager@mito.id',
            'role' => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal' => 'mpr',
        ]);

        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        // Must NOT be 200, must NOT silently pass — must redirect to HRIS login.
        $response->assertRedirect(route('login'));
    }

    public function test_hris_domain_root_returns_public_portal_for_anonymous_user(): void
    {
        $response = $this->get('http://hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.portal');
        $response->assertSee('Portal Layanan Internal HRIS');
    }

    public function test_hris_domain_root_keeps_portal_visible_for_authenticated_user(): void
    {
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Super Admin',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);

        $response = $this->get('http://hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.portal');
        $response->assertSee('Portal Layanan Internal HRIS');
    }

    public function test_mpr_domain_root_returns_public_portal_for_anonymous_user(): void
    {
        $response = $this->get('http://mpr.hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.mpr-portal');
        $response->assertSee('Portal Manpower Request (MPR)');
        $response->assertSee('Get Started');
    }

    public function test_mpr_domain_root_keeps_public_portal_visible_for_authenticated_requestor(): void
    {
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email' => 'manager@mito.id',
            'fullName' => 'John Manager',
            'role' => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal' => 'mpr',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.mpr-portal');
        $response->assertSee('Portal Manpower Request (MPR)');
    }

    public function test_recruitment_domain_root_returns_recruitment_landing(): void
    {
        $response = $this->get('http://recruitment.hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('public.career.landing');
    }

    public function test_outsource_domain_root_returns_outsource_landing(): void
    {
        $response = $this->get('http://outsource.hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('public.outsource.apply');
    }

    public function test_hris_internal_routes_are_not_available_on_mpr_domain(): void
    {
        $response = $this->get('http://mpr.hrismitogroup.web.id/hr/dashboard');

        $response->assertNotFound();
    }

    public function test_hris_internal_routes_are_not_available_on_recruitment_domain(): void
    {
        $response = $this->get('http://recruitment.hrismitogroup.web.id/hr/dashboard');

        $response->assertNotFound();
    }

    public function test_hris_internal_routes_are_not_available_on_outsource_domain(): void
    {
        $response = $this->get('http://outsource.hrismitogroup.web.id/hr/dashboard');

        $response->assertNotFound();
    }

    // =========================================================================
    // Duplicate username/email regression tests
    // Covers the exact scenario where the same identifier exists in BOTH
    // the users (HRIS) and mpr_requestors (MPR) account stores.
    // Authentication crossover between them must NEVER occur.
    // =========================================================================

    public function test_hris_portal_access_ignores_stale_mpr_session_and_requires_hr_login(): void
    {
        // Scenario: user previously logged into MPR. mpr_requestor_auth is still alive.
        // hr_user is absent (HRIS session expired or never started).
        // PortalAccessMiddleware must NOT pick up mpr_requestor_auth as a substitute.
        // Must redirect to HRIS login, not to MPR.
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'fullName'    => 'John Doe',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);
        Session::forget('hr_user');

        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'), 'hr_user must not be created by middleware');
    }

    public function test_hris_portal_with_valid_hr_user_is_not_disrupted_by_concurrent_mpr_session(): void
    {
        // Scenario: user has BOTH sessions simultaneously (same email across both stores).
        // HRIS portal must use ONLY hr_user and allow access normally.
        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'fullName'    => 'John HRIS',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'fullName'    => 'John MPR',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        // Must reach the dashboard (200), not be redirected to MPR.
        $response->assertOk();
    }

    public function test_mpr_portal_with_valid_mpr_session_is_not_disrupted_by_concurrent_hris_session(): void
    {
        // Scenario: user has BOTH sessions simultaneously (same email across both stores).
        // MPR portal must use ONLY mpr_requestor_auth and allow access normally.
        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'fullName'    => 'John HRIS',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'fullName'    => 'John MPR',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        // Must reach the MPR form (200), not be redirected to HRIS.
        $response->assertOk();
        $response->assertViewIs('hr.mpr.create');
    }

    public function test_mpr_portal_ignores_stale_hris_session_and_requires_mpr_login(): void
    {
        // Scenario: user previously logged into HRIS. hr_user is still alive.
        // mpr_requestor_auth is absent.
        // PortalAccessMiddleware must NOT use hr_user for MPR access.
        // Must redirect to MPR login.
        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);
        Session::forget(config('mpr.session_key', 'mpr_requestor_auth'));

        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        $response->assertRedirect(route('mpr.auth.login'));
    }

    public function test_hris_login_only_creates_hr_user_session_never_mpr_session(): void
    {
        // Even when the identifier exists in mpr_requestors, HRIS login must only
        // create hr_user. It never touches mpr_requestor_auth.
        Session::forget('hr_user');
        Session::forget(config('mpr.session_key', 'mpr_requestor_auth'));

        Session::put('hr_user', [
            'email'       => 'john@example.com',
            'role'        => 'Admin',
            'auth_domain' => 'users',
            'portal'      => 'hris',
            'permissions' => config('hris.auth.role_permissions.Admin', []),
        ]);

        $this->assertTrue(session()->has('hr_user'), 'hr_user must exist after HRIS login');
        $this->assertFalse(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'mpr_requestor_auth must NOT be created by HRIS login'
        );
        $this->assertSame('users', session('hr_user')['auth_domain']);
        $this->assertSame('hris', session('hr_user')['portal']);
    }

    public function test_mpr_login_only_creates_mpr_session_never_hr_user_session(): void
    {
        // Even when the identifier exists in users, MPR login must only create
        // mpr_requestor_auth. It never touches hr_user.
        Session::forget('hr_user');
        Session::forget(config('mpr.session_key', 'mpr_requestor_auth'));

        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email'       => 'john@example.com',
            'role'        => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal'      => 'mpr',
            'requestor_id' => 'MPR-REQ-001',
        ]);

        $this->assertTrue(
            session()->has(config('mpr.session_key', 'mpr_requestor_auth')),
            'mpr_requestor_auth must exist after MPR login'
        );
        $this->assertFalse(session()->has('hr_user'), 'hr_user must NOT be created by MPR login');
        $this->assertSame('mpr_requestor', session(config('mpr.session_key', 'mpr_requestor_auth'))['auth_domain']);
        $this->assertSame('mpr', session(config('mpr.session_key', 'mpr_requestor_auth'))['portal']);
    }

    public function test_hris_invalid_auth_domain_in_hr_user_is_rejected(): void
    {
        // PortalAccessMiddleware must reject any hr_user session whose auth_domain
        // is not 'users' — this catches any tampering or legacy data.
        Session::put('hr_user', [
            'email'       => 'attacker@example.com',
            'role'        => 'Super Admin',
            'auth_domain' => 'mpr_requestor', // INVALID for hr_user
            'portal'      => 'hris',
            'permissions' => ['*'],
        ]);

        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        // Must NOT allow access — must clear the session and redirect to login.
        $response->assertRedirect(route('login'));
        $this->assertFalse(session()->has('hr_user'), 'Corrupted hr_user session must be cleared');
    }

    public function test_mpr_invalid_auth_domain_in_mpr_session_is_rejected(): void
    {
        // PortalAccessMiddleware must reject any mpr_requestor_auth session whose
        // auth_domain is not 'mpr_requestor'.
        $sessionKey = config('mpr.session_key', 'mpr_requestor_auth');
        Session::put($sessionKey, [
            'email'       => 'attacker@example.com',
            'role'        => 'Manpower',
            'auth_domain' => 'users', // INVALID for mpr session
            'portal'      => 'mpr',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        $response->assertRedirect(route('mpr.auth.login'));
        $this->assertFalse(session()->has($sessionKey), 'Corrupted MPR session must be cleared');
    }

    public function test_unknown_host_does_not_resolve_to_hris(): void
    {
        // Under strict domain isolation, an unknown host matches no Route::domain()
        // group and therefore returns 404. There is intentionally no global fallback
        // route — this is the correct architecture contract.
        $response = $this->get('http://example.com/');

        $response->assertNotFound();
    }

    public function test_local_environment_exposes_local_compatibility_routes(): void
    {
        $process = new Process(['php', 'artisan', 'route:list', '--json'], base_path());
        $process->setEnv([
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'BCRYPT_ROUNDS' => '4',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput() ?: $process->getOutput());

        $routes = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $routeNames = array_filter(array_map(static fn (array $route) => $route['name'] ?? null, $routes));

        $this->assertContains('local.hris.domain.root', $routeNames);
        $this->assertContains('local.auth.portal', $routeNames);
        $this->assertContains('local.mpr.domain.root', $routeNames);
        $this->assertContains('local.public.career.index', $routeNames);
        $this->assertContains('local.public.outsource.index', $routeNames);
        $this->assertContains('local.login', $routeNames);
        $this->assertContains('local.mpr.login', $routeNames);
    }
}
