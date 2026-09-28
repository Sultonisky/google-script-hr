<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserPermissionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PermissionResolver;
use App\Support\Rbac;
use Illuminate\Console\Command;

/**
 * Carries existing access over when a feature moves from a shared permission
 * to its own dedicated permission keys.
 */
abstract class AbstractGrantSplitPermissionsCommand extends Command
{
    /**
     * New permission => legacy permission that used to guard the same feature.
     *
     * @return array<string,string>
     */
    abstract protected function legacySources(): array;

    abstract protected function grantedBy(): string;

    abstract protected function label(): string;

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
            foreach ($this->legacySources() as $newKey => $legacyKey) {
                // An existing mapping (granted or explicitly revoked) is an admin decision; never override it.
                if (array_key_exists($newKey, $existing) || ($existing[$legacyKey] ?? false) !== true) {
                    continue;
                }
                $toGrant[] = $newKey;
            }

            // Skip keys whose dependency will not be held, e.g. download without view.
            $toGrant = array_values(array_filter($toGrant, function (string $key) use ($toGrant, $existing, $resolver): bool {
                foreach ($resolver->dependenciesFor($key) as $dependency) {
                    if (!in_array($dependency, $toGrant, true) && ($existing[$dependency] ?? null) !== true) {
                        return false;
                    }
                }

                return true;
            }));

            if ($toGrant === []) {
                continue;
            }

            $usersChanged++;
            $this->line(($dryRun ? 'Would grant' : 'Granted') . " {$email}: " . implode(', ', $toGrant));

            foreach ($toGrant as $key) {
                $mappingsCreated++;
                if (!$dryRun && !$permissionRepository->upsert($email, $key, true, $this->grantedBy())) {
                    $this->error("Failed to grant {$key} to {$email}.");
                    return Command::FAILURE;
                }
            }

            if (!$dryRun) {
                $resolver->forget($email);
            }
        }

        $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
        $this->info("{$this->label()} permission grant {$mode}.");
        $this->line("Users changed: {$usersChanged}");
        $this->line('Mappings ' . ($dryRun ? 'to create' : 'created') . ": {$mappingsCreated}");

        return Command::SUCCESS;
    }

    private function parseBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y'], true);
    }
}
