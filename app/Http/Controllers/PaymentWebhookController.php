<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\PaymentWebhookEvent;
use App\Models\Transaction;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentWebhookController extends Controller
{
    public function handle(
        Request $request,
        string $provider,
        PaymentGatewayManager $gatewayManager,
        TransactionService $transactionService,
    ): JsonResponse {
        if (! in_array($provider, ['midtrans', 'xendit'], true)) {
            return response()->json(['message' => 'Provider tidak didukung.'], 404);
        }

        $payload = $request->all();
        $gateway = $gatewayManager->driver($provider);

        $signatureValid = $gateway->verifyWebhookSignature($request, $payload);
        $parsed = $gateway->parseWebhook($payload);

        if (! $signatureValid) {
            PaymentWebhookEvent::query()->create([
                'provider' => $provider,
                'idempotency_key' => $parsed['idempotency_key'].':invalid:'.uniqid(),
                'order_id' => $parsed['order_id'] ?: null,
                'event_type' => $parsed['event_type'] ?: null,
                'signature_valid' => false,
                'payload' => $payload,
                'status' => 'failed',
                'error_message' => 'Signature atau callback token tidak valid.',
            ]);

            Log::warning('Webhook pembayaran ditolak karena signature tidak valid.', [
                'provider' => $provider,
                'order_id' => $parsed['order_id'],
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $existing = PaymentWebhookEvent::query()
            ->where('idempotency_key', $parsed['idempotency_key'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Webhook sudah diproses sebelumnya (idempotent).'], 200);
        }

        $webhookEvent = PaymentWebhookEvent::query()->create([
            'provider' => $provider,
            'idempotency_key' => $parsed['idempotency_key'],
            'order_id' => $parsed['order_id'],
            'event_type' => $parsed['event_type'],
            'signature_valid' => true,
            'payload' => $payload,
            'status' => 'received',
        ]);

        try {
            $transaction = Transaction::query()
                ->with('latestPayment')
                ->where('transaction_number', $parsed['order_id'])
                ->first();

            if (! $transaction || ! $transaction->latestPayment) {
                $webhookEvent->update([
                    'status' => 'ignored',
                    'error_message' => 'Transaksi tidak ditemukan.',
                    'processed_at' => now(),
                ]);

                return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
            }

            if ($parsed['amount'] > 0 && $parsed['amount'] !== $transaction->total_amount) {
                $webhookEvent->update([
                    'status' => 'failed',
                    'error_message' => 'Nominal pembayaran tidak sesuai dengan total transaksi.',
                    'processed_at' => now(),
                ]);

                return response()->json(['message' => 'Nominal tidak sesuai.'], 422);
            }

            $status = $parsed['status'];
            $payment = $transaction->latestPayment;

            if ($status === PaymentStatus::Paid) {
                $transactionService->markQrisPaymentPaid($transaction, $payment, $payload);
            } elseif (in_array($status, [PaymentStatus::Expired, PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
                $transactionService->markQrisPaymentExpiredOrFailed($transaction, $payment, $status, $payload);
            }

            $webhookEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return response()->json(['message' => 'Webhook berhasil diproses.'], 200);
        } catch (Throwable $e) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            Log::error('Gagal memproses webhook pembayaran.', [
                'provider' => $provider,
                'order_id' => $parsed['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Terjadi kesalahan internal saat memproses webhook.'], 500);
        }
    }
}
