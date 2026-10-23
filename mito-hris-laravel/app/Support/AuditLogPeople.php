<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\MprRequest;
use App\Models\MprRequestor;
use App\Models\OutsourceEmployee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves audit rows to person names already stored in HRIS.
 * A missing record stays unnamed instead of showing a placeholder.
 */
class AuditLogPeople
{
    /**
     * @param  iterable<array<string, mixed>>  $logs
     * @return array<string, array{subject: ?string, actor: ?string}>
     */
    public static function forLogs(iterable $logs): array
    {
        $rows = [];
        foreach ($logs as $log) {
            if (is_array($log)) {
                $rows[] = $log;
            }
        }

        $employees = self::employees(self::idsFor($rows, ['employee', 'probation']));
        $candidates = self::candidates(self::idsFor($rows, ['candidate', 'applicant']));
        $outsources = self::outsources(self::idsFor($rows, ['outsource']));
        $mprNames = self::mprRequestors(self::idsFor($rows, ['mpr']));
        $accountNames = self::accounts(self::emails($rows));

        $labels = [];
        foreach ($rows as $log) {
            $auditId = trim((string) ($log['Audit ID'] ?? ''));
            if ($auditId === '') {
                continue;
            }

            $type = strtolower(trim((string) ($log['Entity Type'] ?? '')));
            $entityId = trim((string) ($log['Entity ID'] ?? $log['Recruitment ID'] ?? ''));
            $key = strtolower($entityId);
            $subject = self::subject($type, $key, $employees, $candidates, $outsources, $mprNames, $accountNames);
            $actor = self::actor(trim((string) ($log['User'] ?? '')), $accountNames, $subject);

            $labels[$auditId] = [
                'subject' => $subject,
                'actor' => $actor,
            ];
        }

        return $labels;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $types
     * @return list<string>
     */
    private static function idsFor(array $rows, array $types): array
    {
        $ids = [];
        foreach ($rows as $log) {
            $type = strtolower(trim((string) ($log['Entity Type'] ?? '')));
            if (!in_array($type, $types, true)) {
                continue;
            }

            $id = trim((string) ($log['Entity ID'] ?? $log['Recruitment ID'] ?? ''));
            if ($id !== '' && $id !== '-' && !self::isEmail($id)) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private static function emails(array $rows): array
    {
        $emails = [];
        foreach ($rows as $log) {
            foreach ([$log['Entity ID'] ?? '', $log['User'] ?? ''] as $value) {
                $value = trim((string) $value);
                if (self::isEmail($value)) {
                    $emails[] = strtolower($value);
                }
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * @param  array<string, string>  $employees
     * @param  array<string, string>  $candidates
     * @param  array<string, string>  $outsources
     * @param  array<string, string>  $mprNames
     * @param  array<string, string>  $accountNames
     */
    private static function subject(
        string $type,
        string $key,
        array $employees,
        array $candidates,
        array $outsources,
        array $mprNames,
        array $accountNames,
    ): ?string {
        if ($key === '' || $key === '-') {
            return null;
        }

        $name = match ($type) {
            'employee', 'probation' => $employees[$key] ?? null,
            'candidate', 'applicant' => $candidates[$key] ?? null,
            'outsource' => $outsources[$key] ?? null,
            'mpr' => $mprNames[$key] ?? null,
            'user', 'authentication', 'mpr requestor' => $accountNames[$key] ?? null,
            default => $employees[$key] ?? $candidates[$key] ?? $outsources[$key] ?? $accountNames[$key] ?? null,
        };

        return $name !== null && strcasecmp($name, $key) !== 0 ? $name : null;
    }

    /**
     * @param  array<string, string>  $accountNames
     */
    private static function actor(string $user, array $accountNames, ?string $subject): ?string
    {
        if (!self::isEmail($user)) {
            return null;
        }

        $name = $accountNames[strtolower($user)] ?? null;
        if ($name === null || strcasecmp($name, $user) === 0 || strcasecmp((string) $name, (string) $subject) === 0) {
            return null;
        }

        return $name;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private static function employees(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return self::map(
            self::whereFolded(Employee::query(), 'employee_id', $ids)->get(['employee_id', 'full_name']),
            'employee_id',
            'full_name',
        );
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private static function candidates(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return self::map(
            self::whereFolded(Candidate::query(), 'recruitment_id', $ids)->get(['recruitment_id', 'full_name']),
            'recruitment_id',
            'full_name',
        );
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private static function outsources(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return self::map(
            self::whereFolded(OutsourceEmployee::query(), 'outsource_id', $ids)->get(['outsource_id', 'full_name']),
            'outsource_id',
            'full_name',
        );
    }

    /**
     * @param  list<string>  $numbers
     * @return array<string, string>
     */
    private static function mprRequestors(array $numbers): array
    {
        if ($numbers === []) {
            return [];
        }

        return self::map(
            self::whereFolded(MprRequest::query(), 'mpr_number', $numbers)->get(['mpr_number', 'requestor_name']),
            'mpr_number',
            'requestor_name',
        );
    }

    /**
     * @param  list<string>  $emails
     * @return array<string, string>
     */
    private static function accounts(array $emails): array
    {
        if ($emails === []) {
            return [];
        }

        $names = self::map(
            self::whereFolded(User::query(), 'email', $emails)->get(['email', 'name']),
            'email',
            'name',
        );

        $requestors = self::map(
            self::whereFolded(MprRequestor::query(), 'email', $emails)->get(['email', 'full_name']),
            'email',
            'full_name',
        );

        return $names + $requestors;
    }

    /**
     * @param  iterable<object>  $rows
     * @return array<string, string>
     */
    private static function map(iterable $rows, string $key, string $value): array
    {
        $map = [];
        foreach ($rows as $row) {
            $id = strtolower(trim((string) ($row->{$key} ?? '')));
            $name = trim((string) ($row->{$value} ?? ''));
            if ($id !== '' && $name !== '') {
                $map[$id] = $name;
            }
        }

        return $map;
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<string>  $values
     * @return Builder<*>
     */
    private static function whereFolded(Builder $query, string $column, array $values): Builder
    {
        $folded = array_map(fn (string $value): string => strtolower(trim($value)), $values);
        $placeholders = implode(', ', array_fill(0, count($folded), '?'));

        return $query->whereRaw('LOWER('.$column.') IN ('.$placeholders.')', $folded);
    }

    private static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
