<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\PaymentGateway\PaymentGatewayNotConfiguredException;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TransactionService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function generateTransactionNumber(): string
    {
        do {
            $number = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Transaction::query()->where('transaction_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function createCashTransaction(
        User $cashier,
        array $items,
        int $discountAmount = 0,
        int $cashReceived = 0,
        ?string $notes = null,
    ): Transaction {
        if ($items === []) {
            throw new DomainException('Keranjang belanja tidak boleh kosong.');
        }

        if ($discountAmount < 0) {
            throw new DomainException('Diskon tidak boleh bernilai negatif.');
        }

        return DB::transaction(function () use ($cashier, $items, $discountAmount, $cashReceived, $notes): Transaction {
            $prepared = $this->prepareItemsAndTotals($items, $discountAmount, lockForUpdate: true);

            if ($cashReceived < $prepared['total']) {
                throw new DomainException(
                    'Uang yang diterima (Rp '.number_format($cashReceived, 0, ',', '.').') kurang dari total pembayaran (Rp '.number_format($prepared['total'], 0, ',', '.').').'
                );
            }

            $changeAmount = $cashReceived - $prepared['total'];

            $transaction = Transaction::query()->create([
                'transaction_number' => $this->generateTransactionNumber(),
                'user_id' => $cashier->id,
                'subtotal' => $prepared['subtotal'],
                'discount_amount' => $prepared['discount'],
                'total_amount' => $prepared['total'],
                'payment_method' => PaymentMethod::Cash,
                'payment_status' => PaymentStatus::Paid,
                'status' => TransactionStatus::Completed,
                'notes' => $notes,
                'paid_at' => now(),
            ]);

            foreach ($prepared['lines'] as $line) {
                /** @var Product $product */
                $product = $line['product'];

                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit' => $product->unit,
                    'unit_price' => $product->selling_price,
                    'cost_price' => $product->cost_price,
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);

                $this->stockService->adjustStock(
                    product: $product,
                    quantityChange: -$line['quantity'],
                    type: InventoryMovementType::Sale,
                    reason: "Penjualan {$transaction->transaction_number}",
                    user: $cashier,
                    reference: $transaction,
                );
            }

            Payment::query()->create([
                'transaction_id' => $transaction->id,
                'method' => PaymentMethod::Cash,
                'provider' => 'manual',
                'amount' => $prepared['total'],
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            return $transaction->load(['items', 'latestPayment', 'user']);
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function createQrisTransaction(
        User $cashier,
        array $items,
        int $discountAmount = 0,
        ?string $notes = null,
    ): Transaction {
        if ($items === []) {
            throw new DomainException('Keranjang belanja tidak boleh kosong.');
        }

        if ($discountAmount < 0) {
            throw new DomainException('Diskon tidak boleh bernilai negatif.');
        }

        $gateway = $this->gatewayManager->driver();
        if (! $gateway->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException();
        }

        $transaction = DB::transaction(function () use ($cashier, $items, $discountAmount, $notes): Transaction {
            $prepared = $this->prepareItemsAndTotals($items, $discountAmount, lockForUpdate: false);

            $transaction = Transaction::query()->create([
                'transaction_number' => $this->generateTransactionNumber(),
                'user_id' => $cashier->id,
                'subtotal' => $prepared['subtotal'],
                'discount_amount' => $prepared['discount'],
                'total_amount' => $prepared['total'],
                'payment_method' => PaymentMethod::Qris,
                'payment_status' => PaymentStatus::Pending,
                'status' => TransactionStatus::Pending,
                'notes' => $notes,
            ]);

            foreach ($prepared['lines'] as $line) {
                /** @var Product $product */
                $product = $line['product'];

                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit' => $product->unit,
                    'unit_price' => $product->selling_price,
                    'cost_price' => $product->cost_price,
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $transaction;
        });

        try {
            $charge = $gateway->createQrisCharge($transaction);

            Payment::query()->create([
                'transaction_id' => $transaction->id,
                'method' => PaymentMethod::Qris,
                'provider' => $gateway->providerName(),
                'provider_reference' => $charge['provider_reference'],
                'amount' => $transaction->total_amount,
                'qris_string' => $charge['qris_string'],
                'qris_url' => $charge['qris_url'],
                'status' => PaymentStatus::Pending,
                'expires_at' => $charge['expires_at'],
                'raw_response' => $charge['raw_response'],
            ]);
        } catch (Throwable $e) {
            $transaction->update([
                'payment_status' => PaymentStatus::Failed,
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => 'Gagal membuat tagihan QRIS pada payment gateway.',
            ]);

            throw $e;
        }

        return $transaction->load(['items', 'latestPayment', 'user']);
    }

    public function markQrisPaymentPaid(Transaction $transaction, Payment $payment, ?array $rawPayload = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $payment, $rawPayload): Transaction {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Idempotency: jika sudah lunas, jangan kurangi stok lagi
            if ($lockedTx->payment_status === PaymentStatus::Paid) {
                return $lockedTx->load(['items', 'latestPayment', 'user']);
            }

            $lockedTx->load('items');

            foreach ($lockedTx->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                /** @var Product $product */
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                $this->stockService->adjustStock(
                    product: $product,
                    quantityChange: -$item->quantity,
                    type: InventoryMovementType::Sale,
                    reason: "Penjualan QRIS {$lockedTx->transaction_number}",
                    user: $lockedTx->user,
                    reference: $lockedTx,
                );
            }

            $paidAt = now();

            $lockedPayment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $paidAt,
                'raw_response' => $rawPayload ?? $lockedPayment->raw_response,
            ]);

            $lockedTx->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => TransactionStatus::Completed,
                'paid_at' => $paidAt,
            ]);

            return $lockedTx->fresh(['items', 'latestPayment', 'user']);
        });
    }

    public function markQrisPaymentExpiredOrFailed(
        Transaction $transaction,
        Payment $payment,
        PaymentStatus $newStatus,
        ?array $rawPayload = null,
    ): Transaction {
        return DB::transaction(function () use ($transaction, $payment, $newStatus, $rawPayload): Transaction {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedTx->payment_status === PaymentStatus::Paid) {
                return $lockedTx;
            }

            $lockedPayment->update([
                'status' => $newStatus,
                'raw_response' => $rawPayload ?? $lockedPayment->raw_response,
            ]);

            $lockedTx->update([
                'payment_status' => $newStatus,
                'status' => TransactionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => 'Pembayaran QRIS '.$newStatus->label(),
            ]);

            return $lockedTx->fresh(['items', 'latestPayment', 'user']);
        });
    }

    public function checkAndSyncQrisStatus(Transaction $transaction): Transaction
    {
        $payment = $transaction->latestPayment;

        if (! $payment || $transaction->payment_status !== PaymentStatus::Pending) {
            return $transaction;
        }

        if ($payment->expires_at !== null && $payment->expires_at->isPast()) {
            return $this->markQrisPaymentExpiredOrFailed($transaction, $payment, PaymentStatus::Expired);
        }

        $gateway = $this->gatewayManager->driver($payment->provider);
        if (! $gateway->isConfigured()) {
            return $transaction;
        }

        $result = $gateway->checkStatus($payment);

        if ($result['status'] === PaymentStatus::Paid) {
            return $this->markQrisPaymentPaid($transaction, $payment, $result['raw_response']);
        }

        if (in_array($result['status'], [PaymentStatus::Expired, PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
            return $this->markQrisPaymentExpiredOrFailed($transaction, $payment, $result['status'], $result['raw_response']);
        }

        return $transaction;
    }

    public function cancelTransaction(Transaction $transaction, User $actor, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $actor, $reason): Transaction {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (! $lockedTx->canBeCancelled()) {
                throw new DomainException('Transaksi ini tidak dapat dibatalkan.');
            }

            $wasPaid = $lockedTx->payment_status === PaymentStatus::Paid;

            if ($wasPaid) {
                $lockedTx->load('items');
                foreach ($lockedTx->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }
                    $product = Product::query()->withTrashed()->find($item->product_id);
                    if ($product) {
                        $this->stockService->adjustStock(
                            product: $product,
                            quantityChange: $item->quantity,
                            type: InventoryMovementType::In,
                            reason: "Pembatalan transaksi {$lockedTx->transaction_number}: {$reason}",
                            user: $actor,
                            reference: $lockedTx,
                        );
                    }
                }
            }

            $lockedTx->update([
                'status' => TransactionStatus::Cancelled,
                'payment_status' => PaymentStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ]);

            if ($lockedTx->latestPayment) {
                $lockedTx->latestPayment->update(['status' => PaymentStatus::Cancelled]);
            }

            AuditLog::record(
                action: 'transaction.cancelled',
                auditable: $lockedTx,
                oldValues: ['status' => $wasPaid ? 'completed' : 'pending'],
                newValues: ['status' => 'cancelled', 'reason' => $reason],
                description: "Transaksi {$lockedTx->transaction_number} dibatalkan: {$reason}",
                userId: $actor->id,
            );

            return $lockedTx->fresh(['items', 'latestPayment', 'user']);
        });
    }

    public function refundTransaction(Transaction $transaction, User $owner, string $reason, bool $restockItems = true): Refund
    {
        return DB::transaction(function () use ($transaction, $owner, $reason, $restockItems): Refund {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (! $lockedTx->canBeRefunded()) {
                throw new DomainException('Hanya transaksi yang sudah lunas dan selesai yang dapat di-refund.');
            }

            $refund = Refund::query()->create([
                'transaction_id' => $lockedTx->id,
                'user_id' => $owner->id,
                'amount' => $lockedTx->total_amount,
                'reason' => $reason,
                'restock_items' => $restockItems,
            ]);

            if ($restockItems) {
                $lockedTx->load('items');
                foreach ($lockedTx->items as $item) {
                    if (! $item->product_id) {
                        continue;
                    }
                    $product = Product::query()->withTrashed()->find($item->product_id);
                    if ($product) {
                        $this->stockService->adjustStock(
                            product: $product,
                            quantityChange: $item->quantity,
                            type: InventoryMovementType::Refund,
                            reason: "Refund transaksi {$lockedTx->transaction_number}: {$reason}",
                            user: $owner,
                            reference: $refund,
                        );
                    }
                }
            }

            $lockedTx->update([
                'status' => TransactionStatus::Refunded,
                'payment_status' => PaymentStatus::Refunded,
                'cancelled_at' => now(),
                'cancelled_by' => $owner->id,
                'cancel_reason' => "Refund: {$reason}",
            ]);

            if ($lockedTx->latestPayment) {
                $lockedTx->latestPayment->update(['status' => PaymentStatus::Refunded]);
            }

            AuditLog::record(
                action: 'transaction.refunded',
                auditable: $lockedTx,
                oldValues: ['status' => 'completed', 'payment_status' => 'paid'],
                newValues: ['status' => 'refunded', 'amount' => $lockedTx->total_amount, 'reason' => $reason, 'restock' => $restockItems],
                description: "Refund transaksi {$lockedTx->transaction_number} sebesar Rp ".number_format($lockedTx->total_amount, 0, ',', '.'),
                userId: $owner->id,
            );

            return $refund;
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array{lines: array<int, array{product: Product, quantity: int, subtotal: int}>, subtotal: int, discount: int, total: int}
     */
    private function prepareItemsAndTotals(array $items, int $discountAmount, bool $lockForUpdate): array
    {
        $aggregated = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new DomainException('Jumlah barang harus lebih dari 0.');
            }

            $aggregated[$productId] = ($aggregated[$productId] ?? 0) + $qty;
        }

        $query = Product::query()->whereIn('id', array_keys($aggregated));
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $products = $query->get()->keyBy('id');

        $lines = [];
        $subtotal = 0;

        foreach ($aggregated as $productId => $qty) {
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product || ! $product->is_active) {
                throw new DomainException('Produk tidak ditemukan atau sudah dinonaktifkan.');
            }

            if ($product->stock < $qty) {
                throw new DomainException(
                    "Stok {$product->name} tidak mencukupi (tersedia: {$product->stock} {$product->unit})."
                );
            }

            $lineSubtotal = $product->selling_price * $qty;
            $subtotal += $lineSubtotal;

            $lines[] = [
                'product' => $product,
                'quantity' => $qty,
                'subtotal' => $lineSubtotal,
            ];
        }

        if ($discountAmount > $subtotal) {
            throw new DomainException('Diskon tidak boleh melebihi subtotal belanja.');
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'total' => $subtotal - $discountAmount,
        ];
    }
}
