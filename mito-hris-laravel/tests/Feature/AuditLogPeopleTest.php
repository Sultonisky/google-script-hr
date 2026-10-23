<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Database\AuditLogDatabaseRepository;
use App\Support\AuditLogPeople;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuditLogPeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_names_the_employee_and_the_hr_user_when_both_exist(): void
    {
        Employee::query()->create([
            'employee_id' => 'EMP-VINA',
            'full_name' => 'Vina Anggraeni',
            'status_employee' => 'Contract',
        ]);
        Candidate::query()->create([
            'recruitment_id' => 'REC-BUDI',
            'lifecycle_status' => 'pending',
            'full_name' => 'Budi Santoso',
        ]);
        User::query()->create([
            'name' => 'Siti HR',
            'email' => 'siti@mito.co.id',
            'password' => 'secret',
        ]);

        $labels = AuditLogPeople::forLogs([
            [
                'Audit ID' => 'AUD-1',
                'Entity Type' => 'Employee',
                'Entity ID' => 'EMP-VINA',
                'User' => 'siti@mito.co.id',
            ],
            [
                'Audit ID' => 'AUD-2',
                'Entity Type' => 'Candidate',
                'Entity ID' => 'REC-BUDI',
                'User' => 'System',
            ],
            [
                'Audit ID' => 'AUD-3',
                'Entity Type' => 'Authentication',
                'Entity ID' => 'siti@mito.co.id',
                'User' => 'siti@mito.co.id',
            ],
            [
                'Audit ID' => 'AUD-4',
                'Entity Type' => 'Employee',
                'Entity ID' => 'EMP-MISSING',
                'User' => 'nobody@mito.co.id',
            ],
        ]);

        $this->assertSame('Vina Anggraeni', $labels['AUD-1']['subject']);
        $this->assertSame('Siti HR', $labels['AUD-1']['actor']);
        $this->assertSame('Budi Santoso', $labels['AUD-2']['subject']);
        $this->assertNull($labels['AUD-2']['actor']);
        $this->assertSame('Siti HR', $labels['AUD-3']['subject']);
        $this->assertNull($labels['AUD-3']['actor']);
        $this->assertNull($labels['AUD-4']['subject']);
        $this->assertNull($labels['AUD-4']['actor']);
    }

    public function test_audit_detail_payload_includes_the_employee_name(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogDatabaseRepository::class);
        Employee::query()->create([
            'employee_id' => 'EMP-VINA',
            'full_name' => 'Vina Anggraeni',
            'status_employee' => 'Contract',
        ]);
        User::query()->create([
            'name' => 'Siti HR',
            'email' => 'siti@mito.co.id',
            'password' => 'secret',
        ]);
        AuditLog::query()->create([
            'audit_id' => 'AUD-000900',
            'entity_type' => 'Employee',
            'entity_id' => 'EMP-VINA',
            'action' => 'updated',
            'user' => 'siti@mito.co.id',
            'source' => 'Dashboard',
            'logged_at' => now(),
        ]);

        Session::put('hr_user', [
            'email' => 'super.admin@mito.id',
            'fullName' => 'Super Admin User',
            'role' => 'Super Admin',
            'permissions' => config('hris.auth.role_permissions')['Super Admin'] ?? [],
            'auth_domain' => 'users',
            'entities' => [],
            'branch' => '',
        ]);

        $html = $this->get('/hr/audit-logs')->assertOk()->getContent();
        $this->assertStringContainsString('audit-detail-list', $html);
        $this->assertStringContainsString('modal-dialog-scrollable', $html);
        $this->assertStringContainsString('Vina Anggraeni', $html);
        $this->assertStringContainsString('Siti HR', $html);
        $this->assertStringContainsString('Untuk', $html);
        $this->assertStringContainsString('Oleh', $html);
    }
}
