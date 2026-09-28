<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\UserPermission;
use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Database\PermissionCatalogDatabaseRepository;
use App\Repositories\Database\UserPermissionDatabaseRepository;
use App\Services\PermissionResolver;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permissions + User_Permissions Eloquent repos (HRIS_DATA_DRIVER=pgsql).
 */
class PermissionDatabaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(PermissionCatalogRepositoryInterface::class, PermissionCatalogDatabaseRepository::class);
        $this->app->singleton(UserPermissionRepositoryInterface::class, UserPermissionDatabaseRepository::class);
        $this->app->forgetInstance(PermissionResolver::class);
    }

    public function test_catalog_falls_back_to_static_when_empty_then_syncs(): void
    {
        /** @var PermissionCatalogDatabaseRepository $catalog */
        $catalog = $this->app->make(PermissionCatalogRepositoryInterface::class);

        $fallback = $catalog->all();
        $this->assertNotEmpty($fallback);
        $this->assertArrayHasKey('key', $fallback[0]);

        $inserted = $catalog->syncFromStaticCatalog();
        $this->assertSame(count(PermissionCatalog::all()), $inserted);
        $this->assertSame(0, $catalog->syncFromStaticCatalog());

        $rows = $catalog->all();
        $this->assertArrayHasKey('Permission Key', $rows[0]);
        $this->assertSame(count(PermissionCatalog::all()), Permission::count());
        $this->assertTrue(collect($rows)->every(fn ($r) => strtolower($r['Status']) === 'active'));
    }

    public function test_user_permission_upsert_and_resolver(): void
    {
        /** @var UserPermissionRepositoryInterface $perms */
        $perms = $this->app->make(UserPermissionRepositoryInterface::class);

        $this->assertTrue($perms->upsert('hr@mito.id', 'view_employees', true, 'admin@mito.id'));
        $this->assertTrue($perms->upsert('hr@mito.id', 'manage_employees', false, 'admin@mito.id'));

        $mappings = $perms->mappingsForUser('HR@mito.id');
        $this->assertCount(2, $mappings);
        $this->assertSame('TRUE', collect($mappings)->firstWhere('Permission Key', 'view_employees')['Granted']);
        $this->assertSame('FALSE', collect($mappings)->firstWhere('Permission Key', 'manage_employees')['Granted']);

        $this->assertTrue($perms->upsert('hr@mito.id', 'view_employees', false, 'admin@mito.id'));
        $this->assertSame(1, UserPermission::where('user_email', 'hr@mito.id')->where('permission_key', 'view_employees')->count());

        /** @var PermissionResolver $resolver */
        $resolver = $this->app->make(PermissionResolver::class);
        $user = ['email' => 'hr@mito.id', 'role' => 'User', 'auth_domain' => 'users'];
        $this->assertFalse($resolver->allows($user, 'view_employees'));
        $this->assertFalse($resolver->allows($user, 'manage_employees'));

        $perms->upsert('hr@mito.id', 'view_employees', true, 'admin@mito.id');
        $resolver = $this->app->make(PermissionResolver::class); // new instance clears cache via forget - need clearCache if exists
        // PermissionResolver caches per instance; resolve fresh
        $this->app->forgetInstance(PermissionResolver::class);
        $resolver = $this->app->make(PermissionResolver::class);
        $this->assertTrue($resolver->allows($user, 'view_employees'));
    }

    public function test_pgsql_driver_binds_permission_database_repositories(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => 'pgsql']);
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(
            UserPermissionDatabaseRepository::class,
            $this->app->make(UserPermissionRepositoryInterface::class)
        );
        $this->assertInstanceOf(
            PermissionCatalogDatabaseRepository::class,
            $this->app->make(PermissionCatalogRepositoryInterface::class)
        );
    }
}
