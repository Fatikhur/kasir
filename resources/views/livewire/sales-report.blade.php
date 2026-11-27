<div class="space-y-6">
    <!-- Filter Periode -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            @foreach(['today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $key => $label)
                <button type="button" wire:click="applyPeriod('{{ $key }}')"
                        class="px-3.5 py-2 rounded-lg text-sm font-medium cursor-pointer {{ $period === $key ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $label }}
                </button>
            @endforeach

            <div class="flex items-center gap-2 ml-1">
                <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <span class="text-slate-400 text-sm">s/d</span>
                <input type="date" wire:model.live="dateTo" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
            </div>
        </div>

        <button type="button" wire:click="exportCsv"
                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium cursor-pointer">
            Export CSV
        </button>
    </div>

    <!-- KPI Laporan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase">Total Penjualan (Lunas)</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">Rp {{ number_format($totalSales, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase">Estimasi Keuntungan</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">Rp {{ number_format($estimatedProfit, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase">Jumlah Transaksi</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <p class="text-xs font-medium text-slate-500 uppercase">Total Barang Terjual</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalItemsSold, 0, ',', '.') }} item</p>
        </div>
    </div>

    <!-- Metode Pembayaran & Kategori -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Penjualan per Metode Pembayaran</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($byMethod as $row)
                    <div class="px-5 py-3.5 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-slate-900">{{ $row->payment_method->label() }}</p>
                            <p class="text-xs text-slate-500">{{ $row->tx_count }} transaksi</p>
                        </div>
                        <span class="font-bold text-slate-900 tabular-nums">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Belum ada transaksi lunas pada periode ini.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Penjualan per Kategori</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($byCategory as $cat)
                    <div class="px-5 py-3.5 flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-slate-900">{{ $cat->category_name }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($cat->total_qty, 0, ',', '.') }} item terjual</p>
                        </div>
                        <span class="font-bold text-slate-900 tabular-nums">Rp {{ number_format($cat->total_sales, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Belum ada data penjualan kategori.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Produk Terlaris Periode Ini</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Qty Terjual</th>
                        <th class="px-4 py-3 text-right">Total Penjualan</th>
                        <th class="px-4 py-3 text-right">Estimasi Laba</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($topProducts as $item)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $item->product_sku }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($item->total_qty, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-700 tabular-nums">Rp {{ number_format($item->total_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data produk terjual pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
