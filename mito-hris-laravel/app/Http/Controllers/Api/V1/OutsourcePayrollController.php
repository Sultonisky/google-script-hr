<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OutsourceIncentive;
use App\Models\OutsourcePayslip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutsourcePayrollController extends Controller
{
    public function payslips(Request $request, string $outsourceId): JsonResponse
    {
        $filters = $this->validatedFilters($request, $outsourceId);

        $payslips = OutsourcePayslip::query()
            ->where('outsource_id', $filters['outsource_id'])
            ->when($filters['period'] !== null, fn ($query) => $query->where('period', $filters['period']))
            ->orderByDesc('period')
            ->get()
            ->map(fn (OutsourcePayslip $payslip): array => [
                'period' => $payslip->period,
                'outsource_id' => $payslip->outsource_id,
                'full_name' => $payslip->full_name,
                'vendor' => $payslip->vendor,
                'hke' => (float) ($payslip->hke ?? 0),
                'basic_salary' => (float) ($payslip->basic_salary ?? 0),
                'bpjs_kesehatan_deduction' => (float) ($payslip->bpjs_kesehatan_deduction ?? 0),
                'loan_deduction' => (float) ($payslip->loan_deduction ?? 0),
                'take_home_pay' => (float) ($payslip->take_home_pay ?? 0),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'outsource_id' => $filters['outsource_id'],
            'filters' => ['period' => $filters['period']],
            'data' => $payslips,
            'meta' => ['count' => $payslips->count()],
        ]);
    }

    public function incentives(Request $request, string $outsourceId): JsonResponse
    {
        $filters = $this->validatedFilters($request, $outsourceId);

        $incentives = OutsourceIncentive::query()
            ->where('outsource_id', $filters['outsource_id'])
            ->when($filters['period'] !== null, fn ($query) => $query->where('period', $filters['period']))
            ->orderByDesc('period')
            ->get()
            ->map(fn (OutsourceIncentive $incentive): array => [
                'period' => $incentive->period,
                'outsource_id' => $incentive->outsource_id,
                'full_name' => $incentive->full_name,
                'vendor' => $incentive->vendor,
                'umk_amount' => (float) ($incentive->umk_amount ?? 0),
                'incentive_amount' => (float) ($incentive->incentive_amount ?? 0),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'outsource_id' => $filters['outsource_id'],
            'filters' => ['period' => $filters['period']],
            'data' => $incentives,
            'meta' => ['count' => $incentives->count()],
        ]);
    }

    /**
     * @return array{outsource_id: string, period: string|null}
     */
    private function validatedFilters(Request $request, string $outsourceId): array
    {
        $validated = validator([
            'outsource_id' => $outsourceId,
            'period' => $request->query('period'),
        ], [
            'outsource_id' => ['required', 'string', 'max:32'],
            'period' => ['sometimes', 'nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ])->validate();

        return [
            'outsource_id' => strtoupper($validated['outsource_id']),
            'period' => $validated['period'] ?? null,
        ];
    }
}
