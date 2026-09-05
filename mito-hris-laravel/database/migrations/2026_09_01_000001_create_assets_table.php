<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('asset_code')->nullable()->unique();
            $table->string('category');       // Building | Vehicle | Office | Elektronik
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();

            // Purchase info
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();

            // Condition & status
            $table->string('condition_status')->default('Good');  // Good | Fair | Poor | Damaged
            $table->string('status')->default('Available');       // Available | Assigned | Maintenance | Damaged | Lost | Disposed

            // Location
            $table->string('location')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Category-specific: Vehicle
            $table->string('license_plate')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->integer('year')->nullable();

            // Category-specific: Building
            $table->string('address')->nullable();
            $table->decimal('area_sqm', 10, 2)->nullable();

            // Category-specific: Office / Elektronik
            $table->string('equipment_type')->nullable();

            // Audit
            $table->string('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('status');
            $table->index('serial_number');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
