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
    public function performance_review_export_follows_probation_permission(): void
    {
        $middleware = $this->middleware('hr.export.performance-review');

        $this->assertContains('can:manage_probation', $middleware);
        $this->assertNotContains('can:manage_employees', $middleware);
    }

    #[Test]
    public function certificate_attachment_requires_portal_access_and_certificate_view(): void
    {
        $middleware = $this->middleware('certificates.portal.attachment');

        $this->assertContains('can:access_certificates_portal', $middleware);
        $this->assertContains('can:certificates.view', $middleware);
    }

    #[Test]
    public function category_asset_mutations_use_their_action_permissions(): void
    {
        $expected = [
            'assets.portal.building.index' => 'can:assets.building.view',
            'assets.portal.building.store' => 'can:assets.building.create',
            'assets.portal.building.update' => 'can:assets.building.update',
            'assets.portal.building.destroy' => 'can:assets.building.delete',
            'assets.portal.vehicle.index' => 'can:assets.vehicle.view',
            'assets.portal.vehicle.store' => 'can:assets.vehicle.create',
            'assets.portal.office.store' => 'can:assets.office.create',
            'assets.portal.electronics.store' => 'can:assets.electronics.create',
            'assets.portal.electronics.assign' => 'can:assets.electronics.assign',
            'assets.portal.electronics.return' => 'can:assets.electronics.return',
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