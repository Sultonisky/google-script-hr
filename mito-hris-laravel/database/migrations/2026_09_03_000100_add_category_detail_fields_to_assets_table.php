<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Category-specific detail columns for Asset Management.
     *
     * All columns are nullable so existing Asset records keep working
     * untouched. Only fields belonging to the active category are used.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // ── Building & Property ─────────────────────────────────
            $table->string('property_type', 50)->nullable();      // Building|Office|Warehouse|Land|Facility|Other
            $table->string('ownership_status', 20)->nullable();   // Owned|Leased|Rented
            $table->decimal('land_area_sqm', 12, 2)->nullable();  // existing `area_sqm` = building area
            $table->integer('floors')->nullable();
            $table->string('certificate_number', 120)->nullable();
            $table->string('maintenance_schedule', 255)->nullable();

            // ── Vehicle & Transport ────────────────────────────────
            $table->string('vin', 100)->nullable();               // VIN / chassis number
            $table->string('engine_number', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('fuel_type', 20)->nullable();          // Petrol|Diesel|Electric|Hybrid
            $table->string('transmission', 20)->nullable();       // Manual|Automatic|CVT
            $table->string('stnk_number', 50)->nullable();
            $table->date('stnk_expiry')->nullable();
            $table->string('bpkb_number', 50)->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('mileage')->nullable();               // km

            // ── Office Equipment ───────────────────────────────────
            $table->string('purchase_warranty', 120)->nullable(); // e.g. "1 Year"
            $table->string('supplier', 255)->nullable();

            // ── Electronics & IT (warranty shared w/ Office) ───────
            $table->string('device_type', 50)->nullable();        // Laptop|Desktop|Monitor|...
            $table->string('processor', 120)->nullable();
            $table->string('ram', 50)->nullable();
            $table->string('storage', 50)->nullable();
            $table->string('storage_type', 20)->nullable();       // SSD|HDD|NVMe
            $table->string('operating_system', 120)->nullable();
            $table->string('mac_address', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname', 120)->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_expiry')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $cols = [
                'property_type', 'ownership_status', 'land_area_sqm', 'floors',
                'certificate_number', 'maintenance_schedule', 'vin', 'engine_number',
                'color', 'fuel_type', 'transmission', 'stnk_number', 'stnk_expiry',
                'bpkb_number', 'last_service_date', 'next_service_date', 'mileage',
                'purchase_warranty', 'supplier', 'device_type', 'processor', 'ram',
                'storage', 'storage_type', 'operating_system', 'mac_address',
                'ip_address', 'hostname', 'warranty_start', 'warranty_expiry',
            ];
            $table->dropColumn($cols);
        });
    }
};
