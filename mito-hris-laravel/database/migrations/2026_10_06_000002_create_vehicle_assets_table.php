<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_asset_id')->nullable()->unique();
            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin', 100)->nullable();
            $table->string('engine_number', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->string('transmission', 20)->nullable();
            $table->string('stnk_number', 50)->nullable();
            $table->date('stnk_expiry')->nullable();
            $table->string('bpkb_number', 50)->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('condition_status', 30)->default('Good');
            $table->string('status', 30)->default('Available');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('license_plate');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_assets');
    }
};
