<?php

namespace App\Services;

use App\Enums\CertStatus;
use App\Models\Certification;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CertificationService
{
    private const MAX_GENERATE_ATTEMPTS = 50;
    private const CODE_PREFIX = 'SRT';

    public function __construct(
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    // ── Code Generation ─────────────────────────────────────────────

    public function generateCode(): string
    {
        $existingCodes = Certification::where('cert_code', 'LIKE', self::CODE_PREFIX . '-%')
            ->pluck('cert_code')->filter()->all();

        $maxSequence = $this->findMaxSequence($existingCodes);

        for ($attempt = 0; $attempt < self::MAX_GENERATE_ATTEMPTS; $attempt++) {
            $nextSequence = $maxSequence + 1 + $attempt;
            $code = sprintf('%s-%05d', self::CODE_PREFIX, $nextSequence);
            if (!Certification::where('cert_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Failed to generate unique certification code.');
    }

    private function findMaxSequence(array $codes): int
    {
        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote(self::CODE_PREFIX, '/') . '-(\d{5})$/', $code, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $max) $max = $seq;
            }
        }
        return $max;
    }

    // ── Status Handling ─────────────────────────────────────────────

    /**
     * Compute computed status: only for Active vs Expired.
     * Revoked/Suspended are explicit and never overwritten.
     */
    public function computeStatus(?string $expiryDate, ?CertStatus $currentStatus = null): CertStatus
    {
        // Explicit statuses must be preserved
        if ($currentStatus && in_array($currentStatus, [CertStatus::REVOKED, CertStatus::SUSPENDED])) {
            return $currentStatus;
        }

        if (!$expiryDate) return CertStatus::ACTIVE;

        return $expiryDate >= now()->toDateString()
            ? CertStatus::ACTIVE
            : CertStatus::EXPIRED;
    }

    // ── Statistics ──────────────────────────────────────────────────

    public function getStats(): array
    {
        $base = fn () => Certification::query();
        return [
            'total'         => $base()->count(),
            'active'        => $base()->where('status', CertStatus::ACTIVE)->count(),
            'expiring_soon' => $base()->where('status', CertStatus::ACTIVE)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>=', now())
                ->where('expiry_date', '<=', now()->addDays(30))
                ->count(),
            'expired'       => $base()->where('status', CertStatus::EXPIRED)->count(),
        ];
    }

    public function getExpiringSoon(int $days = 30): \Illuminate\Support\Collection
    {
        return Certification::where('status', CertStatus::ACTIVE)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date')
            ->get();
    }
}
