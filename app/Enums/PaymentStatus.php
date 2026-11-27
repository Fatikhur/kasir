<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Dikembalikan (Refund)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Failed => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            self::Expired => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            self::Cancelled => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            self::Refunded => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        };
    }
}
