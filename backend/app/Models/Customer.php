<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['customer_name', 'customer_address', 'gender', 'birth_date', 'phone'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        return $query->where('customer_name', 'ilike', '%'.addcslashes($term, '%_\\').'%');
    }
}
