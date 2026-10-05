<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outsource_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->index();
            $table->string('outsource_id', 32)->index();
            $table->string('full_name')->nullable();
            $table->string('vendor', 64)->nullable()->index();
            $table->decimal('incentive_amount', 15, 2);
            $table->string('source_file')->nullable();
            $table->string('imported_by')->nullable();
            $table->timestamps();

            $table->unique(['period', 'outsource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outsource_incentives');
    }
};
