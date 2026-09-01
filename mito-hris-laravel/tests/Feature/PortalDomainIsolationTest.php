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
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Super Admin',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/mpr/request');

        $response->assertRedirect(route('hr.dashboard'));
    }

    public function test_mpr_user_cannot_access_hris_portal(): void
    {
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email' => 'manager@mito.id',
            'role' => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal' => 'mpr',
        ]);

        $response = $this->get('http://hrismitogroup.web.id/hr/dashboard');

        $response->assertRedirect(route('mpr.auth.request'));
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
