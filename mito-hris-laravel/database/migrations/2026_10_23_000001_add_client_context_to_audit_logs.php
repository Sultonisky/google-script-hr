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
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('source');
            }
            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 120)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('audit_logs', 'device')) {
                $table->string('device', 120)->nullable()->after('browser');
            }
            if (!Schema::hasColumn('audit_logs', 'platform')) {
                $table->string('platform', 64)->nullable()->after('device');
            }
            if (!Schema::hasColumn('audit_logs', 'referer')) {
                $table->text('referer')->nullable()->after('platform');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['ip_address', 'user_agent', 'browser', 'device', 'platform', 'referer'],
                fn (string $column): bool => Schema::hasColumn('audit_logs', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
