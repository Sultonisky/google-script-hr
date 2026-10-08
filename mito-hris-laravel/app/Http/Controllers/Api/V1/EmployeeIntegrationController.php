<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeIntegrationResource;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class EmployeeIntegrationController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'work_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'work_area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 50);
        $allEmployees = $this->employees->getAll();
        $filterOptions = [
            'work_locations' => $this->distinctValues($allEmployees->pluck('lokasiKerja')),
            'work_areas' => $this->distinctValues($allEmployees->pluck('areaKerja')),
        ];

        foreach ([
            'work_location' => 'lokasiKerja',
            'work_area' => 'areaKerja',
        ] as $filterKey => $employeeField) {
            if (! empty($filters[$filterKey])) {
                $selectedValue = mb_strtolower(trim($filters[$filterKey]));
                $allEmployees = $allEmployees->filter(
                    fn (EmployeeData $employee): bool => mb_strtolower(trim((string) $employee->{$employeeField})) === $selectedValue
                );
            }
        }

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $searchableFields = [
                'employeeId',
                'fullName',
                'nikNpwp',
                'jobPosition',
                'division',
                'department',
                'branchName',
                'jobPositionLocation',
                'areaKerja',
                'lokasiKerja',
            ];

            $allEmployees = $allEmployees->filter(function (EmployeeData $employee) use ($search, $searchableFields): bool {
                foreach ($searchableFields as $field) {
                    if (str_contains(mb_strtolower(trim((string) $employee->{$field})), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $allEmployees = $allEmployees->sortBy('employeeId')->values();
        $employees = new LengthAwarePaginator(
            $allEmployees->forPage($page, $perPage)->values(),
            $allEmployees->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return EmployeeIntegrationResource::collection($employees)
            ->additional([
                'success' => true,
                'filters' => $filterOptions,
            ]);
    }

    public function showByNik(string $nik): JsonResource|JsonResponse
    {
        Validator::make(['nik' => $nik], [
            'nik' => ['required', 'digits:16'],
        ])->validate();

        $employee = $this->employees->findByNik($nik);

        if (! $employee instanceof EmployeeData) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        return (new EmployeeIntegrationResource($employee))
            ->additional(['success' => true]);
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return list<string>
     */
    private function distinctValues(Collection $values): array
    {
        return $values
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->sort(fn (string $left, string $right): int => strcasecmp($left, $right))
            ->values()
            ->all();
    }
}
