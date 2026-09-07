<?php

namespace App\Http\Requests\HR;

use App\Enums\ProbationDecisionType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SubmitProbationEvaluationRequest
 *
 * Backend validation for the Probation Evaluation / Performance Review
 * submission. This is the single server-side validation source — the frontend
 * validation in probation-modals.blade.php is convenience only.
 *
 * Business rules enforced here:
 *   - decision must classify into PASS / FAIL / EXTEND
 *   - all 13 behavioral indicators are required ("1" or "0")
 *   - EXTEND ("Perpanjang Kontrak") requires extension_start; duration is derived
 *     server-side from the current Employee contract dates.
 */
class SubmitProbationEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by route middleware `can:manage_probation`.
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (ProbationDecisionType::fromDecisionString($value) === null) {
                        $fail('Keputusan evaluasi tidak valid. Pilih: Diangkat sebagai Karyawan Tetap, Tidak Lulus, atau Perpanjang Kontrak.');
                    }
                },
            ],

            // Indicators — required, "1" (✓) or "0" (X) only
            'indicators.integrity_1' => 'required|in:1,0',
            'indicators.integrity_2' => 'required|in:1,0',
            'indicators.integrity_3' => 'required|in:1,0',
            'indicators.integrity_4' => 'required|in:1,0',
            'indicators.ci_1'        => 'required|in:1,0',
            'indicators.ci_2'        => 'required|in:1,0',
            'indicators.ci_3'        => 'required|in:1,0',
            'indicators.ci_4'        => 'required|in:1,0',
            'indicators.ee_1'        => 'required|in:1,0',
            'indicators.ee_2'        => 'required|in:1,0',
            'indicators.tw_1'        => 'required|in:1,0',
            'indicators.tw_2'        => 'required|in:1,0',
            'indicators.tw_3'        => 'required|in:1,0',

            // Kept nullable for backward-compatible clients; the service ignores it.
            'extension_duration' => 'nullable|string',
            'extension_start' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $isExtend = ProbationDecisionType::fromDecisionString(
                        (string) $this->input('decision')
                    )?->isExtend();

                    if ($isExtend && empty($value)) {
                        $fail('Tanggal mulai kontrak baru wajib diisi untuk keputusan Perpanjang Kontrak.');
                    }
                },
            ],
            'extension_end' => 'nullable|date',

            'notes'              => 'nullable|string|max:1000',
            'reviewer_name'      => 'nullable|string|max:200',
            'approval_dept'      => 'nullable|string|in:Setuju,Tidak',
            'approval_dept_name' => 'nullable|string|max:200',
            'approval_dept_date' => 'nullable|date',
            'approval_hrbp'      => 'nullable|string|in:Setuju,Tidak',
            'approval_hrbp_name' => 'nullable|string|max:200',
            'approval_hrbp_date' => 'nullable|date',
        ];
    }
}
