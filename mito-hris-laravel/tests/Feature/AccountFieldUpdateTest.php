<?php

namespace Tests\Feature;

use App\Models\MprRequestor;
use App\Models\User;
use App\Models\UserPermission;
use App\Repositories\Database\MprRequestorDatabaseRepository;
use App\Repositories\Database\UserDatabaseRepository;
use App\Repositories\Database\UserPermissionDatabaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountFieldUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_user_update_persists_every_editable_field(): void
    {
        $repository = new UserDatabaseRepository();
        $repository->create([
            'email' => 'old.user@example.test',
            'username' => 'olduser',
            'fullName' => 'Old User',
            'role' => 'User',
            'status' => 'Active',
            'passwordHash' => Hash::make('secret-password'),
        ]);

        UserPermission::query()->create([
            'user_email' => 'old.user@example.test',
            'permission_key' => 'view_employees',
            'granted' => true,
            'granted_by' => 'test',
        ]);

        $repository->updateByEmail('old.user@example.test', [
            'email' => 'new.user@example.test',
            'username' => 'newuser',
            'fullName' => 'New User',
            'role' => 'Admin',
            'status' => 'Inactive',
            'passwordHash' => Hash::make('new-secret-password'),
        ]);
        (new UserPermissionDatabaseRepository())->reassignEmail('old.user@example.test', 'new.user@example.test');

        $this->assertNull($repository->findByEmail('old.user@example.test'));
        $updated = $repository->findByEmail('new.user@example.test');
        $this->assertNotNull($updated);
        $this->assertSame('new.user@example.test', $updated['Email']);
        $this->assertSame('newuser', $updated['Username']);
        $this->assertSame('New User', $updated['Full Name']);
        $this->assertSame('Admin', $updated['Role']);
        $this->assertSame('Inactive', $updated['Status']);
        $this->assertTrue(Hash::check('new-secret-password', $updated['Password Hash']));
        $this->assertSame('new.user@example.test', UserPermission::query()->value('user_email'));
        $this->assertSame('newuser', User::query()->value('username'));
    }

    public function test_database_mpr_requestor_update_persists_every_editable_field(): void
    {
        $repository = new MprRequestorDatabaseRepository();
        $repository->create([
            'email' => 'old.requestor@example.test',
            'username' => 'oldrequestor',
            'fullName' => 'Old Requestor',
            'jobPosition' => 'Supervisor',
            'role' => 'Manpower',
            'status' => 'Active',
            'passwordHash' => Hash::make('requestor-password'),
        ]);

        $repository->updateByEmail('old.requestor@example.test', [
            'email' => 'new.requestor@example.test',
            'username' => 'newrequestor',
            'fullName' => 'New Requestor',
            'jobPosition' => 'Regional Head',
            'role' => 'Manpower',
            'status' => 'Inactive',
            'passwordHash' => Hash::make('new-requestor-password'),
        ]);

        $this->assertNull($repository->findByEmail('old.requestor@example.test'));
        $updated = $repository->findByEmail('new.requestor@example.test');
        $this->assertNotNull($updated);
        $this->assertSame('new.requestor@example.test', $updated['Email']);
        $this->assertSame('newrequestor', $updated['Username']);
        $this->assertSame('New Requestor', $updated['Full Name']);
        $this->assertSame('Regional Head', $updated['Job Position']);
        $this->assertSame('Manpower', $updated['Role']);
        $this->assertSame('Inactive', $updated['Status']);
        $this->assertTrue(Hash::check('new-requestor-password', $updated['Password Hash']));
        $this->assertSame('new.requestor@example.test', MprRequestor::query()->value('email'));
    }
}
