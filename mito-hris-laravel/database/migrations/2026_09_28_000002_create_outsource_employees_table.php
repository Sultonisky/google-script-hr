<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated outsource data store (split from employees). Column order follows
 * the HR outsource master sheet (A–V) plus Vendor and audit columns.
 * Phone / bank account stored as string to keep leading zeroes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outsource_employees', function (Blueprint $table) {
            $table->id();
            $table->string('outsource_id', 32)->unique();
            $table->string('full_name')->nullable();
            $table->text('citizen_id_address')->nullable();
            $table->string('birth_date', 32)->nullable();
            $table->string('birth_place')->nullable();
            $table->string('last_education', 64)->nullable();
            $table->string('whatsapp_number', 32)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('job_title')->nullable();
            $table->string('work_location')->nullable();
            $table->string('work_city')->nullable();
            $table->string('bank_account', 64)->nullable();
            $table->string('mito_join_date', 32)->nullable();
            $table->string('contract_start_date', 32)->nullable();
            $table->string('contract_end_date', 32)->nullable();
            $table->string('cost_center')->nullable();
            $table->string('entity')->nullable();
            $table->string('payroll_scheme', 32)->nullable();
            $table->decimal('umk_amount', 15, 2)->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('incentive_amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('vendor', 64)->nullable()->index();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outsource_employees');
    }
};
