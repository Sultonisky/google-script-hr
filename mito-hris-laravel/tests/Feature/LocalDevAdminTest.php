<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Local development test-user RBAC.
 *
 * test@example.com is the local Admin/full-access account produced by
 * `php artisan migrate:fresh --seed`. These tests lock that contract:
 *   1. DatabaseSeeder creates test@example.com with role=Admin.
 *   2. GA_IT / LEGAL dummy users are still produced by LocalDevUsersSeeder.
 *   3. Admin can perform the admin-only Asset (edit_asset) and Certification
 *      (manage_certification) actions — not merely view the index pages.
 */
class LocalDevAdminTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'test@example.com';
    private const GA_IT_EMAIL = 'ga.it@mitogroup.local';
    private const LEGAL_EMAIL = 'legal@mitogroup.local';

    private function actingAsRole(string $role, string $email): static
    {
        Session::put('hr_user', [
            'email'       => $email,
            'fullName'    => $role . ' User',
            'role'        => $role,
            'permissions' => config('hris.auth.role_permissions')[$role] ?? [],
            'auth_domain' => 'users',
            'entities'    => [],
            'branch'      => '',
        ]);

        return $this;
    }

    #[Test]
    public function migrate_fresh_seed_creates_expected_local_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => self::ADMIN_EMAIL, 'role' => 'Admin', 'status' => 'Active',
        ]);
        $this->assertDatabaseHas('users', ['email' => self::GA_IT_EMAIL, 'role' => 'GA_IT']);
        $this->assertDatabaseHas('users', ['email' => self::LEGAL_EMAIL, 'role' => 'LEGAL']);
    }

    #[Test]
    public function admin_can_access_admin_only_asset_and_certification_actions(): void
    {
        $this->actingAsRole('Admin', self::ADMIN_EMAIL);

        // Admin-only gates (route middleware can:edit_asset / can:manage_certification).
        $this->assertTrue(Gate::allows('view_asset'));
        $this->assertTrue(Gate::allows('edit_asset'));
        $this->assertTrue(Gate::allows('view_certification'));
        $this->assertTrue(Gate::allows('manage_certification'));

        // Route-level: admin-only write actions must pass RBAC (not 403).
        // 422/200 both acceptable — only a 403 means RBAC denial.
        $assetStatus = $this->post('/hr/assets', [
            'category' => 'Elektronik', 'name' => 'Admin RBAC Probe',
        ])->status();
        $this->assertNotSame(403, $assetStatus, 'Admin must pass edit_asset on asset store');

        $certStatus = $this->post('/hr/certifications', [])->status();
        $this->assertNotSame(403, $certStatus, 'Admin must pass manage_certification on certification store');
    }
}
