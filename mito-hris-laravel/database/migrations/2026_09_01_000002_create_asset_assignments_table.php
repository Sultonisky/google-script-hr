<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('employee_id');        // String matching Google Sheets Employee ID
            $table->string('employee_name')->nullable();
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('return_by')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('status')->default('active'); // active | returned

            $table->timestamps();

            // Indexes
            $table->index('asset_id');
            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
