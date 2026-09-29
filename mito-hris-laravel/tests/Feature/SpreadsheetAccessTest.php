<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Services\PermissionResolver;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SpreadsheetAccessTest extends TestCase
{
    use RefreshDatabase;

    private const SPREADSHEET_ID = 'test-spreadsheet-archive-id';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'hris.data_driver' => 'pgsql',
            'google.enabled' => true,
            'google.spreadsheet_id' => self::SPREADSHEET_ID,
        ]);
        (new \App\Providers\AppServiceProvider($this->app))->register();
    }

    private function loginAs(string $role, string $email = 'user@mito.id'): void
    {
        Session::put('hr_user', $this->migratedTestUser([
            'email' => $email,
            'fullName' => $role.' User',
            'role' => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]));
    }

    private function setGrant(string $email, bool $granted): void
    {
        app(UserPermissionRepositoryInterface::class)->upsert($email, 'open_spreadsheet', $granted, 'test');
        app(PermissionResolver::class)->forget($email);
    }

    private function renderFab(): string
    {
        return view('hr.partials.floating-action-button')->render();
    }

    public function test_permission_is_registered_in_catalog(): void
    {
        $this->assertNotNull(PermissionCatalog::find('open_spreadsheet'));
    }

    public function test_super_admin_is_redirected_to_spreadsheet_and_audited(): void
    {
        $this->loginAs('Super Admin', 'root@mito.id');

        $this->get('/hr/spreadsheet/open')
            ->assertRedirect('https://docs.google.com/spreadsheets/d/'.self::SPREADSHEET_ID.'/edit');

        $this->assertSame(1, AuditLog::query()->where('action', 'Opened')->count());
    }

    public function test_admin_without_explicit_grant_is_forbidden(): void
    {
        $this->loginAs('Admin', 'admin@mito.id');

        $this->get('/hr/spreadsheet/open')->assertForbidden();
        $this->assertSame(0, AuditLog::query()->where('action', 'Opened')->count());
    }

    public function test_user_with_explicit_grant_can_open_spreadsheet(): void
    {
        $this->loginAs('User', 'staff@mito.id');
        $this->setGrant('staff@mito.id', true);

        $this->get('/hr/spreadsheet/open')
            ->assertRedirect('https://docs.google.com/spreadsheets/d/'.self::SPREADSHEET_ID.'/edit');
    }

    public function test_revoked_grant_is_forbidden(): void
    {
        $this->loginAs('Admin', 'admin@mito.id');
        $this->setGrant('admin@mito.id', false);

        $this->get('/hr/spreadsheet/open')->assertForbidden();
    }

    public function test_missing_spreadsheet_id_returns_not_found(): void
    {
        config(['google.spreadsheet_id' => '']);
        $this->loginAs('Super Admin', 'root@mito.id');

        $this->get('/hr/spreadsheet/open')->assertNotFound();
    }

    public function test_fab_hides_spreadsheet_link_and_id_without_permission(): void
    {
        $this->loginAs('Admin', 'admin@mito.id');

        $html = $this->renderFab();

        $this->assertStringNotContainsString('Buka Spreadsheet', $html);
        $this->assertStringNotContainsString(self::SPREADSHEET_ID, $html);
    }

    public function test_fab_shows_gated_link_without_exposing_id_when_granted(): void
    {
        $this->loginAs('User', 'staff@mito.id');
        $this->setGrant('staff@mito.id', true);

        $html = $this->renderFab();

        $this->assertStringContainsString('Buka Spreadsheet', $html);
        $this->assertStringContainsString('/hr/spreadsheet/open', $html);
        $this->assertStringNotContainsString(self::SPREADSHEET_ID, $html);
    }

    public function test_migration_adds_permission_to_populated_catalog_only(): void
    {
        $migration = require database_path('migrations/2026_09_29_000001_add_open_spreadsheet_permission.php');

        // Empty table: static catalog fallback already covers the key.
        Permission::query()->delete();
        $migration->up();
        $this->assertSame(0, Permission::query()->count());

        Permission::query()->create([
            'permission_key' => 'manage_settings',
            'name' => 'Manage settings',
            'group' => 'Settings',
            'status' => 'active',
        ]);

        $migration->up();
        $migration->up();

        $this->assertSame(1, Permission::query()->where('permission_key', 'open_spreadsheet')->count());
        $this->assertSame('active', Permission::query()->where('permission_key', 'open_spreadsheet')->value('status'));
    }
}
