<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronics_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_asset_id')->nullable()->unique();
            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('processor', 120)->nullable();
            $table->string('ram', 50)->nullable();
            $table->string('storage', 50)->nullable();
            $table->string('storage_type', 20)->nullable();
            $table->string('operating_system', 120)->nullable();
            $table->string('mac_address', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname', 120)->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('condition_status', 30)->default('Good');
            $table->string('status', 30)->default('Available');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('serial_number');
            $table->index('hostname');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_assets');
    }
};
