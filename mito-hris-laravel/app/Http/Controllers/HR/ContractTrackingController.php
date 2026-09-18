<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ContractTrackingController extends Controller
{
    /** Window of remaining days shown on this page. */
    private const TRACKING_DAYS = 21;

    protected EmployeeRepositoryInterface $employeeRepo;

    public function __construct(EmployeeRepositoryInterface $employeeRepo)
    {
        $this->employeeRepo = $employeeRepo;
    }

    public function index(Request $request): View
    {
        $perPage     = max(1, (int) $request->query('per_page', 10));
        $currentPage = max(1, (int) $request->query('page', 1));
        $today       = now()->timezone('Asia/Jakarta')->startOfDay();

        $allEmployees = $this->employeeRepo->getAll();

        $tracking = $allEmployees
            ->filter(function ($employee) {
                $status = strtolower(trim((string) ($employee->statusEmployee ?? '')));

                return in_array($status, ['contract', 'pkwt'], true)
                    && !empty(trim((string) ($employee->endDateContract ?? '')));
            })
            ->map(function ($employee) use ($today) {
                $record = (object) get_object_vars($employee);
                $endDate = $this->parseDate($employee->endDateContract);

                $record->endDateParsed   = $endDate;
                $record->daysRemaining   = $endDate ? (int) $today->diffInDays($endDate, false) : null;
                $record->contractDuration = $this->formatContractDuration(
                    $employee->joinDate ?? null,
                    $employee->endDateContract ?? null
                );

                return $record;
            })
            ->filter(function ($record) {
                return $record->daysRemaining !== null
                    && $record->daysRemaining >= 0
                    && $record->daysRemaining <= self::TRACKING_DAYS;
            })
            ->values();

        $stats = [
            'total'     => $tracking->count(),
            'critical'  => $tracking->filter(fn ($e) => $e->daysRemaining <= 7)->count(),
            'urgent'    => $tracking->filter(fn ($e) => $e->daysRemaining > 7 && $e->daysRemaining <= 14)->count(),
            'upcoming'  => $tracking->filter(fn ($e) => $e->daysRemaining > 14 && $e->daysRemaining <= self::TRACKING_DAYS)->count(),
        ];

        $filtered = $tracking;

        $searchFilter = $request->query('search');
        if ($searchFilter) {
            $search = strtolower(trim($searchFilter));
            $filtered = $filtered->filter(
                fn ($e) =>
                str_contains(strtolower($e->fullName ?? ''), $search)
                    || str_contains(strtolower($e->employeeId ?? ''), $search)
                    || str_contains(strtolower($e->jobPosition ?? ''), $search)
                    || str_contains(strtolower($e->department ?? ''), $search)
            );
        }

        $windowFilter = $request->query('window', '');
        $windowFilter = in_array($windowFilter, ['7', '14', '21'], true) ? $windowFilter : '';
        if ($windowFilter !== '') {
            $maxDays = (int) $windowFilter;
            $filtered = $filtered->filter(fn ($e) => $e->daysRemaining <= $maxDays);
        }

        $sortFilter = $request->query('sort', 'days_asc');
        $sortFilter = in_array($sortFilter, ['days_asc', 'days_desc', 'name_asc', 'name_desc', 'end_asc', 'end_desc'], true)
            ? $sortFilter
            : 'days_asc';

        $filtered = match ($sortFilter) {
            'days_desc' => $filtered->sortByDesc(fn ($e) => $e->daysRemaining)->values(),
            'name_asc'  => $filtered->sortBy(fn ($e) => strtolower(trim($e->fullName ?? '')))->values(),
            'name_desc' => $filtered->sortByDesc(fn ($e) => strtolower(trim($e->fullName ?? '')))->values(),
            'end_asc'   => $filtered->sortBy(fn ($e) => $e->endDateParsed?->timestamp ?? PHP_INT_MAX)->values(),
            'end_desc'  => $filtered->sortByDesc(fn ($e) => $e->endDateParsed?->timestamp ?? 0)->values(),
            default     => $filtered->sortBy(fn ($e) => $e->daysRemaining)->values(),
        };

        $total  = $filtered->count();
        $offset = ($currentPage - 1) * $perPage;
        $contracts = $filtered->slice($offset, $perPage)->values();

        return view('hr.contracts.index', compact(
            'contracts',
            'stats',
            'total',
            'currentPage',
            'perPage',
            'searchFilter',
            'sortFilter',
            'windowFilter'
        ));
    }

    private function parseDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone('Asia/Jakarta')->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Human-readable contract duration from Join Date → End Date (Contract),
     * matching PKWT month convention used elsewhere in the app.
     */
    private function formatContractDuration(?string $joinDate, ?string $endContract): string
    {
        $start = $this->parseDate($joinDate);
        $end   = $this->parseDate($endContract);

        if (!$start || !$end || $end->lessThan($start)) {
            return '-';
        }

        $anchor = $end->copy()->addDay();
        $months = ($anchor->year - $start->year) * 12 + ($anchor->month - $start->month);
        while ($months > 0 && $start->copy()->addMonthsNoOverflow($months)->greaterThan($anchor)) {
            $months--;
        }

        if ($months <= 0) {
            $days = (int) $start->diffInDays($end);
            return $days > 0 ? $days . ' Hari' : '-';
        }

        return $months . ' Bulan';
    }
}
