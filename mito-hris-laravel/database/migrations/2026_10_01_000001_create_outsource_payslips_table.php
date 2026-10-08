<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payslip outsource per periode (YYYY-MM), satu baris per Outsource ID.
 * outsource_id merujuk ke outsource_employees.outsource_id tanpa foreign key
 * karena master outsource bisa berasal dari driver Sheets; nama dan vendor
 * disalin saat import agar payslip lama tidak berubah ketika master diedit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outsource_payslips', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->index();
            $table->string('outsource_id', 32)->index();
            $table->string('full_name')->nullable();
            $table->string('vendor', 64)->nullable()->index();
            $table->decimal('hke', 5, 2);
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('bpjs_kesehatan_deduction', 15, 2)->default(0);
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2);
            $table->string('source_file')->nullable();
            $table->string('imported_by')->nullable();
            $table->timestamps();

            $table->unique(['period', 'outsource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outsource_payslips');
    }
};
