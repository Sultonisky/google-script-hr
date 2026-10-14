<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('outsource_employees')
            ->whereNull('hris_status')
            ->update(['hris_status' => 'Aktif']);

        Schema::table('outsource_employees', function (Blueprint $table): void {
            $table->string('hris_status', 32)->default('Aktif')->change();
        });
    }

    public function down(): void
    {
        Schema::table('outsource_employees', function (Blueprint $table): void {
            $table->string('hris_status', 32)->nullable()->default(null)->change();
        });
    }
};
