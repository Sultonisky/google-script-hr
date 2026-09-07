<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->string('employee_id', 100)->nullable()->change();
            $table->string('employee_name', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing records may legitimately be company/product certificates
        // without an employee owner, so the nullable contract is retained.
    }
};