<div class="space-y-6">
    <!-- Ringkasan KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Penjualan Hari Ini</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $todayTransactionCount }} transaksi lunas</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pendapatan Bulan Ini</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ now()->translatedFormat('F Y') }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Produk Aktif</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalProducts, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Item siap dijual di kasir</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Peringatan Stok</p>
            <div class="mt-2 flex items-baseline gap-3">
                <span class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-slate-900' }} tabular-nums">{{ $lowStockCount }}</span>
                <span class="text-xs text-slate-500">menipis</span>
                <span class="text-slate-300">|</span>
                <span class="text-2xl font-bold {{ $outOfStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }} tabular-nums">{{ $outOfStockCount }}</span>
                <span class="text-xs text-slate-500">habis</span>
            </div>
            @can('manage-stock')
                <a href="{{ route('stock.index') }}" class="mt-1 inline-block text-xs font-medium text-emerald-700 hover:underline">Lihat manajemen stok &rarr;</a>
            @endcan
        </div>
    </div>

    <!-- Grafik 7 Hari & Metode Pembayaran -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Grafik Penjualan 7 Hari Terakhir</h2>
                    <p class="text-xs text-slate-500">Hanya menghitung transaksi dengan status lunas</p>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-3 items-end h-52 pt-4">
                @foreach($dailySales as $day)
                    @php
                        $pct = $day['total'] > 0 ? max(8, (int) round(($day['total'] / $maxDailySale) * 100)) : 2;
                    @endphp
                    <div class="flex flex-col items-center h-full justify-end group">
                        <div class="text-[11px] text-slate-600 font-medium mb-1 opacity-0 group-hover:opacity-100 transition-opacity tabular-nums">
                            {{ number_format($day['total'] / 1000, 0) }}rb
                        </div>
                        <div class="w-full max-w-[40px] rounded-t-md {{ $day['total'] > 0 ? 'bg-emerald-600' : 'bg-slate-200' }} transition-all"
                             style="height: {{ $pct }}%"
                             title="{{ $day['label'] }}: Rp {{ number_format($day['total'], 0, ',', '.') }}">
                        </div>
                        <span class="mt-2 text-xs text-slate-500">{{ $day['short'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Metode Pembayaran (Hari Ini)</h2>
                <p class="text-xs text-slate-500 mb-5">Ringkasan transaksi lunas hari ini</p>

                <div class="space-y-4">
                    <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">Tunai</span>
                            <span class="text-xs text-slate-500">{{ $cashCount }} transaksi</span>
                        </div>
                        <p class="mt-1 text-lg font-bold text-slate-900 tabular-nums">Rp {{ number_format($cashTotal, 0, ',', '.') }}</p>
                    </div>

                    <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">QRIS</span>
                            <span class="text-xs text-slate-500">{{ $qrisCount }} transaksi</span>
                        </div>
                        <p class="mt-1 text-lg font-bold text-slate-900 tabular-nums">Rp {{ number_format($qrisTotal, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <a href="{{ route('pos.index') }}"
               class="mt-5 w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                Mulai Transaksi Baru
            </a>
        </div>
    </div>

    <!-- Produk Terlaris & Transaksi Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Produk Terlaris (30 Hari)</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($topProducts as $item)
                    <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $item->product_name }}</p>
                            <p class="text-xs text-slate-500">SKU: {{ $item->product_sku }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-slate-900 tabular-nums">{{ number_format($item->total_qty, 0, ',', '.') }} terjual</p>
                            <p class="text-xs text-slate-500 tabular-nums">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        Belum ada data penjualan dalam 30 hari terakhir.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Transaksi Terbaru</h2>
                <a href="{{ route('transactions.index') }}" class="text-xs font-medium text-emerald-700 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($recentTransactions as $tx)
                    <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $tx->transaction_number }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $tx->created_at->format('d M Y H:i') }} &middot; {{ $tx->user?->name ?? '-' }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-slate-900 tabular-nums">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</p>
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $tx->payment_status->badgeClass() }}">
                                {{ $tx->payment_status->label() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        Belum ada transaksi yang tercatat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
