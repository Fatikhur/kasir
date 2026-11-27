<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name',
    'address',
    'phone',
    'email',
    'receipt_footer',
    'low_stock_threshold',
    'qris_enabled',
])]
class StoreSetting extends Model
{
    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'integer',
            'qris_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Toko KasirKita',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'phone' => '0812-3456-7890',
                'receipt_footer' => 'Terima kasih telah berbelanja!',
                'low_stock_threshold' => 5,
                'qris_enabled' => true,
            ]
        );
    }
}
