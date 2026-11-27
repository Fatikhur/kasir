<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'provider',
    'idempotency_key',
    'order_id',
    'event_type',
    'signature_valid',
    'payload',
    'status',
    'error_message',
    'processed_at',
])]
class PaymentWebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
