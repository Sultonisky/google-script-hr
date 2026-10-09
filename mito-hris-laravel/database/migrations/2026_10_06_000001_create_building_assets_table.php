<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_asset_id')->nullable()->unique();
            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('property_type', 50)->nullable();
            $table->string('ownership_status', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('area_sqm', 12, 2)->nullable();
            $table->decimal('land_area_sqm', 12, 2)->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->string('certificate_number', 120)->nullable();
            $table->string('maintenance_schedule')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('condition_status', 30)->default('Good');
            $table->string('status', 30)->default('Available');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_assets');
    }
};
