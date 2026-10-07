<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_asset_id')->nullable()->unique();
            $table->string('asset_code')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('equipment_type', 80)->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('supplier')->nullable();
            $table->string('purchase_warranty', 120)->nullable();
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
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_assets');
    }
};
