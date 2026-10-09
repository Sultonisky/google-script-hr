<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_outsource_payslip' => ['View outsource payslips', 'View imported outsource payslips (HKE, salary, deductions, THP).'],
        'manage_outsource_payslip' => ['Manage outsource payslips', 'Download the payslip template and import outsource payslips from Excel.'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        // An empty table makes the catalog repository fall back to the static
        // catalog (which already contains these keys); inserting rows would
        // disable that fallback and hide every other permission.
        if (!DB::table('permissions')->exists()) {
            return;
        }

        $now = now();
        foreach (self::PERMISSIONS as $key => [$name, $description]) {
            if (DB::table('permissions')->where('permission_key', $key)->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                'permission_key' => $key,
                'name' => $name,
                'description' => $description,
                'group' => 'Outsource',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->whereIn('permission_key', array_keys(self::PERMISSIONS))->delete();
    }
};
