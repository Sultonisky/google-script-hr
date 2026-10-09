<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\BuildingAsset;
use App\Models\ElectronicsAsset;
use App\Models\OfficeAsset;
use App\Models\VehicleAsset;
use App\Enums\AssetStatus;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class AssetPortalOverviewController extends Controller
{
    public function __invoke(): View
    {
        $models = [
            'Building' => BuildingAsset::class,
            'Vehicle' => VehicleAsset::class,
            'Office' => OfficeAsset::class,
            'Electronics' => ElectronicsAsset::class,
        ];

        $statuses = collect(AssetStatus::cases())
            ->mapWithKeys(fn (AssetStatus $status): array => [$status->value => 0]);
        $categories = [];
        $totalValue = 0.0;
        $recentAssets = collect();
        $visibleCategories = [];

        foreach ($models as $category => $model) {
            if (!Gate::allows('assets.' . strtolower($category) . '.view')) {
                continue;
            }
            $visibleCategories[] = $category;

            $statusCounts = $model::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            $categoryTotal = (int) $statusCounts->sum();

            foreach ($statusCounts as $status => $count) {
                $statuses[$status] = ($statuses[$status] ?? 0) + (int) $count;
            }

            $categories[$category] = [
                'total' => $categoryTotal,
                'statuses' => $statusCounts,
            ];
            $totalValue += (float) $model::query()->sum('purchase_price');

            $recentAssets = $recentAssets->merge(
                $model::query()
                    ->with('activeAssignment')
                    ->latest('updated_at')
                    ->limit(8)
                    ->get()
                    ->each(fn ($asset) => $asset->setAttribute('category_label', $category))
            );
        }

        $recentAssets = $recentAssets->sortByDesc('updated_at')->take(8)->values();
        $stats = [
            'total' => array_sum(array_column($categories, 'total')),
            'total_value' => $totalValue,
            'statuses' => $statuses,
            'categories' => $categories,
        ];

        return view('hr.assets.overview', compact('stats', 'recentAssets', 'visibleCategories'));
    }
}
