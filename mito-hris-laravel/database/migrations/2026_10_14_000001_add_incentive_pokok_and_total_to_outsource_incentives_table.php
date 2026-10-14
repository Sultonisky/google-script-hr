<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outsource_incentives')) {
            return;
        }

        Schema::table('outsource_incentives', function (Blueprint $table): void {
            if (! Schema::hasColumn('outsource_incentives', 'incentive_pokok_amount')) {
                $table->decimal('incentive_pokok_amount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('outsource_incentives', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->nullable();
            }
        });

        if (Schema::hasColumn('outsource_incentives', 'umk_amount')) {
            DB::table('outsource_incentives')
                ->whereNull('incentive_pokok_amount')
                ->whereNotNull('umk_amount')
                ->update(['incentive_pokok_amount' => DB::raw('umk_amount')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('outsource_incentives')) {
            return;
        }

        Schema::table('outsource_incentives', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('outsource_incentives', 'incentive_pokok_amount')) {
                $columns[] = 'incentive_pokok_amount';
            }
            if (Schema::hasColumn('outsource_incentives', 'total_amount')) {
                $columns[] = 'total_amount';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
