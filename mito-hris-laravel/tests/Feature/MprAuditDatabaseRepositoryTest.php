<?php

namespace Tests\Feature;

use App\DTOs\MprData;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\MprRepositoryInterface;
use App\Repositories\Contracts\MprRequestorRepositoryInterface;
use App\Repositories\Database\AuditLogDatabaseRepository;
use App\Repositories\Database\MprDatabaseRepository;
use App\Repositories\Database\MprRequestorDatabaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit + MPR Eloquent repos (HRIS_DATA_DRIVER=pgsql). No Sheets writes.
 */
class MprAuditDatabaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogDatabaseRepository::class);
        $this->app->bind(MprRepositoryInterface::class, MprDatabaseRepository::class);
        $this->app->bind(MprRequestorRepositoryInterface::class, MprRequestorDatabaseRepository::class);
    }

    public function test_audit_log_write_and_filter_by_entity(): void
    {
        /** @var AuditLogRepositoryInterface $audit */
        $audit = $this->app->make(AuditLogRepositoryInterface::class);

        $this->assertTrue($audit->log('Employee', 'EMP-1', 'Created', 'Status', null, 'Contract', 'HR Admin'));
        $this->assertTrue($audit->log('Employee', 'EMP-2', 'Updated', 'Status', 'Contract', 'PKWTT', 'HR Admin'));

        $all = $audit->getLogs();
        $this->assertCount(2, $all);
        $this->assertSame('AUD-000002', $all->first()['Audit ID']);

        $filtered = $audit->getLogs('EMP-1');
        $this->assertCount(1, $filtered);
        $this->assertSame('EMP-1', $filtered->first()['Entity ID']);
        $this->assertSame('Created', $filtered->first()['Action']);
    }

    public function test_mpr_create_find_update_and_filters(): void
    {
        /** @var MprRepositoryInterface $mprs */
        $mprs = $this->app->make(MprRepositoryInterface::class);

        $created = $mprs->create(new MprData(
            requestorName: 'Manager A',
            requestorEmail: 'mgr@mito.id',
            entity: 'MSI',
            department: 'IT',
            division: 'Software Development',
            position: 'Backend Engineer',
            quantity: 2,
            status: 'Submitted',
            createdBy: 'mgr@mito.id',
            skillsCompetencies: 'PHP, Laravel',
            specialNotes: 'Urgent',
        ));

        $this->assertNotEmpty($created->mprNumber);
        $this->assertSame('Urgent', $created->specialNotes);
        $this->assertSame('PHP, Laravel', $created->skillsCompetencies);

        $found = $mprs->findByMprNumber($created->mprNumber);
        $this->assertSame('Backend Engineer', $found?->position);

        $created->status = 'Approved';
        $created->position = 'Senior Backend Engineer';
        $updated = $mprs->update($created->mprNumber, $created);
        $this->assertSame('Approved', $updated->status);
        $this->assertSame('Senior Backend Engineer', $updated->position);

        $this->assertCount(1, $mprs->getAll(['department' => 'IT']));
        $this->assertCount(1, $mprs->getAll(['search' => 'senior']));
        $this->assertCount(1, $mprs->getAllForManager('mgr@mito.id'));
        $this->assertCount(0, $mprs->getAllForManager('other@mito.id'));
    }

    public function test_mpr_requestor_crud_and_id_sequence(): void
    {
        /** @var MprRequestorRepositoryInterface $repo */
        $repo = $this->app->make(MprRequestorRepositoryInterface::class);

        $this->assertTrue($repo->isEmpty());
        $this->assertSame('MPR-REQ-001', $repo->generateNextId());

        $repo->create([
            'email' => 'mgr@mito.id',
            'username' => 'mgr',
            'fullName' => 'Manager A',
            'jobPosition' => 'IT Manager',
            'passwordHash' => 'hash',
        ]);

        $this->assertFalse($repo->isEmpty());
        $found = $repo->findByEmail('MGR@mito.id');
        $this->assertSame('Manager A', $found['Full Name'] ?? null);
        $this->assertSame('MPR-REQ-001', $found['Requestor ID'] ?? null);

        $repo->updateByEmail('mgr@mito.id', ['fullName' => 'Manager Updated', 'status' => 'Active']);
        $this->assertSame('Manager Updated', $repo->findByEmail('mgr@mito.id')['Full Name'] ?? null);

        $repo->updateLastLogin('mgr@mito.id');
        $this->assertNotEmpty($repo->findByEmail('mgr@mito.id')['Last Login'] ?? '');

        $this->assertSame('MPR-REQ-002', $repo->generateNextId());

        $repo->deleteByEmail('mgr@mito.id');
        $this->assertSame('Inactive', $repo->findByEmail('mgr@mito.id')['Status'] ?? null);
    }

    public function test_pgsql_driver_binds_mpr_and_audit_database_repositories(): void
    {
        config(['google.enabled' => false, 'hris.data_driver' => 'pgsql']);
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(AuditLogDatabaseRepository::class, $this->app->make(AuditLogRepositoryInterface::class));
        $this->assertInstanceOf(MprDatabaseRepository::class, $this->app->make(MprRepositoryInterface::class));
        $this->assertInstanceOf(MprRequestorDatabaseRepository::class, $this->app->make(MprRequestorRepositoryInterface::class));
    }
}
