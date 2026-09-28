<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PermissionResolver;
use App\Support\Rbac;
use Illuminate\Console\Command;

class GrantDocumentPermissionsCommand extends Command
{
    protected $signature = 'mito:permissions:grant-document-access {--dry-run : Report changes without writing User_Permissions}';

    protected $description = 'Grant view_documents/download_documents to users who previously accessed Document Tracking via view_employees/manage_employees';

    private const GRANTED_BY = 'migration:document_permissions';

    /**
     * New permission => legacy permission that used to guard the same feature.
     * Order matters: download_documents depends on view_documents.
     */
    private const LEGACY_SOURCES = [
        'view_documents' => 'view_employees',
        'download_documents' => 'manage_employees',
    ];

    public function handle(
        UserRepositoryInterface $userRepository,
        UserPermissionRepositoryInterface $permissionRepository,
        PermissionResolver $resolver,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $usersChanged = 0;
        $mappingsCreated = 0;

        foreach ($userRepository->getAll() as $user) {
            $email = strtolower(trim((string) ($user['Email'] ?? $user['email'] ?? '')));
            $role = Rbac::normalizeRole((string) ($user['Role'] ?? $user['role'] ?? ''));
            if ($email === '' || $role === 'Super Admin') {
                continue;
            }

            $existing = [];
            foreach ($permissionRepository->mappingsForUser($email) as $row) {
                $key = trim((string) ($row['Permission Key'] ?? ''));
                if ($key !== '') {
                    $existing[$key] = $this->parseBoolean($row['Granted'] ?? false);
                }
            }

            $toGrant = [];
            foreach (self::LEGACY_SOURCES as $newKey => $legacyKey) {
                // An existing mapping (granted or explicitly revoked) is an admin decision; never override it.
                if (array_key_exists($newKey, $existing) || ($existing[$legacyKey] ?? false) !== true) {
                    continue;
                }
                $toGrant[] = $newKey;
            }

            if (in_array('download_documents', $toGrant, true)
                && !in_array('view_documents', $toGrant, true)
                && ($existing['view_documents'] ?? null) !== true) {
                // Without view_documents the download route is unreachable; skip rather than grant a dead permission.
                $toGrant = array_values(array_diff($toGrant, ['download_documents']));
            }

            if ($toGrant === []) {
                continue;
            }

            $usersChanged++;
            $this->line(($dryRun ? 'Would grant' : 'Granted') . " {$email}: " . implode(', ', $toGrant));

            foreach ($toGrant as $key) {
                $mappingsCreated++;
                if (!$dryRun && !$permissionRepository->upsert($email, $key, true, self::GRANTED_BY)) {
                    $this->error("Failed to grant {$key} to {$email}.");
                    return Command::FAILURE;
                }
            }

            if (!$dryRun) {
                $resolver->forget($email);
            }
        }

        $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
        $this->info("Document permission grant {$mode}.");
        $this->line("Users changed: {$usersChanged}");
        $this->line("Mappings " . ($dryRun ? 'to create' : 'created') . ": {$mappingsCreated}");

        return Command::SUCCESS;
    }

    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y'], true);
    }
}
