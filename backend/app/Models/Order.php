<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'customer_id', 'created_by', 'order_date', 'total_amount'];

    protected function casts(): array
    {
        return ['order_date' => 'datetime', 'total_amount' => 'decimal:2'];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->where('order_date', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->where('order_date', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['customer_id'] ?? null, fn (Builder $q, string $id) => $q->where('customer_id', $id));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
