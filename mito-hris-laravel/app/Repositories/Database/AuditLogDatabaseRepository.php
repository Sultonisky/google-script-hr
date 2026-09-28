<?php

namespace App\Repositories\Database;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AuditLogDatabaseRepository implements AuditLogRepositoryInterface
{
    public function getLogs(?string $entityId = null): Collection
    {
        $query = AuditLog::query()->orderByDesc('id');

        if ($entityId !== null) {
            $query->where('entity_id', trim($entityId));
        }

        return $query->get()->map(fn (AuditLog $row) => $this->toSheetFormat($row))->values();
    }

    public function log(
        string $entityType,
        ?string $entityId,
        string $action,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $user = null,
        string $source = 'System'
    ): bool {
        try {
            $now = now()->timezone('Asia/Jakarta');

            AuditLog::create([
                'audit_id' => $this->nextAuditId(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'field' => $field,
                'old_value' => $this->serializeValue($oldValue),
                'new_value' => $this->serializeValue($newValue),
                'user' => $user ?: 'HR Administrator',
                'source' => $source,
                'logged_at' => $now,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Audit log write failed', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function nextAuditId(): string
    {
        $max = 0;
        foreach (AuditLog::query()->pluck('audit_id') as $id) {
            if (preg_match('/^AUD-(\d+)$/', (string) $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return sprintf('AUD-%06d', $max + 1);
    }

    /**
     * @return array<string, string>
     */
    protected function toSheetFormat(AuditLog $row): array
    {
        return [
            'Audit ID' => (string) $row->audit_id,
            'Entity Type' => (string) ($row->entity_type ?? ''),
            'Entity ID' => (string) ($row->entity_id ?? ''),
            'Action' => (string) ($row->action ?? ''),
            'Field' => (string) ($row->field ?? ''),
            'Old Value' => (string) ($row->old_value ?? ''),
            'New Value' => (string) ($row->new_value ?? ''),
            'User' => (string) ($row->user ?? ''),
            'Source' => (string) ($row->source ?? ''),
            'Timestamp' => optional($row->logged_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                ?? optional($row->created_at)?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                ?? '',
        ];
    }

    protected function serializeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }
}
