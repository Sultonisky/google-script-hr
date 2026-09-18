<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Local\ArrayUserPermissionRepository;
use Mockery;
use Tests\TestCase;

class PermissionMigrationCommandTest extends TestCase
{
    public function test_dry_run_reports_without_writing_permissions(): void
    {
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('getAll')->once()->andReturn([
            ['Email' => 'admin@example.test', 'Role' => 'Admin', 'Status' => 'Active'],
        ]);
        $this->app->instance(UserRepositoryInterface::class, $users);

        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('mappingsForUser')->once()->with('admin@example.test')->andReturn([]);
        $permissions->shouldReceive('upsert')->never();
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $this->artisan('mito:permissions:migrate', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutput('Permission migration DRY-RUN.')
            ->expectsOutput('Mappings to create: 31');
    }

    public function test_migration_preserves_explicit_revoke_and_is_idempotent(): void
    {
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('getAll')->twice()->andReturn([
            ['Email' => 'admin@example.test', 'Role' => 'Admin', 'Status' => 'Active'],
        ]);
        $this->app->instance(UserRepositoryInterface::class, $users);

        $permissions = new ArrayUserPermissionRepository();
        $permissions->upsert('admin@example.test', 'assets.access', false, 'administrator');
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $this->artisan('mito:permissions:migrate')->assertExitCode(0);
        $firstRun = $permissions->mappingsForUser('admin@example.test');

        $this->artisan('mito:permissions:migrate')->assertExitCode(0);
        $secondRun = $permissions->mappingsForUser('admin@example.test');

        $this->assertCount(count($firstRun), $secondRun);
        $accessRows = array_values(array_filter(
            $secondRun,
            fn (array $row): bool => $row['Permission Key'] === 'assets.access'
        ));
        $this->assertCount(1, $accessRows);
        $this->assertFalse($accessRows[0]['Granted']);
    }
}
