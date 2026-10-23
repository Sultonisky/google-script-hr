<?php

namespace App\Repositories\Sheets;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\Google\GoogleSheetsService;
use App\Support\AuditClientContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AuditLogSheetsRepository implements AuditLogRepositoryInterface
{
    protected GoogleSheetsService $sheets;
    protected string $sheetName;

    public function __construct(GoogleSheetsService $sheets)
    {
        $this->sheets = $sheets;
        $this->sheetName = config('google.sheets.audit_log', 'Audit_Log');
    }

    public function getLogs(?string $entityId = null): Collection
    {
        $this->migrateLegacySchema();
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $collection = collect($rows);

        if ($entityId !== null) {
            $target = trim($entityId);
            $collection = $collection->filter(function($row) use ($target) {
                return trim($row['Entity ID'] ?? $row['Recruitment ID'] ?? '') === $target;
            });
        }

        // Return latest first
        return $collection->reverse()->values();
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
    ): bool
    {
        try {
            $this->migrateLegacySchema();
            $user = $user ?: 'HR Administrator';
            $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $context = AuditClientContext::capture();

            $assoc = [
                'Audit ID' => $this->nextAuditId(),
                'Entity Type' => $entityType,
                'Entity ID' => $entityId ?? '',
                'Action' => $action,
                'Field' => $field ?? '',
                'Old Value' => $this->serializeValue($oldValue),
                'New Value' => $this->serializeValue($newValue),
                'User' => $user,
                'Source' => $source,
                'Timestamp' => $timestamp,
                'IP Address' => $context->ipAddress ?? '',
                'User Agent' => $context->userAgent ?? '',
                'Browser' => $context->browser ?? '',
                'Device' => $context->device ?? '',
                'Platform' => $context->platform ?? '',
                'Referer' => $context->referer ?? '',
            ];
            $headers = config('hris.schemas.Audit_Log', []);
            $rowValues = [];
            foreach ($headers as $header) {
                $rowValues[] = $assoc[$header] ?? '';
            }

            return $this->sheets->appendRow($this->sheetName, $rowValues);
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
        $rows = $this->sheets->getRowsAsAssoc($this->sheetName);
        $max = collect($rows)->map(function (array $row): int {
            return (int) preg_replace('/^AUD-/', '', (string) ($row['Audit ID'] ?? '0'));
        })->max() ?? 0;

        return sprintf('AUD-%06d', $max + 1);
    }

    protected function migrateLegacySchema(): void
    {
        $data = $this->sheets->getRange($this->sheetName, 'A:ZZ', false);
        $headers = array_map('trim', $data[0] ?? []);
        if (in_array('Entity Type', $headers, true)) {
            $this->ensureContextHeaders($headers);

            return;
        }

        $targetHeaders = array_values(config('hris.schemas.Audit_Log', []));
        $end = $this->columnLetter(max(1, count($targetHeaders)));
        if (empty($headers)) {
            $this->sheets->updateRange($this->sheetName, 'A1:'.$end.'1', [$targetHeaders]);
            return;
        }

        if (!in_array('Recruitment ID', $headers, true)) {
            return;
        }

        $rows = [array_values($targetHeaders)];
        foreach (array_slice($data, 1) as $index => $row) {
            $rows[] = array_pad([
                sprintf('AUD-LEGACY-%06d', $index + 1),
                'Candidate',
                $row[0] ?? '',
                $row[1] ?? '',
                $row[2] ?? '',
                $row[3] ?? '',
                $row[4] ?? '',
                $row[5] ?? '',
                'Legacy',
                $row[6] ?? '',
            ], count($targetHeaders), '');
        }

        $this->sheets->updateRange($this->sheetName, 'A1:'.$end.count($rows), $rows);
        $this->sheets->clearCache($this->sheetName);
    }

    /**
     * @param  list<string>  $headers
     */
    protected function ensureContextHeaders(array $headers): void
    {
        $required = ['IP Address', 'User Agent', 'Browser', 'Device', 'Platform', 'Referer'];
        $missing = array_values(array_filter(
            $required,
            fn (string $header): bool => !in_array($header, $headers, true),
        ));
        if ($missing === []) {
            return;
        }

        $updated = array_values(array_merge($headers, $missing));
        $end = $this->columnLetter(count($updated));
        $this->sheets->updateRange($this->sheetName, 'A1:'.$end.'1', [$updated]);
        $this->sheets->clearCache($this->sheetName);
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
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
