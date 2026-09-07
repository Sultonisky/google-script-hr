<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->string('product_scope', 255)->nullable()->after('name');
            $table->string('brand', 100)->nullable()->after('product_scope');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->dropColumn(['product_scope', 'brand']);
        });
    }
};