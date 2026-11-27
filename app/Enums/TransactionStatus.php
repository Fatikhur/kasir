<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Refund',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Cancelled => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            self::Refunded => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        };
    }
}
