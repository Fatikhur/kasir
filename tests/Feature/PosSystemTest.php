<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Livewire\SalesReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentGateway\PaymentGatewayNotConfiguredException;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $kasir;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $this->kasir = User::factory()->create(['role' => UserRole::Kasir, 'is_active' => true]);

        $category = Category::query()->create(['name' => 'Minuman', 'slug' => 'minuman', 'is_active' => true]);

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Air Mineral 600ml',
            'sku' => 'MIN-001',
            'barcode' => '8990001000011',
            'cost_price' => 2000,
            'selling_price' => 5000,
            'stock' => 10,
            'min_stock' => 3,
            'unit' => 'botol',
            'is_active' => true,
        ]);
    }

    public function test_cash_transaction_computes_change_and_deducts_stock(): void
    {
        $service = app(TransactionService::class);

        $tx = $service->createCashTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 3]],
            discountAmount: 1000,
            cashReceived: 20000,
        );

        $this->assertEquals(15000, $tx->subtotal);
        $this->assertEquals(1000, $tx->discount_amount);
        $this->assertEquals(14000, $tx->total_amount);
        $this->assertEquals(PaymentStatus::Paid, $tx->payment_status);
        $this->assertEquals(TransactionStatus::Completed, $tx->status);
        $this->assertEquals(6000, $tx->latestPayment->change_amount);
        $this->assertEquals(7, $this->product->fresh()->stock);
    }

    public function test_cash_transaction_rejects_insufficient_cash_and_prevents_negative_stock(): void
    {
        $service = app(TransactionService::class);

        $this->expectException(DomainException::class);
        $service->createCashTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 2]],
            cashReceived: 5000,
        );

        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_prevents_selling_more_than_available_stock(): void
    {
        $service = app(TransactionService::class);

        $this->expectException(DomainException::class);
        $service->createCashTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 15]],
            cashReceived: 100000,
        );
    }

    public function test_qris_throws_when_gateway_credentials_not_configured(): void
    {
        config(['payment.provider' => 'midtrans', 'payment.midtrans.server_key' => '']);

        $service = app(TransactionService::class);

        $this->expectException(PaymentGatewayNotConfiguredException::class);
        $service->createQrisTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 1]],
        );
    }

    public function test_qris_creation_webhook_idempotency_and_stock_deduction(): void
    {
        config([
            'payment.provider' => 'midtrans',
            'payment.mode' => 'sandbox',
            'payment.midtrans.server_key' => 'SB-Mid-server-TESTKEY',
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
                'status_code' => '201',
                'status_message' => 'QRIS created',
                'transaction_id' => 'mid-tx-12345',
                'qr_string' => '00020101021226680016ID.CO.QRIS.WWW0215ID10200300400505204549953033605802ID',
                'expiry_time' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
            ], 201),
        ]);

        $service = app(TransactionService::class);
        $tx = $service->createQrisTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 2]],
        );

        // Sebelum webhook diterima, transaksi masih pending & stok belum berkurang
        $this->assertEquals(PaymentStatus::Pending, $tx->payment_status);
        $this->assertEquals(10, $this->product->fresh()->stock);

        $orderId = $tx->transaction_number;
        $statusCode = '200';
        $grossAmount = '10000.00';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.'SB-Mid-server-TESTKEY');

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_id' => 'mid-tx-12345',
            'transaction_status' => 'settlement',
            'signature_key' => $signature,
        ];

        // Webhook pertama: berhasil -> status paid & stok berkurang 2
        $this->postJson('/webhooks/payment/midtrans', $payload)->assertOk();

        $this->assertEquals(PaymentStatus::Paid, $tx->fresh()->payment_status);
        $this->assertEquals(TransactionStatus::Completed, $tx->fresh()->status);
        $this->assertEquals(8, $this->product->fresh()->stock);

        // Webhook duplikat: tetap 200 OK tetapi stok TIDAK berkurang dua kali
        $this->postJson('/webhooks/payment/midtrans', $payload)->assertOk();
        $this->assertEquals(8, $this->product->fresh()->stock);

        // Webhook dengan signature palsu ditolak 403
        $badPayload = array_merge($payload, ['signature_key' => 'invalid-signature', 'transaction_status' => 'expire']);
        $this->postJson('/webhooks/payment/midtrans', $badPayload)->assertForbidden();
    }

    public function test_qris_expired_webhook_cancels_transaction_without_deducting_stock(): void
    {
        config([
            'payment.provider' => 'midtrans',
            'payment.mode' => 'sandbox',
            'payment.midtrans.server_key' => 'SB-Mid-server-TESTKEY',
        ]);

        Http::fake([
            'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
                'status_code' => '201',
                'transaction_id' => 'mid-tx-exp',
                'qr_string' => 'QR-EXP-STRING',
                'expiry_time' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
            ], 201),
        ]);

        $service = app(TransactionService::class);
        $tx = $service->createQrisTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 2]],
        );

        $orderId = $tx->transaction_number;
        $statusCode = '202';
        $grossAmount = '10000.00';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.'SB-Mid-server-TESTKEY');

        $this->postJson('/webhooks/payment/midtrans', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_id' => 'mid-tx-exp',
            'transaction_status' => 'expire',
            'signature_key' => $signature,
        ])->assertOk();

        $this->assertEquals(PaymentStatus::Expired, $tx->fresh()->payment_status);
        $this->assertEquals(TransactionStatus::Cancelled, $tx->fresh()->status);
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_cancel_and_refund_restock_items_and_sales_report_excludes_non_paid(): void
    {
        $service = app(TransactionService::class);

        // 1 transaksi tunai lunas (total 10.000, modal 4.000, laba 6.000)
        $paidTx = $service->createCashTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 2]],
            cashReceived: 10000,
        );

        // 1 transaksi tunai yang kemudian dibatalkan (harus mengembalikan stok dan tidak masuk laporan)
        $cancelledTx = $service->createCashTransaction(
            cashier: $this->kasir,
            items: [['product_id' => $this->product->id, 'quantity' => 3]],
            cashReceived: 15000,
        );
        $service->cancelTransaction($cancelledTx, $this->owner, 'Salah input kasir');

        // 1 transaksi pending (tidak boleh masuk laporan)
        Transaction::query()->create([
            'transaction_number' => 'TRX-PENDING-001',
            'user_id' => $this->kasir->id,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'payment_method' => PaymentMethod::Qris,
            'payment_status' => PaymentStatus::Pending,
            'status' => TransactionStatus::Pending,
        ]);

        // Stok akhir: 10 - 2 - 3 + 3 = 8
        $this->assertEquals(8, $this->product->fresh()->stock);

        // Laporan penjualan hanya menghitung transaksi lunas ($paidTx = 10.000)
        $this->actingAs($this->owner);
        Livewire::test(SalesReport::class)
            ->assertViewHas('totalSales', 10000)
            ->assertViewHas('totalTransactions', 1)
            ->assertViewHas('estimatedProfit', 6000);

        // Refund transaksi lunas mengembalikan stok menjadi 10
        $service->refundTransaction($paidTx->fresh(), $this->owner, 'Barang dikembalikan pelanggan', true);
        $this->assertEquals(10, $this->product->fresh()->stock);
        $this->assertEquals(TransactionStatus::Refunded, $paidTx->fresh()->status);
    }
}
