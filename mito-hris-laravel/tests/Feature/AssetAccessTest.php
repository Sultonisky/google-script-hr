<?php

namespace Tests\Feature;

use App\Http\Requests\HR\AssignAssetRequest;
use App\Http\Requests\HR\StoreAssetRequest;
use App\Http\Requests\HR\UpdateAssetRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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
        Session::put('hr_user', $this->makeSessionUser($role));
        return $this;
    }

    /**
     * FormRequest authorize() must return true.
     *
     * NOTE: We instantiate with `new` instead of `app()` to avoid
     * triggering ValidatesWhenResolvedTrait which runs validation
     * on an empty request — causing a ValidationException.
     */
    #[Test]
    public function form_requests_are_authorized(): void
    {
        $this->assertTrue((new StoreAssetRequest())->authorize());
        $this->assertTrue((new UpdateAssetRequest())->authorize());
        $this->assertTrue((new AssignAssetRequest())->authorize());
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
    public function user_without_edit_asset_cannot_create_asset(): void
    {
        $this->withSession(['_token' => 'test-token'])
            ->actingAsRole('User')
            ->postJson('/hr/assets', ['category' => 'Elektronik', 'name' => 'Laptop'], ['X-CSRF-TOKEN' => 'test-token', 'X-XSRF-TOKEN' => 'test-token'])
            ->assertForbidden();

        $this->assertDatabaseCount('assets', 0);
    }
}
