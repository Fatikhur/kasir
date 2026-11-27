<div class="space-y-6">
    <!-- Ringkasan Peringatan Stok -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-amber-800 uppercase">Peringatan Stok Menipis</p>
                <p class="mt-1 text-2xl font-bold text-amber-900 tabular-nums">{{ $lowStockCount }} produk</p>
            </div>
            <button type="button" wire:click="$set('stockFilter', 'low')"
                    class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 text-xs font-medium cursor-pointer">
                Filter Menipis
            </button>
        </div>

        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-rose-800 uppercase">Peringatan Stok Habis</p>
                <p class="mt-1 text-2xl font-bold text-rose-900 tabular-nums">{{ $outOfStockCount }} produk</p>
            </div>
            <button type="button" wire:click="$set('stockFilter', 'out')"
                    class="px-3 py-1.5 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-900 text-xs font-medium cursor-pointer">
                Filter Habis
            </button>
        </div>
    </div>

    <!-- Daftar Stok Produk -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Stok Produk Saat Ini</h2>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" wire:model.live.debounce.250ms="search" placeholder="Cari produk / SKU..."
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-emerald-600">
                <select wire:model.live="stockFilter" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm bg-white">
                    <option value="all">Semua Stok</option>
                    <option value="low">Stok Menipis</option>
                    <option value="out">Stok Habis</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Stok</th>
                        <th class="px-4 py-3 text-right">Min. Stok</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $product->sku }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums {{ $product->isOutOfStock() ? 'text-rose-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-slate-900') }}">
                                {{ $product->stock }} {{ $product->unit }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $product->min_stock }}</td>
                            <td class="px-4 py-3">
                                @if($product->isOutOfStock())
                                    <span class="inline-flex rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">Habis</span>
                                @elseif($product->isLowStock())
                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Menipis</span>
                                @else
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Aman</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="openAdjustModal({{ $product->id }})"
                                        class="px-3 py-1 rounded-md border border-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                                    Sesuaikan Stok
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada data produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Riwayat Perubahan Stok -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Perubahan Stok</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3 text-right">Perubahan</th>
                        <th class="px-4 py-3 text-right">Sebelum &rarr; Sesudah</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($movements as $mov)
                        <tr>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $mov->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $mov->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs font-medium text-slate-700">{{ $mov->type->label() }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums {{ $mov->quantity_change >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                {{ $mov->quantity_change >= 0 ? '+'.$mov->quantity_change : $mov->quantity_change }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-xs text-slate-600">
                                {{ $mov->stock_before }} &rarr; {{ $mov->stock_after }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $mov->reason }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $mov->user?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat perubahan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $movements->links() }}
        </div>
    </div>

    <!-- Modal Penyesuaian Stok -->
    @if($showAdjustModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Penyesuaian Stok</h3>
                        <p class="text-xs text-slate-500">{{ $selectedProductName }} (Stok saat ini: {{ $currentStock }})</p>
                    </div>
                    <button type="button" wire:click="$set('showAdjustModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ $errorMessage }}</div>
                @endif

                <form wire:submit="saveAdjustment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Perubahan *</label>
                        <select wire:model="adjustType" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                            <option value="in">Stok Masuk (+)</option>
                            <option value="out">Stok Keluar (-)</option>
                            <option value="adjustment">Penyesuaian Tambah (+)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Jumlah *</label>
                        <input type="number" min="1" wire:model="adjustQuantity" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                        @error('adjustQuantity') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Alasan Penyesuaian *</label>
                        <input type="text" wire:model="adjustReason" placeholder="Contoh: Restock supplier, barang rusak, stok opname"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('adjustReason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button type="button" wire:click="$set('showAdjustModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-sm cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
