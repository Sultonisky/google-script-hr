<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

abstract class CategoryAsset extends Model
{
    protected $guarded = ['id'];
    protected array $searchableColumns = ['asset_code', 'name', 'location'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'condition_status' => AssetCondition::class,
            'status' => AssetStatus::class,
        ];
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(CategoryAssetAssignment::class, 'asset');
    }

    public function activeAssignment(): MorphOne
    {
        return $this->morphOne(CategoryAssetAssignment::class, 'asset')->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $term = '%' . strtolower($search) . '%';

        return $query->where(function (Builder $query) use ($term) {
            foreach ($this->searchableColumns as $column) {
                $query->orWhereRaw("LOWER({$column}) LIKE ?", [$term]);
            }
        });
    }
}
