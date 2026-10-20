<?php

namespace App\Services;

use App\DTOs\EmployeeData;
use App\Exceptions\AttendanceEmployeePushException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttendanceEmployeePushService
{
    private const PATH = '/api/v1/integrations/hris/employees';

    private const STATUSES = ['created', 'updated', 'skipped', 'conflict', 'would_create', 'would_update'];

    private const COUNTERS = ['processed', 'created', 'updated', 'skipped', 'conflict', 'would_create', 'would_update'];

    public function isConfigured(): bool
    {
        return trim((string) config('hris.integration.attendance.base_url', '')) !== ''
            && (string) config('hris.integration.attendance.employee_push_api_token', '') !== '';
    }

    /**
     * Push employee profile data after a successful HRIS write. Attendance
     * failures must not roll back or block the HRIS transaction.
     *
     * @param  iterable<int, EmployeeData>  $employees
     */
    public function pushCreated(iterable $employees): void
    {
        $this->pushBestEffort($employees, 'create');
    }

    public function pushUpdated(EmployeeData $employee): void
    {
        $this->pushBestEffort([$employee], 'update');
    }

    /**
     * @param  iterable<int, EmployeeData>  $employees
     * @return array{data: list<array{employee_id: string, status: string}>, meta: array<string, int>}
     */
    public function pushEmployees(iterable $employees, bool $dryRun = false): array
    {
        $people = [];
        foreach ($employees as $employee) {
            if (filled($employee->employeeId) && filled($employee->fullName)) {
                if (! filled($employee->statusEmployee)) {
                    throw new AttendanceEmployeePushException('Employee status is required for Attendance sync.');
                }

                $people[] = $this->toPayload($employee);
            }
        }

        $aggregate = [
            'data' => [],
            'meta' => array_fill_keys(self::COUNTERS, 0),
        ];

        foreach (array_chunk($people, 100) as $batch) {
            $result = $this->push($batch, $dryRun);
            array_push($aggregate['data'], ...$result['data']);
            foreach (self::COUNTERS as $counter) {
                $aggregate['meta'][$counter] += $result['meta'][$counter];
            }
        }

        return $aggregate;
    }

    /**
     * @param  list<array{
     *   employee_id: string, nik: ?string, full_name: string, employment_status: string,
     *   join_date: ?string, end_date: ?string, job_position: ?string, department: ?string,
     *   division: ?string, branch: ?string, email: ?string, phone: ?string
     * }>  $people
     * @return array{data: list<array{employee_id: string, status: string}>, meta: array<string, int>}
     */
    public function push(array $people, bool $dryRun = false): array
    {
        $baseUrl = rtrim((string) config('hris.integration.attendance.base_url', ''), '/');
        $token = (string) config('hris.integration.attendance.employee_push_api_token', '');
        $timeout = max(1, (int) config('hris.integration.attendance.timeout', 5));

        if (
            $baseUrl === ''
            || $token === ''
            || count($people) > 100
            || (! app()->environment(['local', 'testing']) && parse_url($baseUrl, PHP_URL_SCHEME) !== 'https')
        ) {
            throw new AttendanceEmployeePushException('Attendance employee push is not configured securely.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(min(3, $timeout))
                ->timeout($timeout)
                ->post($baseUrl.self::PATH, ['people' => $people, 'dry_run' => $dryRun]);
        } catch (ConnectionException) {
            throw new AttendanceEmployeePushException('Attendance service is unavailable.');
        }

        if (! $response->successful()) {
            throw new AttendanceEmployeePushException("Attendance returned HTTP {$response->status()}.");
        }

        $payload = $response->json();
        if (
            ! is_array($payload)
            || ($payload['success'] ?? false) !== true
            || ! is_array($payload['data'] ?? null)
            || ! array_is_list($payload['data'])
            || count($payload['data']) !== count($people)
            || ! is_array($payload['meta'] ?? null)
        ) {
            throw new AttendanceEmployeePushException('Attendance returned an unexpected response.');
        }

        $results = [];
        $meta = array_fill_keys(self::COUNTERS, 0);
        foreach ($people as $index => $person) {
            $record = $payload['data'][$index];
            if (
                ! is_array($record)
                || ! is_string($record['employee_id'] ?? null)
                || strcasecmp($record['employee_id'], $person['employee_id']) !== 0
                || ! in_array($record['status'] ?? null, self::STATUSES, true)
            ) {
                throw new AttendanceEmployeePushException('Attendance returned an unexpected response.');
            }

            $results[] = ['employee_id' => $record['employee_id'], 'status' => $record['status']];
            $meta[$record['status']]++;
            $meta['processed']++;
        }

        foreach (self::COUNTERS as $counter) {
            if (! is_int($payload['meta'][$counter] ?? null) || $payload['meta'][$counter] !== $meta[$counter]) {
                throw new AttendanceEmployeePushException('Attendance returned an unexpected response.');
            }
        }

        return ['data' => $results, 'meta' => $meta];
    }

    private function toPayload(EmployeeData $employee): array
    {
        $resignDate = trim((string) $employee->resignDate);

        return [
            'employee_id' => trim((string) $employee->employeeId),
            'nik' => $this->nullableValue(ltrim(trim((string) $employee->nikNpwp), "'")),
            'full_name' => trim((string) $employee->fullName),
            'employment_status' => trim((string) $employee->statusEmployee),
            'join_date' => $this->nullableValue($employee->joinDate),
            'end_date' => $this->nullableValue($resignDate !== '' ? $resignDate : $employee->endDateContract),
            'job_position' => $this->nullableValue($employee->jobPosition),
            'department' => $this->nullableValue($employee->department),
            'division' => $this->nullableValue($employee->division),
            'branch' => $this->nullableValue($employee->branchName),
            'email' => $this->nullableValue($employee->workingEmail),
            'phone' => $this->nullableValue(ltrim(trim((string) $employee->mobilePhone), "'")),
        ];
    }

    private function nullableValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  iterable<int, EmployeeData>  $employees
     */
    private function pushBestEffort(iterable $employees, string $operation): void
    {
        $people = [];
        foreach ($employees as $employee) {
            if (filled($employee->employeeId) && filled($employee->fullName)) {
                $people[] = $employee;
            }
        }

        if ($people === [] || ! $this->isConfigured()) {
            return;
        }

        try {
            $result = $this->pushEmployees($people);
            foreach ($result['data'] as $record) {
                if ($record['status'] === 'conflict') {
                    Log::warning('Attendance reported an employee profile conflict.', [
                        'employee_id' => $record['employee_id'],
                    ]);
                }
            }
        } catch (AttendanceEmployeePushException $e) {
            Log::warning("HRIS saved an employee but could not push the {$operation} profile to Attendance.", [
                'employee_ids' => array_map(fn (EmployeeData $employee) => $employee->employeeId, $people),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
