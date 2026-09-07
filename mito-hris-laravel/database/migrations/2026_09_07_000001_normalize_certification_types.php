<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('certifications')) {
            return;
        }

        // Preserve old records while moving them into the current classification contract.
        DB::table('certifications')
            ->whereIn('cert_type', ['Professional', 'External Training'])
            ->update(['cert_type' => 'ISO']);
        DB::table('certifications')
            ->whereIn('cert_type', ['Internal Training', 'Compliance'])
            ->update(['cert_type' => 'K3']);
        DB::table('certifications')
            ->whereIn('cert_type', ['License', 'Other'])
            ->update(['cert_type' => 'SNI']);
    }

    public function down(): void
    {
        // The old categories were not a lossless classification contract;
        // reverting them would invent inaccurate values.
    }
};