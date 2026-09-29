<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KEY = 'open_spreadsheet';

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        // An empty table makes the catalog repository fall back to the static
        // catalog (which already contains this key); inserting a single row
        // would disable that fallback and hide every other permission.
        if (!DB::table('permissions')->exists()) {
            return;
        }

        if (DB::table('permissions')->where('permission_key', self::KEY)->exists()) {
            return;
        }

        $now = now();
        DB::table('permissions')->insert([
            'permission_key' => self::KEY,
            'name' => 'Open spreadsheet archive',
            'description' => 'Open the Google Sheets archive (database mirror) from the dashboard quick actions.',
            'group' => 'Settings',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->where('permission_key', self::KEY)->delete();
    }
};
