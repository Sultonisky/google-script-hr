<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outsource_incentives')) {
            return;
        }

        Schema::table('outsource_incentives', function (Blueprint $table): void {
            if (! Schema::hasColumn('outsource_incentives', 'promotor_product_race_amount')) {
                $table->decimal('promotor_product_race_amount', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('outsource_incentives')) {
            return;
        }

        Schema::table('outsource_incentives', function (Blueprint $table): void {
            if (Schema::hasColumn('outsource_incentives', 'promotor_product_race_amount')) {
                $table->dropColumn('promotor_product_race_amount');
            }
        });
    }
};
