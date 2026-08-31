<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Console\Command;

class MigrateSuperUserRoleCommand extends Command
{
    protected $signature = 'mito:migrate-super-user-role {--dry-run : Report changes without writing to the Users sheet}';
    protected $description = 'Rename existing Users sheet roles from Super User to User';

    public function handle(UserRepositoryInterface $userRepository): int
    {
        $matches = array_values(array_filter(
            $userRepository->getAll(),
            fn(array $user): bool => strcasecmp(trim((string) ($user['Role'] ?? '')), 'Super User') === 0
        ));

        if ($this->option('dry-run')) {
            $this->info(sprintf('%d user(s) would be migrated.', count($matches)));
            return self::SUCCESS;
        }

        foreach ($matches as $user) {
            $email = trim((string) ($user['Email'] ?? ''));
            if ($email !== '') {
                $userRepository->updateByEmail($email, ['role' => 'User']);
            }
        }

        $this->info(sprintf('%d user(s) migrated to User.', count($matches)));
        return self::SUCCESS;
    }
}
