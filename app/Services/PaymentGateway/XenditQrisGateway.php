<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditQrisGateway implements PaymentGatewayInterface
{
    public function providerName(): string
    {
        return 'xendit';
    }

    public function isConfigured(): bool
    {
        return filled(config('payment.xendit.secret_key'))
            && filled(config('payment.xendit.callback_token'));
    }

    private function secretKey(): string
    {
        $key = (string) config('payment.xendit.secret_key');
        if ($key === '') {
            throw new PaymentGatewayNotConfiguredException();
        }

        return $key;
    }

    public function createQrisCharge(Transaction $transaction): array
    {
        $secretKey = $this->secretKey();
        $expiryMinutes = (int) config('payment.qris_expiry_minutes', 15);
        $expiresAt = now()->addMinutes($expiryMinutes);

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders(['api-version' => '2022-07-31'])
            ->acceptJson()
            ->timeout(15)
            ->post(config('payment.xendit.base_url').'/qr_codes', [
                'reference_id' => $transaction->transaction_number,
                'type' => 'DYNAMIC',
                'currency' => 'IDR',
                'amount' => $transaction->total_amount,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat QRIS Xendit: HTTP '.$response->status());
        }

        $data = $response->json();
        $qrString = (string) ($data['qr_string'] ?? '');

        if ($qrString === '') {
            throw new RuntimeException('Xendit tidak mengembalikan qr_string.');
        }

        return [
            'provider_reference' => (string) ($data['id'] ?? $transaction->transaction_number),
            'qris_string' => $qrString,
            'qris_url' => null,
            'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : $expiresAt,
            'raw_response' => is_array($data) ? $data : [],
        ];
    }

    public function checkStatus(Payment $payment): array
    {
        $secretKey = $this->secretKey();
        $ref = $payment->provider_reference ?: $payment->transaction->transaction_number;

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders(['api-version' => '2022-07-31'])
            ->acceptJson()
            ->timeout(15)
            ->get(config('payment.xendit.base_url')."/qr_codes/{$ref}");

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengecek status QRIS Xendit: HTTP '.$response->status());
        }

        $data = $response->json();

        return [
            'status' => $this->mapStatus((string) ($data['status'] ?? 'ACTIVE')),
            'raw_response' => is_array($data) ? $data : [],
        ];
    }

    public function verifyWebhookSignature(Request $request, array $payload): bool
    {
        $expectedToken = (string) config('payment.xendit.callback_token');
        $receivedToken = (string) $request->header('x-callback-token', '');

        if ($expectedToken === '' || $receivedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $receivedToken);
    }

    public function parseWebhook(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $event = (string) ($payload['event'] ?? 'qr.payment');
        $orderId = (string) ($data['reference_id'] ?? '');
        $paymentId = (string) ($data['id'] ?? '');
        $status = (string) ($data['status'] ?? 'SUCCEEDED');

        return [
            'idempotency_key' => "xendit:{$orderId}:{$paymentId}:{$event}:{$status}",
            'order_id' => $orderId,
            'event_type' => $event,
            'status' => $this->mapStatus($status),
            'amount' => (int) ($data['amount'] ?? 0),
        ];
    }

    private function mapStatus(string $xenditStatus): PaymentStatus
    {
        return match (strtoupper($xenditStatus)) {
            'SUCCEEDED', 'COMPLETED', 'PAID' => PaymentStatus::Paid,
            'EXPIRED', 'INACTIVE' => PaymentStatus::Expired,
            'FAILED' => PaymentStatus::Failed,
            'CANCELLED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };
    }
}
