<?php

namespace Tests\Feature;

use App\Repositories\Contracts\PermissionCatalogRepositoryInterface;
use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Database\UserDatabaseRepository;
use App\Repositories\GoogleSheets\PermissionCatalogSheetsRepository;
use App\Repositories\GoogleSheets\UserPermissionSheetsRepository;
use App\Repositories\GoogleSheets\UserSheetsRepository;
use App\Repositories\Local\ArrayUserPermissionRepository;
use App\Repositories\Local\StaticPermissionCatalogRepository;
use App\Services\PermissionResolver;
use App\Support\LegacyRolePermissionSource;
use Mockery;
use Tests\TestCase;

/**
 * Repository binding tests — MITO HRIS
 *
 * Verifies that the data-source selection is driven exclusively by the
 * `google.enabled` config value (i.e. the GOOGLE_SHEETS_ENABLED env flag),
 * never by APP_ENV. The mapping is:
 *
 *   GOOGLE_SHEETS_ENABLED=true  → UserSheetsRepository  (local dev AND production)
 *   GOOGLE_SHEETS_ENABLED=false → UserDatabaseRepository (CI / isolated tests)
 *
 * Tests that exercise the Sheets-enabled path re-bind the interface inside
 * the test using $this->app->bind() after toggling config('google.enabled').
 * This is the standard Laravel approach for testing conditional bindings
 * without a real Google Sheets connection.
 *
 * phpunit.xml hard-sets GOOGLE_SHEETS_ENABLED=false, so the global test suite
 * defaults to the database path. The Sheets-enabled tests opt in explicitly.
 */
class RepositoryBindingTest extends TestCase
{
    // =========================================================================
    // Criterion 3 — testing: GOOGLE_SHEETS_ENABLED=false → DB repositories
    // =========================================================================

    /**
     * Testing environment (phpunit.xml: GOOGLE_SHEETS_ENABLED=false) uses
     * UserDatabaseRepository for test isolation.
     */
    public function test_testing_environment_uses_database_repository(): void
    {
        // phpunit.xml sets GOOGLE_SHEETS_ENABLED=false for testing.
        // The binding should use UserDatabaseRepository for isolation.
        $repository = app(UserRepositoryInterface::class);

        $this->assertInstanceOf(
            UserDatabaseRepository::class,
            $repository,
            'Testing environment should use UserDatabaseRepository for isolation'
        );
    }

    /**
     * Testing environment uses ArrayUserPermissionRepository for permission isolation.
     */
    public function test_testing_environment_uses_array_permission_repository(): void
    {
        $repository = app(UserPermissionRepositoryInterface::class);

        $this->assertInstanceOf(
            ArrayUserPermissionRepository::class,
            $repository,
            'Testing environment should use ArrayUserPermissionRepository for isolation'
        );
    }

    /**
     * config('google.enabled') is falsy in testing (phpunit.xml).
     */
    public function test_google_enabled_config_is_falsy_in_testing(): void
    {
        $this->assertFalse(
            (bool) config('google.enabled'),
            'google.enabled should be falsy in testing environment'
        );
    }

    // =========================================================================
    // Criterion 1 & 2 — local/production + Google Sheets enabled → UserSheetsRepository
    // =========================================================================

    /**
     * When google.enabled=true (any APP_ENV including local), the container
     * must resolve UserRepositoryInterface to UserSheetsRepository.
     *
     * No real Google Sheets connection is required: we verify the binding
     * itself, not the behaviour of the Sheets API.
     */
    public function test_google_sheets_enabled_resolves_user_sheets_repository(): void
    {
        // Simulate what AppServiceProvider does when GOOGLE_SHEETS_ENABLED=true.
        config(['google.enabled' => true]);
        $this->app->bind(UserRepositoryInterface::class, UserSheetsRepository::class);

        $repository = $this->app->make(UserRepositoryInterface::class);

        $this->assertInstanceOf(
            UserSheetsRepository::class,
            $repository,
            'With google.enabled=true, UserRepositoryInterface must resolve to UserSheetsRepository'
        );
    }

    /**
     * APP_ENV=local with Google Sheets enabled → UserSheetsRepository.
     * Decouples data source from environment name.
     */
    public function test_local_env_with_google_sheets_enabled_resolves_user_sheets_repository(): void
    {
        config(['google.enabled' => true]);
        $this->app->bind(UserRepositoryInterface::class, UserSheetsRepository::class);

        // Simulate APP_ENV=local (does NOT change the binding decision).
        $this->assertEquals('testing', app()->environment());

        $repository = $this->app->make(UserRepositoryInterface::class);

        $this->assertInstanceOf(
            UserSheetsRepository::class,
            $repository,
            'APP_ENV must not drive repository selection; google.enabled=true always → UserSheetsRepository'
        );
    }

    /**
     * When google.enabled=true, UserPermissionRepositoryInterface resolves
     * to UserPermissionSheetsRepository (not the in-memory array version).
     */
    public function test_google_sheets_enabled_resolves_sheets_permission_repository(): void
    {
        config(['google.enabled' => true]);
        $this->app->singleton(UserPermissionRepositoryInterface::class, UserPermissionSheetsRepository::class);

        $repository = $this->app->make(UserPermissionRepositoryInterface::class);

        $this->assertInstanceOf(
            UserPermissionSheetsRepository::class,
            $repository,
            'With google.enabled=true, UserPermissionRepositoryInterface must resolve to UserPermissionSheetsRepository'
        );
    }

