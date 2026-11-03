<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_code', 'product_name', 'stock', 'price'];

    protected function casts(): array
    {
        return ['stock' => 'integer', 'price' => 'decimal:2'];
    }

    public const LOW_STOCK_THRESHOLD = 5;

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('product_name', 'ilike', $like)
                ->orWhere('product_code', 'ilike', $like);
        });
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        $sort = $sort ?: 'id';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        return $query->orderBy(ltrim($sort, '-'), $direction)->orderBy('id');
    }
}
