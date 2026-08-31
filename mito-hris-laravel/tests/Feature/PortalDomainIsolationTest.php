<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
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

    public function test_hris_domain_root_redirects_anonymous_to_login_and_not_recruitment(): void
    {
        $response = $this->get('http://hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_hris_domain_root_redirects_authenticated_user_to_dashboard(): void
    {
        Session::put('hr_user', [
            'email' => 'admin@mito.id',
            'role' => 'Super Admin',
            'auth_domain' => 'users',
            'portal' => 'hris',
        ]);

        $response = $this->get('http://hrismitogroup.web.id/');

        $response->assertRedirect(route('hr.dashboard'));
    }

    public function test_mpr_domain_root_redirects_anonymous_to_mpr_login_and_not_recruitment(): void
    {
        $response = $this->get('http://mpr.hrismitogroup.web.id/');

        $response->assertOk();
        $response->assertViewIs('auth.mpr-auth');
    }

    public function test_mpr_domain_root_redirects_authenticated_requestor_to_request_flow(): void
    {
        Session::put(config('mpr.session_key', 'mpr_requestor_auth'), [
            'email' => 'manager@mito.id',
            'fullName' => 'John Manager',
            'role' => 'Manpower',
            'auth_domain' => 'mpr_requestor',
            'portal' => 'mpr',
        ]);

        $response = $this->get('http://mpr.hrismitogroup.web.id/');

        $response->assertRedirect(route('mpr.auth.request'));
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
}
