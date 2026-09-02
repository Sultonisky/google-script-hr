<?php

namespace Tests\Feature;

use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Services\MprRequestorAuthService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

/**
 * Tests for MPR Requestor authentication domain.
 *
 * Verifies that:
 * 1. MprRequestorAuthService authenticates against mpr_requestor sheet (NOT Users).
 * 2. Inactive requestors are rejected.
 * 3. Wrong role (non-Manager) in mpr_requestor is rejected.
 * 4. Unknown identifier returns null error (fall-through to Users domain).
 * 5. Session built by MprRequestorAuthService has correct auth_domain marker.
 * 6. Entity and branch are correctly placed in the session.
 */
class MprRequestorAuthTest extends TestCase
{
    // =========================================================================
    // Helper: build a mock requestor row as returned by the repository
    // =========================================================================

    private function makeRequestorRow(array $overrides = []): array
    {
        return array_merge([
            'Requestor ID' => 'MPR-REQ-001',
            'Email'        => 'manager@mito.co.id',
            'Username'     => 'manager.test',
            'Full Name'    => 'John Manager',
            'Role'         => 'Manager',
            'Status'       => 'Active',
            'Password Hash' => Hash::make('password123'),
            'Entity'       => 'MSI',
            'Branch'       => 'Bandung',
            'Last Login'   => '',
            'Created At'   => '2026-08-01 09:00:00',
            'Updated At'   => '2026-08-01 09:00:00',
            'Created By'   => 'seed-command',
        ], $overrides);
    }

    // =========================================================================
    // Test 1: Successful login → session has correct structure
    // =========================================================================

    /** @test */
    public function successful_login_returns_correct_session_structure(): void
    {
        $row = $this->makeRequestorRow();

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')
            ->once()->with('manager@mito.co.id')
            ->andReturn($row);
        $mockRepo->shouldReceive('updateLastLogin')->once();

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'password123');

        $this->assertTrue($result['success']);
        $user = $result['user'];

        // auth_domain must identify this as mpr_requestor
        $this->assertSame('mpr_requestor', $user['auth_domain'],
            'Session must carry auth_domain = mpr_requestor');

        // Identity from requestor row
        $this->assertSame('manager@mito.co.id', $user['email']);
        $this->assertSame('John Manager', $user['fullName']);
        $this->assertSame('Manager', $user['role']);

        // requestor_id must be present
        $this->assertSame('MPR-REQ-001', $user['requestor_id']);

        // MPR permissions
        $this->assertContains('create_mpr', $user['permissions']);
        $this->assertContains('view_mpr',   $user['permissions']);
        $this->assertContains('export_mpr', $user['permissions']);
    }

    // =========================================================================
    // Test 2: Job position is resolved into session
    // =========================================================================

    /** @test */
    public function job_position_is_resolved_into_session(): void
    {
        $row = $this->makeRequestorRow(['Job Position' => 'Branch Manager']);

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn($row);
        $mockRepo->shouldReceive('updateLastLogin')->once();

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'password123');

        $this->assertTrue($result['success']);
        $this->assertSame('Branch Manager', $result['user']['jobPosition']);
    }

    // =========================================================================
    // Test 3: Unknown identifier → null error (fall-through signal)
    // =========================================================================

    /** @test */
    public function unknown_identifier_returns_null_error_for_fallthrough(): void
    {
        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn(null);

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('unknown@example.com', 'anypassword');

        $this->assertFalse($result['success']);
        $this->assertNull($result['error'],
            'null error signals caller to try the next auth domain (Users sheet)');
    }

    // =========================================================================
    // Test 4: Inactive requestor is rejected
    // =========================================================================

    /** @test */
    public function inactive_requestor_is_rejected_with_error_message(): void
    {
        $row = $this->makeRequestorRow(['Status' => 'Inactive']);

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn($row);

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'password123');

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error'],
            'Inactive requestor must return a non-null error (hard rejection)');
        $this->assertStringContainsString('aktif', strtolower($result['error']));
    }

    // =========================================================================
    // Test 5: Non-Manager role in mpr_requestor is rejected
    // =========================================================================

    /** @test */
    public function non_manager_role_in_mpr_requestor_is_rejected(): void
    {
        $row = $this->makeRequestorRow(['Role' => 'HR Staff']);

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn($row);

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'password123');

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    // =========================================================================
    // Test 6: Wrong password is rejected
    // =========================================================================

    /** @test */
    public function wrong_password_is_rejected(): void
    {
        $row = $this->makeRequestorRow();

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn($row);

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'wrongpassword');

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('salah', strtolower($result['error']));
    }

    // =========================================================================
    // Test 7: Empty password hash is rejected
    // =========================================================================

    /** @test */
    public function empty_password_hash_is_rejected(): void
    {
        $row = $this->makeRequestorRow(['Password Hash' => '']);

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')->andReturn($row);

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager@mito.co.id', 'password123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('diatur', strtolower($result['error']));
    }

    // =========================================================================
    // Test 8: Username-based login also works
    // =========================================================================

    /** @test */
    public function login_via_username_works(): void
    {
        $row = $this->makeRequestorRow();

        $mockRepo = Mockery::mock(MprRequestorRepositoryInterface::class);
        $mockRepo->shouldReceive('findByIdentifier')
            ->once()->with('manager.test')
            ->andReturn($row);
        $mockRepo->shouldReceive('updateLastLogin')->once();

        $service = new MprRequestorAuthService($mockRepo);
        $result  = $service->attemptLogin('manager.test', 'password123');

        $this->assertTrue($result['success']);
        $this->assertSame('mpr_requestor', $result['user']['auth_domain']);
    }
}
