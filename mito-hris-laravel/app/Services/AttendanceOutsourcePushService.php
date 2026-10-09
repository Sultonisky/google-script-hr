<?php

namespace App\Services;

use App\DTOs\OutsourceEmployeeData;
use App\Exceptions\AttendanceOutsourcePushException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes outsource persons (Outsource ID + name) from HRIS, the master, into the
 * Attendance Person List. Attendance creates missing IDs as inactive with the
 * default PIN and never modifies existing records, so pushes are safe to retry.
 */
class AttendanceOutsourcePushService
{
    private const PATH = '/api/v1/integrations/hris/outsource-persons';

    public const STATUSES = ['created', 'skipped', 'conflict', 'would_create'];

    public function isConfigured(): bool
    {
        return trim((string) config('hris.integration.attendance.base_url', '')) !== ''
            && (string) config('hris.integration.attendance.outsource_push_api_token', '') !== '';
    }

    /**
     * Best-effort push right after HRIS creates persons. Never throws: a failure
     * must not undo the HRIS record; `mito:outsource-push-attendance` retries.
     *
     * @param  iterable<int, OutsourceEmployeeData>  $created
     */
    public function pushCreated(iterable $created): void
    {
        $people = [];
        foreach ($created as $person) {
            if (filled($person->outsourceId) && filled($person->fullName)) {
                $people[] = ['outsource_id' => (string) $person->outsourceId, 'full_name' => (string) $person->fullName];
            }
        }

        if ($people === [] || ! $this->isConfigured()) {
            return;
        }

        try {
            foreach (array_chunk($people, 100) as $batch) {
                $result = $this->push($batch);
                foreach ($result['data'] as $record) {
                    if ($record['status'] === 'conflict') {
                        Log::warning('Attendance already has this Outsource ID with a different name or as a deleted person.', [
                            'outsource_id' => $record['outsource_id'],
                        ]);
                    }
                }
            }
        } catch (AttendanceOutsourcePushException $e) {
            Log::warning('HRIS saved the outsource person but could not push it to Attendance.', [
                'outsource_ids' => array_column($people, 'outsource_id'),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<array{outsource_id: string, full_name: string}>  $people
     * @return array{
     *   data: list<array{outsource_id: string, status: 'created'|'skipped'|'conflict'|'would_create', conflict_reason?: 'name_mismatch'|'deleted_record'}>,
     *   meta: array{processed: int, created: int, skipped: int, conflict: int, would_create: int}
     * }
     */
    public function push(array $people, bool $dryRun = false): array
    {
        $baseUrl = rtrim((string) config('hris.integration.attendance.base_url', ''), '/');
        $token = (string) config('hris.integration.attendance.outsource_push_api_token', '');
        $timeout = max(1, (int) config('hris.integration.attendance.timeout', 5));

        if (
            $baseUrl === ''
            || $token === ''
            || (! app()->environment(['local', 'testing']) && parse_url($baseUrl, PHP_URL_SCHEME) !== 'https')
        ) {
            throw new AttendanceOutsourcePushException('Attendance outsource push is not configured securely.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(min(3, $timeout))
                ->timeout($timeout)
                ->post($baseUrl . self::PATH, ['people' => $people, 'dry_run' => $dryRun]);
        } catch (ConnectionException) {
            throw new AttendanceOutsourcePushException('Attendance service is unavailable.');
        }

        if (! $response->successful()) {
            throw new AttendanceOutsourcePushException("Attendance returned HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (
            ! is_array($payload)
            || ($payload['success'] ?? false) !== true
            || ! is_array($payload['data'] ?? null)
            || ! array_is_list($payload['data'])
            || count($payload['data']) !== count($people)
        ) {
            throw new AttendanceOutsourcePushException('Attendance returned an unexpected response.');
        }

        $results = [];
        $meta = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'conflict' => 0, 'would_create' => 0];
        foreach ($people as $index => $person) {
            $record = $payload['data'][$index];
            if (
                ! is_array($record)
                || ! is_string($record['outsource_id'] ?? null)
                || strcasecmp($record['outsource_id'], $person['outsource_id']) !== 0
                || ! in_array($record['status'] ?? null, self::STATUSES, true)
            ) {
                throw new AttendanceOutsourcePushException('Attendance returned an unexpected response.');
            }

            $result = ['outsource_id' => $record['outsource_id'], 'status' => $record['status']];
            if (isset($record['conflict_reason'])) {
                if (! in_array($record['conflict_reason'], ['name_mismatch', 'deleted_record'], true)) {
                    throw new AttendanceOutsourcePushException('Attendance returned an unexpected response.');
                }

                $result['conflict_reason'] = $record['conflict_reason'];
            }

            $results[] = $result;
            $meta[$record['status']]++;
            $meta['processed']++;
        }

        return ['data' => $results, 'meta' => $meta];
    }
}
