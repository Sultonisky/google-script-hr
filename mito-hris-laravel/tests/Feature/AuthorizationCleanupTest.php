<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function middleware(string $routeName): array
    {
        return Route::getRoutes()->getByName($routeName)->gatherMiddleware();
    }

    #[Test]
    public function candidate_export_uses_recruitment_permission_instead_of_role_middleware(): void
    {
        $middleware = $this->middleware('hr.export.candidates-csv');

        $this->assertContains('can:manage_recruitment', $middleware);
        $this->assertNotContains('role:Super Admin,Admin,User', $middleware);
    }

    #[Test]
    public function certificate_attachment_requires_portal_access_and_certificate_view(): void
    {
        $middleware = $this->middleware('certificates.portal.attachment');

        $this->assertContains('can:access_certificates_portal', $middleware);
        $this->assertContains('can:certificates.view', $middleware);
    }

    #[Test]
    public function dedicated_asset_mutations_use_their_action_permissions(): void
    {
        $expected = [
            'assets.portal.store'        => 'can:assets.create',
            'assets.portal.update'       => 'can:assets.update',
            'assets.portal.destroy'      => 'can:assets.delete',
            'assets.portal.assign'       => 'can:assets.assign',
            'assets.portal.return'       => 'can:assets.return',
            'assets.portal.generate-code' => 'can:assets.generate_code',
        ];

        foreach ($expected as $routeName => $permission) {
            $this->assertContains($permission, $this->middleware($routeName));
        }
    }

    #[Test]
    public function dedicated_certificate_mutations_use_their_action_permissions(): void
    {
        $expected = [
            'certificates.portal.store'        => 'can:certificates.create',
            'certificates.portal.update'       => 'can:certificates.update',
            'certificates.portal.destroy'      => 'can:certificates.delete',
            'certificates.portal.generate-code' => 'can:certificates.generate_code',
        ];

        foreach ($expected as $routeName => $permission) {
            $this->assertContains($permission, $this->middleware($routeName));
        }
    }

    #[Test]
    public function refresh_data_requires_hris_authentication_without_granular_permission(): void
    {
        $middleware = $this->middleware('hr.refresh-data');

        $this->assertContains('hr.auth', $middleware);
        $this->assertNotContains('can:manage_settings', $middleware);
        $this->assertNotContains('can:manage_recruitment', $middleware);
    }
}