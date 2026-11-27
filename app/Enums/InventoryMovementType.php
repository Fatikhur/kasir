<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stok Masuk',
            self::Out => 'Stok Keluar',
            self::Adjustment => 'Penyesuaian',
            self::Sale => 'Penjualan',
            self::Refund => 'Pengembalian (Refund)',
        };
    }
}
