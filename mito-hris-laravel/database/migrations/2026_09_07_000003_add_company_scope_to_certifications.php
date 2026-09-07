<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->string('company_scope', 255)->nullable()->after('brand');
        });

        DB::table('certifications')
            ->whereIn('cert_type', ['ISO', 'K3'])
            ->whereNotNull('product_scope')
            ->update([
                'company_scope' => DB::raw('product_scope'),
                'product_scope' => null,
                'brand' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->dropColumn('company_scope');
        });
    }
};