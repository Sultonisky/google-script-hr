<?php

namespace App\Support;

use App\Repositories\Contracts\AuditLogRepositoryInterface;

class PublicApplicationRejection
{
    public function __construct(private AuditLogRepositoryInterface $audit)
    {
    }

    /**
     * Record a rejected public application without the application body, NIK, or CV.
     *
     * @param  list<string>  $reasons
     */
    public function record(array $reasons, ?string $ip, ?string $userAgent): void
    {
        if ($reasons === []) {
            return;
        }

        $agent = trim((string) $userAgent);

        try {
            $this->audit->log(
                entityType: 'Candidate',
                entityId: null,
                action: 'application_rejected',
                field: 'probe',
                oldValue: null,
                newValue: [
                    'timestamp' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'ip' => $ip,
                    'user_agent' => $agent === '' ? null : mb_substr($agent, 0, 500),
                    'reasons' => array_values($reasons),
                ],
                user: 'Sistem',
                source: 'Public'
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
