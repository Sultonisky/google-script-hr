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
        if (!Schema::hasTable('assets')) {
            return;
        }

        DB::table('assets')->orderBy('id')->chunk(250, function ($legacyAssets): void {
            foreach ($legacyAssets as $legacyAsset) {
                $modelClass = self::CATEGORIES[$legacyAsset->category] ?? null;
                if (!$modelClass) {
                    continue;
                }

                $target = new $modelClass();
                $legacy = (array) $legacyAsset;
                $legacy['legacy_asset_id'] = $legacyAsset->id;
                if ($modelClass === ElectronicsAsset::class) {
                    $legacy['device_type'] = $legacyAsset->equipment_type;
                }
                $data = array_intersect_key($legacy, array_flip(Schema::getColumnListing($target->getTable())));

                DB::table($target->getTable())->insertOrIgnore($data);
            }
        });

        if (!Schema::hasTable('asset_assignments')) {
            return;
        }

        DB::table('asset_assignments')->orderBy('id')->chunk(250, function ($legacyAssignments): void {
            foreach ($legacyAssignments as $legacyAssignment) {
                $legacyAsset = DB::table('assets')->where('id', $legacyAssignment->asset_id)->first();
                $modelClass = $legacyAsset ? (self::CATEGORIES[$legacyAsset->category] ?? null) : null;
                if (!$modelClass) {
                    continue;
                }

                $target = new $modelClass();
                $categoryAsset = DB::table($target->getTable())
                    ->where('legacy_asset_id', $legacyAsset->id)
                    ->first();
                if (!$categoryAsset) {
                    continue;
                }

                DB::table('category_asset_assignments')->insertOrIgnore([
                    'legacy_assignment_id' => $legacyAssignment->id,
                    'asset_type' => $modelClass,
                    'asset_id' => $categoryAsset->id,
                    'employee_id' => $legacyAssignment->employee_id,
                    'employee_name' => $legacyAssignment->employee_name,
                    'assigned_date' => $legacyAssignment->assigned_date,
                    'return_date' => $legacyAssignment->return_date,
                    'assigned_by' => $legacyAssignment->assigned_by,
                    'return_by' => $legacyAssignment->return_by,
                    'assignment_notes' => $legacyAssignment->assignment_notes,
                    'return_notes' => $legacyAssignment->return_notes,
                    'status' => $legacyAssignment->status,
                    'created_at' => $legacyAssignment->created_at,
                    'updated_at' => $legacyAssignment->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('category_asset_assignments')) {
            DB::table('category_asset_assignments')->whereNotNull('legacy_assignment_id')->delete();
        }

        foreach (self::CATEGORIES as $modelClass) {
            $target = new $modelClass();
            $table = $target->getTable();
            DB::table($table)
                ->whereNotNull('legacy_asset_id')
                ->whereNotExists(function ($query) use ($modelClass, $table): void {
                    $query->selectRaw('1')
                        ->from('category_asset_assignments')
                        ->where('asset_type', $modelClass)
                        ->whereColumn('asset_id', $table . '.id')
                        ->whereNull('legacy_assignment_id');
                })
                ->delete();
        }
    }
};
