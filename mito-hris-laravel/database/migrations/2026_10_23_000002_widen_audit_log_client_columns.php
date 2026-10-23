<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 120)->nullable()->change();
            }
            if (Schema::hasColumn('audit_logs', 'device')) {
                $table->string('device', 120)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 80)->nullable()->change();
            }
            if (Schema::hasColumn('audit_logs', 'device')) {
                $table->string('device', 32)->nullable()->change();
            }
        });
    }
};
