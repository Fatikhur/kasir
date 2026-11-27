<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'product_id',
    'product_name',
    'product_sku',
    'unit',
    'unit_price',
    'cost_price',
    'quantity',
    'subtotal',
])]
class TransactionItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'cost_price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function profit(): int
    {
        return ($this->unit_price - $this->cost_price) * $this->quantity;
    }
}
