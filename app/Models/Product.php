<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'name',
    'sku',
    'barcode',
    'cost_price',
    'selling_price',
    'stock',
    'min_stock',
    'unit',
    'image_path',
    'is_active',
])]
class Product extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'selling_price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0);
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function profitMargin(): int
    {
        return $this->selling_price - $this->cost_price;
    }
}
