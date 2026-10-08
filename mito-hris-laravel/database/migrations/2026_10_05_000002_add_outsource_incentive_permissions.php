<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_outsource_incentive' => ['View outsource incentives', 'View imported outsource incentives per period.'],
        'manage_outsource_incentive' => ['Manage outsource incentives', 'Download the incentive template and import outsource incentives from Excel.'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !DB::table('permissions')->exists()) {
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
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('permission_key', array_keys(self::PERMISSIONS))->delete();
        }
    }
};
