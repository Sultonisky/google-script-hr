<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssetAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeSessionUser(string $role): array
    {
        return [
            'email'       => strtolower(str_replace(' ', '.', $role)) . '@mito.id',
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ];
    }

    private function actingAsRole(string $role): static
    {
        Session::put('hr_user', $this->migratedTestUser($this->makeSessionUser($role)));
        return $this;
    }

    #[Test]
    public function rbac_gates_for_asset_permissions(): void
    {
        $this->actingAsRole('Admin');
        $this->assertTrue(Gate::allows('view_asset'));
        $this->assertTrue(Gate::allows('edit_asset'));

        $this->actingAsRole('User');
        $this->assertTrue(Gate::allows('view_asset'));
        $this->assertFalse(Gate::allows('edit_asset'));

        $this->actingAsRole('Super Admin');
        $this->assertTrue(Gate::allows('view_asset'));
        $this->assertTrue(Gate::allows('edit_asset'));
    }

    #[Test]
    public function category_asset_routes_replace_the_legacy_asset_routes(): void
    {
        $this->assertTrue(Route::has('assets.portal.building.index'));
        $this->assertTrue(Route::has('assets.portal.vehicle.index'));
        $this->assertTrue(Route::has('assets.portal.office.index'));
        $this->assertTrue(Route::has('assets.portal.electronics.index'));
        $this->assertFalse(Route::has('hr.assets.index'));
    }
}
