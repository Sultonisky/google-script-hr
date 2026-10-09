<?php

use App\Models\BuildingAsset;
use App\Models\ElectronicsAsset;
use App\Models\OfficeAsset;
use App\Models\VehicleAsset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORIES = [
        'Building' => BuildingAsset::class,
        'Vehicle' => VehicleAsset::class,
        'Office' => OfficeAsset::class,
        'Elektronik' => ElectronicsAsset::class,
    ];

    public function up(): void
    {
        $hasAssets = Schema::hasTable('assets');
        $hasAssignments = Schema::hasTable('asset_assignments');
        if (!$hasAssets && !$hasAssignments) {
            return;
        }
        if (!$hasAssets || !$hasAssignments) {
            throw new RuntimeException('Legacy asset tables are incomplete; refusing to drop remaining data.');
        }

        $this->assertAllLegacyAssetsWereImported();
        $this->assertAllLegacyAssignmentsWereImported();

        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }

    private function assertAllLegacyAssetsWereImported(): void
    {
        if (DB::table('assets')->whereNotIn('category', array_keys(self::CATEGORIES))->exists()) {
            throw new RuntimeException('Legacy assets contain an unsupported category; refusing to drop source tables.');
        }

        foreach (self::CATEGORIES as $category => $modelClass) {
            $table = (new $modelClass())->getTable();
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("The {$table} category table is missing; refusing to drop source tables.");
            }

            $unimported = DB::table('assets')
                ->where('category', $category)
                ->whereNotExists(function ($query) use ($table): void {
                    $query->selectRaw('1')
                        ->from($table)
                        ->whereColumn($table . '.legacy_asset_id', 'assets.id');
                })
                ->exists();

            if ($unimported) {
                throw new RuntimeException("Some {$category} assets have not been imported; refusing to drop source tables.");
            }
        }
    }

    private function assertAllLegacyAssignmentsWereImported(): void
    {
        if (!Schema::hasTable('category_asset_assignments')) {
            throw new RuntimeException('The category asset assignment table is missing; refusing to drop source tables.');
        }

        $unimported = DB::table('asset_assignments')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('category_asset_assignments')
                    ->whereColumn('category_asset_assignments.legacy_assignment_id', 'asset_assignments.id');
            })
            ->exists();

        if ($unimported) {
            throw new RuntimeException('Some legacy asset assignments have not been imported; refusing to drop source tables.');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Legacy asset tables were permanently dropped and cannot be restored by rolling back this migration.');
    }
};
