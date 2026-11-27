<?php

namespace App\Services\PaymentGateway;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransQrisGateway implements PaymentGatewayInterface
{
    public function providerName(): string
    {
        return 'midtrans';
    }

    public function isConfigured(): bool
    {
        return filled(config('payment.midtrans.server_key'));
    }

    private function baseUrl(): string
    {
        return config('payment.mode') === 'production'
            ? config('payment.midtrans.production_url')
            : config('payment.midtrans.sandbox_url');
    }

    private function serverKey(): string
    {
        $key = (string) config('payment.midtrans.server_key');
        if ($key === '') {
            throw new PaymentGatewayNotConfiguredException();
        }

        return $key;
    }

    public function createQrisCharge(Transaction $transaction): array
    {
        $serverKey = $this->serverKey();
        $expiryMinutes = (int) config('payment.qris_expiry_minutes', 15);

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(15)
            ->post($this->baseUrl().'/charge', [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $transaction->transaction_number,
                    'gross_amount' => $transaction->total_amount,
                ],
                'custom_expiry' => [
                    'expiry_duration' => $expiryMinutes,
                    'unit' => 'minute',
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membuat tagihan QRIS ke Midtrans: HTTP '.$response->status());
        }

        $data = $response->json();

        if (! is_array($data) || ! in_array((string) ($data['status_code'] ?? ''), ['200', '201'], true)) {
            throw new RuntimeException('Midtrans menolak permintaan QRIS: '.($data['status_message'] ?? 'Unknown error'));
        }

        $qrisUrl = null;
        foreach ($data['actions'] ?? [] as $action) {
            if (($action['name'] ?? '') === 'generate-qr-code') {
                $qrisUrl = $action['url'] ?? null;
                break;
            }
        }

        $qrisString = (string) ($data['qr_string'] ?? $qrisUrl ?? '');
        if ($qrisString === '') {
            throw new RuntimeException('Midtrans tidak mengembalikan data QRIS.');
        }

        $expiresAt = isset($data['expiry_time'])
            ? Carbon::parse($data['expiry_time'])
            : now()->addMinutes($expiryMinutes);

        return [
            'provider_reference' => (string) ($data['transaction_id'] ?? $transaction->transaction_number),
            'qris_string' => $qrisString,
            'qris_url' => $qrisUrl,
            'expires_at' => $expiresAt,
            'raw_response' => $data,
        ];
    }

    public function checkStatus(Payment $payment): array
    {
        $serverKey = $this->serverKey();
        $orderId = $payment->transaction->transaction_number;

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(15)
            ->get($this->baseUrl()."/{$orderId}/status");

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengecek status QRIS Midtrans: HTTP '.$response->status());
        }

        $data = $response->json();

        return [
            'status' => $this->mapStatus((string) ($data['transaction_status'] ?? 'pending')),
            'raw_response' => is_array($data) ? $data : [],
        ];
    }

    public function verifyWebhookSignature(Request $request, array $payload): bool
    {
        $serverKey = (string) config('payment.midtrans.server_key');
        if ($serverKey === '') {
            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signatureKey);
    }

    public function parseWebhook(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $txStatus = (string) ($payload['transaction_status'] ?? 'pending');
        $txId = (string) ($payload['transaction_id'] ?? '');

        return [
            'idempotency_key' => "midtrans:{$orderId}:{$txId}:{$txStatus}",
            'order_id' => $orderId,
            'event_type' => $txStatus,
            'status' => $this->mapStatus($txStatus),
            'amount' => (int) round((float) ($payload['gross_amount'] ?? 0)),
        ];
    }

    private function mapStatus(string $midtransStatus): PaymentStatus
    {
        return match ($midtransStatus) {
            'settlement', 'capture' => PaymentStatus::Paid,
            'expire' => PaymentStatus::Expired,
            'cancel' => PaymentStatus::Cancelled,
            'deny', 'failure' => PaymentStatus::Failed,
            'refund', 'partial_refund' => PaymentStatus::Refunded,
            default => PaymentStatus::Pending,
        };
    }
}
