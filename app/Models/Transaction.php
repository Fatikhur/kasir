<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'transaction_number',
    'user_id',
    'subtotal',
    'discount_amount',
    'total_amount',
    'payment_method',
    'payment_status',
    'status',
    'notes',
    'paid_at',
    'cancelled_at',
    'cancelled_by',
    'cancel_reason',
])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'status' => TransactionStatus::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Paid->value)
            ->where('status', TransactionStatus::Completed->value);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [TransactionStatus::Pending, TransactionStatus::Completed], true);
    }

    public function canBeRefunded(): bool
    {
        return $this->payment_status === PaymentStatus::Paid
            && $this->status === TransactionStatus::Completed;
    }
}
