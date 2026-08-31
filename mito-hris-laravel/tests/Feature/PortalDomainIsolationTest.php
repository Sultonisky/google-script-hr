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
}
