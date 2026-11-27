<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'method',
    'provider',
    'provider_reference',
    'amount',
    'cash_received',
    'change_amount',
    'qris_string',
    'qris_url',
    'status',
    'expires_at',
    'paid_at',
    'raw_response',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'cash_received' => 'integer',
            'change_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::Expired
            || ($this->status === PaymentStatus::Pending && $this->expires_at !== null && $this->expires_at->isPast());
    }
}
