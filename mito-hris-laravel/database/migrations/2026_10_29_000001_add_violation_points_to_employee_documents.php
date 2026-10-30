<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->unsignedSmallInteger('violation_points')->default(0)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn('violation_points');
        });
    }
};
