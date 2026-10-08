<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsourcePayslip extends Model
{
    protected $table = 'outsource_payslips';

    protected $fillable = [
        'period',
        'outsource_id',
        'full_name',
        'vendor',
        'hke',
        'basic_salary',
        'bpjs_kesehatan_deduction',
        'loan_deduction',
        'take_home_pay',
        'source_file',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'hke' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'bpjs_kesehatan_deduction' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
        ];
    }

    public function outsourceEmployee(): BelongsTo
    {
        return $this->belongsTo(OutsourceEmployee::class, 'outsource_id', 'outsource_id');
    }
}
