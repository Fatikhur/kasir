<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
    <!-- Kiri: Katalog Produk -->
    <div class="lg:col-span-7 xl:col-span-8 space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <input type="text"
                           wire:model.live.debounce.250ms="search"
                           wire:keydown.enter="addByBarcodeOrSearch"
                           placeholder="Cari nama produk, SKU, atau scan barcode lalu Enter..."
                           class="w-full rounded-lg border border-slate-300 pl-9 pr-4 py-2.5 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Filter Kategori -->
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        wire:click="$set('selectedCategoryId', null)"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $selectedCategoryId === null ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Semua Kategori
                </button>
                @foreach($categories as $category)
                    <button type="button"
                            wire:click="$set('selectedCategoryId', {{ $category->id }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors cursor-pointer {{ $selectedCategoryId === $category->id ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Grid Produk -->
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
            @forelse($products as $product)
                @php
                    $inCartQty = $cart[$product->id]['quantity'] ?? 0;
                    $outOfStock = $product->stock <= 0;
                @endphp
                <button type="button"
                        wire:click="addToCart({{ $product->id }})"
                        @disabled($outOfStock)
                        class="text-left bg-white border rounded-xl p-3 flex flex-col justify-between transition-colors {{ $outOfStock ? 'border-slate-200 opacity-55 cursor-not-allowed' : 'border-slate-200 hover:border-emerald-500 cursor-pointer' }}">
                    <div>
                        <div class="aspect-[4/3] w-full rounded-lg bg-slate-100 mb-2.5 overflow-hidden flex items-center justify-center">
                            @if($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-lg font-semibold text-slate-400">{{ strtoupper(substr($product->name, 0, 2)) }}</span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-400 font-mono">{{ $product->sku }}</p>
                        <h3 class="text-sm font-medium text-slate-900 line-clamp-2 mt-0.5">{{ $product->name }}</h3>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between gap-1">
                        <span class="text-sm font-bold text-emerald-700 tabular-nums">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                        <span class="text-[11px] font-medium px-1.5 py-0.5 rounded {{ $outOfStock ? 'bg-rose-50 text-rose-700' : ($product->isLowStock() ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                            Stok: {{ $product->stock }}
                        </span>
                    </div>

                    @if($inCartQty > 0)
                        <div class="mt-1.5 text-[11px] font-medium text-emerald-700">
                            Di keranjang: {{ $inCartQty }} {{ $product->unit }}
                        </div>
                    @endif
                </button>
            @empty
                <div class="col-span-full bg-white border border-slate-200 rounded-xl p-10 text-center text-sm text-slate-500">
                    Tidak ada produk yang cocok dengan pencarian.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Kanan: Keranjang & Pembayaran -->
    <div class="lg:col-span-5 xl:col-span-4 bg-white border border-slate-200 rounded-xl p-4 lg:sticky lg:top-20 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Keranjang Belanja</h2>
            @if(count($cart) > 0)
                <button type="button" wire:click="clearCart"
                        class="text-xs font-medium text-rose-600 hover:underline cursor-pointer">
                    Kosongkan
                </button>
            @endif
        </div>

        @if($errorMessage)
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs text-rose-700">
                {{ $errorMessage }}
            </div>
        @endif

        <!-- Daftar Item -->
        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
            @forelse($cart as $item)
                <div class="py-2.5 flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $item['name'] }}</p>
                        <p class="text-xs text-slate-500 tabular-nums">
                            Rp {{ number_format($item['price'], 0, ',', '.') }} &times; {{ $item['quantity'] }}
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" wire:click="decrementItem({{ $item['product_id'] }})"
                                class="w-7 h-7 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center justify-center text-sm font-semibold cursor-pointer">-</button>
                        <span class="w-7 text-center text-sm font-medium tabular-nums">{{ $item['quantity'] }}</span>
                        <button type="button" wire:click="incrementItem({{ $item['product_id'] }})"
                                class="w-7 h-7 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center justify-center text-sm font-semibold cursor-pointer">+</button>
                        <button type="button" wire:click="removeItem({{ $item['product_id'] }})" title="Hapus"
                                class="ml-1 p-1 text-slate-400 hover:text-rose-600 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-slate-400">
                    Keranjang masih kosong. Klik produk untuk menambahkan.
                </div>
            @endforelse
        </div>

        <!-- Ringkasan Harga -->
        <div class="pt-3 border-t border-slate-200 space-y-2 text-sm">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal</span>
                <span class="font-medium tabular-nums">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
            </div>

            <div class="flex items-center justify-between gap-3">
                <label for="discountAmount" class="text-slate-600">Diskon (Rp)</label>
                <input id="discountAmount" type="number" min="0" inputmode="numeric" wire:model.live.debounce.300ms="discountAmount"
                       class="w-32 text-right rounded-md border border-slate-300 px-2.5 py-1 text-sm tabular-nums focus:border-emerald-600 outline-none">
            </div>

            <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                <span class="font-semibold text-slate-900">Total Pembayaran</span>
                <span class="text-xl font-bold text-emerald-700 tabular-nums">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Metode Pembayaran -->
        <div class="space-y-3 pt-2 border-t border-slate-200">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Metode Pembayaran</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" wire:click="$set('paymentMethod', 'cash')"
                        class="py-2.5 px-3 rounded-lg border text-sm font-medium transition-colors cursor-pointer {{ $paymentMethod === 'cash' ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                    Tunai
                </button>
                <button type="button" wire:click="$set('paymentMethod', 'qris')"
                        class="py-2.5 px-3 rounded-lg border text-sm font-medium transition-colors cursor-pointer {{ $paymentMethod === 'qris' ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                    QRIS
                </button>
            </div>

            @if($paymentMethod === 'cash')
                <div class="space-y-2.5">
                    <div>
                        <label for="cashReceived" class="block text-xs font-medium text-slate-700 mb-1">Uang Diterima (Rp)</label>
                        <input id="cashReceived" type="number" min="0" inputmode="numeric" wire:model.live.debounce.300ms="cashReceived"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold tabular-nums focus:border-emerald-600 outline-none">
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" wire:click="setExactCash"
                                class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700 cursor-pointer">
                            Uang Pas
                        </button>
                        @foreach([10000, 20000, 50000, 100000] as $nom)
                            <button type="button" wire:click="setCashNominal({{ $nom }})"
                                    class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700 cursor-pointer tabular-nums">
                                {{ number_format($nom / 1000, 0) }}rb
                            </button>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm">
                        <span class="text-slate-600">Kembalian</span>
                        <span class="font-bold tabular-nums {{ (is_numeric($cashReceived) ? (int) $cashReceived : 0) >= $this->total ? 'text-emerald-700' : 'text-rose-600' }}">
                            Rp {{ number_format($this->changeAmount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @elseif($paymentMethod === 'qris')
                @if(!$this->qrisConfigured)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                        <strong>Pembayaran QRIS belum aktif.</strong> Lengkapi credential payment gateway (Midtrans/Xendit) pada file <code>.env</code> untuk mengaktifkan QRIS dinamis.
                    </div>
                @else
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        Kode QRIS dinamis akan dibuat melalui payment gateway ({{ strtoupper(config('payment.provider')) }} - {{ config('payment.mode') }}).
                    </div>
                @endif
            @endif

            <button type="button"
                    wire:click="processCheckout"
                    wire:loading.attr="disabled"
                    @disabled(count($cart) === 0 || ($paymentMethod === 'qris' && !$this->qrisConfigured))
                    class="w-full py-3 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors cursor-pointer">
                <span wire:loading.remove wire:target="processCheckout">
                    {{ $paymentMethod === 'qris' ? 'Buat Tagihan QRIS' : 'Proses Pembayaran Tunai' }}
                </span>
                <span wire:loading wire:target="processCheckout">Memproses...</span>
            </button>
        </div>
    </div>

    <!-- Modal Pembayaran QRIS Menunggu Konfirmasi -->
    @if($activeQrisTransaction)
        <div class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4" wire:poll.4s="checkQrisStatus">
            <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4 border border-slate-200">
                <div class="text-center">
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                        Menunggu Pembayaran QRIS
                    </span>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $activeQrisTransaction->transaction_number }}</h3>
                    <p class="text-2xl font-bold text-emerald-700 tabular-nums mt-1">
                        Rp {{ number_format($activeQrisTransaction->total_amount, 0, ',', '.') }}
                    </p>
                </div>

                <div class="flex justify-center p-4 bg-white border border-slate-200 rounded-xl">
                    @if($qrisSvg)
                        <div class="w-56 h-56">{!! $qrisSvg !!}</div>
                    @elseif($activeQrisTransaction->latestPayment?->qris_url)
                        <img src="{{ $activeQrisTransaction->latestPayment->qris_url }}" alt="QRIS" class="w-56 h-56">
                    @endif
                </div>

                @if($activeQrisTransaction->latestPayment?->expires_at)
                    <p class="text-center text-xs text-slate-500">
                        Berlaku hingga: {{ $activeQrisTransaction->latestPayment->expires_at->format('H:i:s') }} WIB
                    </p>
                @endif

                <div class="flex gap-2">
                    <button type="button" wire:click="checkQrisStatus"
                            class="flex-1 py-2.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                        Cek Status Pembayaran
                    </button>
                    <button type="button" wire:click="cancelPendingQris"
                            class="py-2.5 px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium cursor-pointer">
                        Batalkan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Transaksi Berhasil -->
    @if($completedTransaction)
        <div class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4 border border-slate-200">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Pembayaran Berhasil</h3>
                    <p class="text-sm text-slate-500">{{ $completedTransaction->transaction_number }}</p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Metode</span>
                        <span class="font-medium">{{ $completedTransaction->payment_method->label() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Total</span>
                        <span class="font-bold text-slate-900 tabular-nums">Rp {{ number_format($completedTransaction->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($completedTransaction->payment_method->value === 'cash' && $completedTransaction->latestPayment)
                        <div class="flex justify-between">
                            <span class="text-slate-600">Tunai Diterima</span>
                            <span class="tabular-nums">Rp {{ number_format($completedTransaction->latestPayment->cash_received, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Kembalian</span>
                            <span class="font-bold text-emerald-700 tabular-nums">Rp {{ number_format($completedTransaction->latestPayment->change_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('transactions.receipt', ['transaction' => $completedTransaction, 'paper' => '58']) }}" target="_blank"
                       class="py-2.5 px-3 rounded-lg border border-slate-300 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Struk 58mm
                    </a>
                    <a href="{{ route('transactions.receipt', ['transaction' => $completedTransaction, 'paper' => '80']) }}" target="_blank"
                       class="py-2.5 px-3 rounded-lg border border-slate-300 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Struk 80mm
                    </a>
                </div>

                <button type="button" wire:click="closeSuccessModal"
                        class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold cursor-pointer">
                    Transaksi Baru
                </button>
            </div>
        </div>
    @endif
</div>
