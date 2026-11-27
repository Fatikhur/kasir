<?php

namespace App\Livewire;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\PaymentGateway\PaymentGatewayNotConfiguredException;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Kasir / POS')]
class PosTerminal extends Component
{
    public string $search = '';

    public ?int $selectedCategoryId = null;

    /** @var array<int, array{product_id: int, name: string, sku: string, unit: string, price: int, stock: int, quantity: int}> */
    public array $cart = [];

    public int $discountAmount = 0;

    public string $paymentMethod = 'cash';

    public int $cashReceived = 0;

    public string $notes = '';

    public ?string $errorMessage = null;

    // Active QRIS / Completed Transaction state
    public ?int $activeQrisTransactionId = null;

    public ?string $qrisSvg = null;

    public ?int $completedTransactionId = null;

    public function addByBarcodeOrSearch(): void
    {
        $term = trim($this->search);
        if ($term === '') {
            return;
        }

        $product = Product::query()
            ->active()
            ->where(function ($q) use ($term) {
                $q->where('barcode', $term)->orWhere('sku', $term);
            })
            ->first();

        if ($product) {
            $this->addToCart($product->id);
            $this->search = '';
        }
    }

    public function addToCart(int $productId): void
    {
        $this->errorMessage = null;

        /** @var Product|null $product */
        $product = Product::query()->active()->find($productId);

        if (! $product) {
            $this->errorMessage = 'Produk tidak ditemukan atau tidak aktif.';

            return;
        }

        if ($product->stock <= 0) {
            $this->errorMessage = "Stok {$product->name} sedang habis.";

            return;
        }

        $currentQty = $this->cart[$productId]['quantity'] ?? 0;

        if ($currentQty + 1 > $product->stock) {
            $this->errorMessage = "Stok {$product->name} hanya tersisa {$product->stock} {$product->unit}.";

            return;
        }

        $this->cart[$productId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'price' => $product->selling_price,
            'stock' => $product->stock,
            'quantity' => $currentQty + 1,
        ];
    }

    public function incrementItem(int $productId): void
    {
        $this->errorMessage = null;

        if (! isset($this->cart[$productId])) {
            return;
        }

        $product = Product::query()->active()->find($productId);
        $maxStock = $product ? $product->stock : $this->cart[$productId]['stock'];

        if ($this->cart[$productId]['quantity'] + 1 > $maxStock) {
            $this->errorMessage = "Jumlah melebihi stok tersedia ({$maxStock} {$this->cart[$productId]['unit']}).";

            return;
        }

        $this->cart[$productId]['stock'] = $maxStock;
        $this->cart[$productId]['quantity']++;
    }

    public function decrementItem(int $productId): void
    {
        $this->errorMessage = null;

        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantity'] <= 1) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId]['quantity']--;
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->errorMessage = null;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discountAmount = 0;
        $this->cashReceived = 0;
        $this->notes = '';
        $this->errorMessage = null;
    }

    public function setExactCash(): void
    {
        $this->cashReceived = $this->total;
    }

    public function setCashNominal(int $amount): void
    {
        $this->cashReceived = $amount;
    }

    #[Computed]
    public function subtotal(): int
    {
        $sum = 0;
        foreach ($this->cart as $item) {
            $sum += $item['price'] * $item['quantity'];
        }

        return $sum;
    }

    #[Computed]
    public function sanitizedDiscount(): int
    {
        return max(0, min($this->discountAmount, $this->subtotal));
    }

    #[Computed]
    public function total(): int
    {
        return max(0, $this->subtotal - $this->sanitizedDiscount);
    }

    #[Computed]
    public function changeAmount(): int
    {
        return max(0, $this->cashReceived - $this->total);
    }

    #[Computed]
    public function qrisConfigured(): bool
    {
        return StoreSetting::current()->qris_enabled
            && app(PaymentGatewayManager::class)->isQrisConfigured();
    }

