<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function providerName(): string;

    public function isConfigured(): bool;

    /**
     * @return array{provider_reference: string, qris_string: string, qris_url: ?string, expires_at: \Carbon\Carbon, raw_response: array}
     */
    public function createQrisCharge(Transaction $transaction): array;

    /**
     * @return array{status: PaymentStatus, raw_response: array}
     */
    public function checkStatus(Payment $payment): array;

    public function verifyWebhookSignature(Request $request, array $payload): bool;

    /**
     * @return array{idempotency_key: string, order_id: string, event_type: string, status: PaymentStatus, amount: int}
     */
    public function parseWebhook(array $payload): array;
}