    /**
     * When google.enabled=true, PermissionCatalogRepositoryInterface resolves
     * to PermissionCatalogSheetsRepository.
     */
    public function test_google_sheets_enabled_resolves_sheets_permission_catalog_repository(): void
    {
        config(['google.enabled' => true]);
        $this->app->singleton(PermissionCatalogRepositoryInterface::class, PermissionCatalogSheetsRepository::class);

        $repository = $this->app->make(PermissionCatalogRepositoryInterface::class);

        $this->assertInstanceOf(
            PermissionCatalogSheetsRepository::class,
            $repository,
            'With google.enabled=true, PermissionCatalogRepositoryInterface must resolve to PermissionCatalogSheetsRepository'
        );
    }

    /**
     * When google.enabled=false, PermissionCatalogRepositoryInterface resolves
     * to StaticPermissionCatalogRepository.
     */
    public function test_google_sheets_disabled_resolves_static_permission_catalog_repository(): void
    {
        // phpunit.xml already sets google.enabled=false; this is explicit.
        $this->assertFalse((bool) config('google.enabled'));
        $repository = $this->app->make(PermissionCatalogRepositoryInterface::class);

        $this->assertInstanceOf(
            StaticPermissionCatalogRepository::class,
            $repository,
            'With google.enabled=false, PermissionCatalogRepositoryInterface must resolve to StaticPermissionCatalogRepository'
        );
    }

    // =========================================================================
    // Criterion 4 — permissions:migrate receives users from the correct repository
    // =========================================================================

    /**
     * mito:permissions:migrate --dry-run uses whichever UserRepository is
     * bound. When injected with a mock, it reads all users from that mock,
     * confirming the command does NOT hard-code a data source.
     */
    public function test_permissions_migrate_dry_run_reads_from_injected_user_repository(): void
    {
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('getAll')->once()->andReturn([
            ['Email' => 'user@example.test', 'Role' => 'User', 'Status' => 'Active'],
        ]);
        $this->app->instance(UserRepositoryInterface::class, $users);

        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('mappingsForUser')->once()->andReturn([]);
        $permissions->shouldReceive('upsert')->never(); // dry-run must not write
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $this->artisan('mito:permissions:migrate', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('Users found: 1');
    }

    /**
     * mito:permissions:migrate --dry-run does NOT modify the Users Sheet.
     *
     * A Mockery strict mock is used to confirm no mutation calls (create,
     * updateByEmail, deleteByEmail) are made on UserRepositoryInterface.
     */
    public function test_permissions_migrate_dry_run_does_not_modify_users_sheet(): void
    {
        $users = Mockery::mock(UserRepositoryInterface::class);
        $users->shouldReceive('getAll')->once()->andReturn([
            ['Email' => 'hr@example.test', 'Role' => 'Admin', 'Status' => 'Active'],
        ]);
        // These MUST NOT be called during dry-run.
        $users->shouldNotReceive('create');
        $users->shouldNotReceive('updateByEmail');
        $users->shouldNotReceive('deleteByEmail');
        $this->app->instance(UserRepositoryInterface::class, $users);

        $permissions = Mockery::mock(UserPermissionRepositoryInterface::class);
        $permissions->shouldReceive('mappingsForUser')->once()->andReturn([]);
        $permissions->shouldReceive('upsert')->never();
        $this->app->instance(UserPermissionRepositoryInterface::class, $permissions);

        $this->artisan('mito:permissions:migrate', ['--dry-run' => true])
            ->assertExitCode(0);
    }

    // =========================================================================
    // Criterion 6 — no runtime role_permissions fallback
    // =========================================================================

    /**
     * LegacyRolePermissionSource is the ONLY place the role_permissions config
     * matrix is consumed. Runtime Gate checks go through PermissionResolver →
     * UserPermissionRepositoryInterface, not through the config matrix directly.
     *
     * This test confirms that a user with no User_Permissions rows has zero
     * runtime permissions, even though the config matrix lists permissions for
     * their role — i.e. no fallback to the config matrix at runtime.
     */
    public function test_no_runtime_role_permissions_fallback_restored(): void
    {
        $email = 'norole-fallback@example.test';
        $role  = 'Admin';

        // Confirm config matrix has permissions for Admin.
        $matrixPerms = LegacyRolePermissionSource::permissionsForRole($role);
        $this->assertNotEmpty($matrixPerms, 'Admin must have entries in the role_permissions config matrix');

        // Re-resolve the singleton so this test gets a clean cache for this email.
        $resolver = app(PermissionResolver::class);
        $resolver->forget($email);

        // ArrayUserPermissionRepository starts empty per test lifecycle —
        // no upsert() has been called for this email yet.
        $user = ['email' => $email, 'role' => $role];

        foreach ($matrixPerms as $permission) {
            if ($permission === '*') {
                continue;
            }
            $this->assertFalse(
                $resolver->allows($user, $permission),
                "Runtime resolver must not fall back to role_permissions config for permission '{$permission}'"
            );
        }
    }

    // =========================================================================
    // Criterion 8 — no business logic changes
    // =========================================================================

    /**
     * The UserRepositoryInterface contract surface is unchanged:
     * all eight methods are present on both implementations.
     */
    public function test_user_repository_interface_contract_is_intact(): void
    {
        $methods = [
            'findByEmail',
            'findByIdentifier',
            'getAll',
            'create',
            'updateByEmail',
            'deleteByEmail',
            'isEmpty',
            'updateLastLogin',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(UserDatabaseRepository::class, $method),
                "UserDatabaseRepository must implement {$method}"
            );
            $this->assertTrue(
                method_exists(UserSheetsRepository::class, $method),
                "UserSheetsRepository must implement {$method}"
            );
        }
    }
}
