<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outsource_incentives') && !Schema::hasColumn('outsource_incentives', 'umk_amount')) {
            Schema::table('outsource_incentives', function (Blueprint $table) {
                $table->decimal('umk_amount', 15, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('outsource_incentives') && Schema::hasColumn('outsource_incentives', 'umk_amount')) {
            Schema::table('outsource_incentives', function (Blueprint $table) {
                $table->dropColumn('umk_amount');
            });
        }
    }
};
