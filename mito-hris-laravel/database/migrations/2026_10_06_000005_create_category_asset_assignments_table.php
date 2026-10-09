<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_assignment_id')->nullable()->unique();
            $table->string('asset_type');
            $table->unsignedBigInteger('asset_id');
            $table->string('employee_id');
            $table->string('employee_name')->nullable();
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('return_by')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['asset_type', 'asset_id']);
            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_asset_assignments');
    }
};