    public function processCheckout(TransactionService $transactionService, PaymentGatewayManager $gatewayManager): void
    {
        $this->errorMessage = null;

        if ($this->cart === []) {
            $this->errorMessage = 'Tambahkan minimal satu produk ke keranjang.';

            return;
        }

        $items = array_values(array_map(fn ($item) => [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
        ], $this->cart));

        try {
            if ($this->paymentMethod === 'cash') {
                $tx = $transactionService->createCashTransaction(
                    cashier: Auth::user(),
                    items: $items,
                    discountAmount: $this->sanitizedDiscount,
                    cashReceived: $this->cashReceived,
                    notes: $this->notes ?: null,
                );

                $this->completedTransactionId = $tx->id;
                $this->clearCart();

                return;
            }

            if ($this->paymentMethod === 'qris') {
                if (! StoreSetting::current()->qris_enabled) {
                    $this->errorMessage = 'Pembayaran QRIS sedang dinonaktifkan di pengaturan toko.';

                    return;
                }

                $tx = $transactionService->createQrisTransaction(
                    cashier: Auth::user(),
                    items: $items,
                    discountAmount: $this->sanitizedDiscount,
                    notes: $this->notes ?: null,
                );

                $this->activeQrisTransactionId = $tx->id;
                $qrisString = $tx->latestPayment?->qris_string ?? '';
                $this->qrisSvg = $qrisString !== '' ? $gatewayManager->renderQrSvg($qrisString) : null;
                $this->clearCart();
            }
        } catch (PaymentGatewayNotConfiguredException|DomainException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (Throwable $e) {
            report($e);
            $this->errorMessage = 'Gagal memproses transaksi: '.$e->getMessage();
        }
    }

    public function checkQrisStatus(TransactionService $transactionService): void
    {
        if (! $this->activeQrisTransactionId) {
            return;
        }

        $tx = Transaction::query()->with(['latestPayment', 'items'])->find($this->activeQrisTransactionId);
        if (! $tx) {
            $this->activeQrisTransactionId = null;
            $this->qrisSvg = null;

            return;
        }

        try {
            $tx = $transactionService->checkAndSyncQrisStatus($tx);

            if ($tx->payment_status === PaymentStatus::Paid) {
                $this->activeQrisTransactionId = null;
                $this->qrisSvg = null;
                $this->completedTransactionId = $tx->id;
            } elseif (in_array($tx->payment_status, [PaymentStatus::Expired, PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
                $this->errorMessage = 'Pembayaran QRIS '.$tx->payment_status->label().'.';
                $this->activeQrisTransactionId = null;
                $this->qrisSvg = null;
            }
        } catch (Throwable $e) {
            report($e);
            $this->errorMessage = 'Gagal memeriksa status pembayaran QRIS.';
        }
    }

    public function cancelPendingQris(TransactionService $transactionService): void
    {
        if (! $this->activeQrisTransactionId) {
            return;
        }

        $tx = Transaction::query()->find($this->activeQrisTransactionId);
        if ($tx && $tx->canBeCancelled()) {
            $transactionService->cancelTransaction($tx, Auth::user(), 'Dibatalkan oleh kasir sebelum pembayaran QRIS selesai');
        }

        $this->activeQrisTransactionId = null;
        $this->qrisSvg = null;
    }

    public function closeSuccessModal(): void
    {
        $this->completedTransactionId = null;
    }

    public function render()
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        $products = Product::query()
            ->with('category')
            ->active()
            ->when($this->selectedCategoryId, fn ($q) => $q->where('category_id', $this->selectedCategoryId))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(60)
            ->get();

        $activeQrisTransaction = $this->activeQrisTransactionId
            ? Transaction::query()->with('latestPayment')->find($this->activeQrisTransactionId)
            : null;

        $completedTransaction = $this->completedTransactionId
            ? Transaction::query()->with(['items', 'latestPayment', 'user'])->find($this->completedTransactionId)
            : null;

        return view('livewire.pos-terminal', [
            'categories' => $categories,
            'products' => $products,
            'activeQrisTransaction' => $activeQrisTransaction,
            'completedTransaction' => $completedTransaction,
        ]);
    }
}
