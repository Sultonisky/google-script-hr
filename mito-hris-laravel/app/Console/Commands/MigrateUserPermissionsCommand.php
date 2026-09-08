<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\LegacyRolePermissionSource;
use App\Support\Rbac;
use Illuminate\Console\Command;

class MigrateUserPermissionsCommand extends Command
{
    protected $signature = 'mito:permissions:migrate {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Migrate legacy role permissions into User_Permissions without changing Users';

    public function handle(
        UserRepositoryInterface $userRepository,
        UserPermissionRepositoryInterface $permissionRepository,
    ): int {
        $usersFound = 0;
        $mappingsToCreate = 0;
        $mappingsExisting = 0;
        $conflicts = 0;
        $skippedUsers = 0;
        $invalidUsers = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach ($userRepository->getAll() as $user) {
            $usersFound++;
            $email = strtolower(trim((string) ($user['Email'] ?? $user['email'] ?? '')));
            $sourceRole = trim((string) ($user['Role'] ?? $user['role'] ?? ''));
            $role = Rbac::normalizeRole($sourceRole);

            if ($email === '' || $role === '' || !in_array($role, config('hris.auth.valid_roles_internal', []), true)) {
                $invalidUsers++;
                $this->warn('Skipped invalid user record: missing email or unsupported role.');
                continue;
            }

            if ($role === 'Super Admin') {
                $skippedUsers++;
                $this->line("Skipped {$email}: Super Admin keeps wildcard access.");
                continue;
            }

            $existing = [];
            foreach ($permissionRepository->mappingsForUser($email) as $row) {
                $key = trim((string) ($row['Permission Key'] ?? ''));
                if ($key === '') {
                    continue;
                }

                $granted = $this->parseBoolean($row['Granted'] ?? false);
                if (array_key_exists($key, $existing) && $existing[$key] !== $granted) {
                    $conflicts++;
                    continue;
                }
                $existing[$key] = $granted;
            }

            foreach ($this->permissionsForUser($role) as $permission) {
                if (array_key_exists($permission, $existing)) {
                    $mappingsExisting++;
                    continue;
                }

                $mappingsToCreate++;
                if (!$dryRun && $permissionRepository->upsert($email, $permission, true, 'migration:role_permissions')) {
                    $existing[$permission] = true;
                }
            }
        }

        $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
        $this->info("Permission migration {$mode}.");
        $this->line("Users found: {$usersFound}");
        $this->line("Mappings to create: {$mappingsToCreate}");
        $this->line("Mappings already existing: {$mappingsExisting}");
        $this->line("Conflicts: {$conflicts}");
        $this->line("Skipped users: {$skippedUsers}");
        $this->line("Invalid users: {$invalidUsers}");

        return Command::SUCCESS;
    }

    private function permissionsForUser(string $role): array
    {
        $permissions = array_values(array_filter(
            LegacyRolePermissionSource::permissionsForRole($role),
            fn (string $permission): bool => $permission !== '*'
        ));

        $portalRoles = config('hris.auth.dedicated_portal_roles', []);
        foreach (['assets' => 'assets.access', 'certificates' => 'certificates.access'] as $portal => $permission) {
            if (in_array($role, $portalRoles[$portal] ?? [], true)) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y'], true);
    }
}