<div class="space-y-5">
    <!-- Toolbar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex flex-col sm:flex-row gap-2.5 flex-1">
            <input type="text" wire:model.live.debounce.250ms="search"
                   placeholder="Cari nama, SKU, atau barcode..."
                   class="w-full sm:w-64 rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-emerald-600 outline-none">

            <select wire:model.live="filterCategoryId"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-emerald-600 outline-none">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-emerald-600 outline-none">
                <option value="all">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('showCategoryModal', true)"
                    class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium cursor-pointer">
                Kategori
            </button>
            <button type="button" wire:click="$set('showImportModal', true)"
                    class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium cursor-pointer">
                Import CSV
            </button>
            <button type="button" wire:click="exportCsv"
                    class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium cursor-pointer">
                Export CSV
            </button>
            <button type="button" wire:click="openCreateModal"
                    class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                + Tambah Produk
            </button>
        </div>
    </div>

    <!-- Tabel Produk -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">SKU / Barcode</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-right">Harga Modal</th>
                        <th class="px-4 py-3 text-right">Harga Jual</th>
                        <th class="px-4 py-3 text-right">Stok</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center shrink-0">
                                        @if($product->image_path)
                                            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xs font-semibold text-slate-400">{{ strtoupper(substr($product->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">Satuan: {{ $product->unit }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-mono text-xs text-slate-800">{{ $product->sku }}</p>
                                <p class="font-mono text-[11px] text-slate-400">{{ $product->barcode ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-600">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                <span class="font-semibold {{ $product->isOutOfStock() ? 'text-rose-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-slate-900') }}">
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="openEditModal({{ $product->id }})"
                                        class="text-xs font-medium text-emerald-700 hover:underline cursor-pointer">Edit</button>
                                <button type="button" wire:click="toggleActive({{ $product->id }})"
                                        class="text-xs font-medium text-slate-600 hover:underline cursor-pointer">
                                    {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Belum ada produk yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Modal Tambah / Edit Produk -->
    @if($showProductModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl max-w-xl w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">{{ $editingProductId ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
                    <button type="button" wire:click="$set('showProductModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <form wire:submit="saveProduct" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Produk *</label>
                            <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">SKU / Kode Barang *</label>
                            <input type="text" wire:model="sku" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                            @error('sku') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Barcode</label>
                            <input type="text" wire:model="barcode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                            @error('barcode') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Kategori</label>
                            <select wire:model="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Satuan *</label>
                            <input type="text" wire:model="unit" placeholder="pcs, botol, dus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Harga Modal (Rp) *</label>
                            <input type="number" min="0" wire:model="cost_price" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Harga Jual (Rp) *</label>
                            <input type="number" min="0" wire:model="selling_price" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                            @error('selling_price') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if(!$editingProductId)
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Stok Awal *</label>
                                <input type="number" min="0" wire:model="stock" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Batas Stok Menipis *</label>
                            <input type="number" min="0" wire:model="min_stock" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Foto Produk (Opsional)</label>
                            <input type="file" wire:model="image" accept="image/*" class="w-full text-sm text-slate-600">
                            @error('image') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600">
                                <span>Produk aktif dan tampil di Kasir</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                        <button type="button" wire:click="$set('showProductModal', false)"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Kategori -->
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">Kelola Kategori</h3>
                    <button type="button" wire:click="$set('showCategoryModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <form wire:submit="saveCategory" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kategori *</label>
                        <input type="text" wire:model="categoryName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('categoryName') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi</label>
                        <input type="text" wire:model="categoryDescription" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                        Simpan Kategori
                    </button>
                </form>

                <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 border-t border-slate-200 pt-2">
                    @foreach($categories as $cat)
                        <div class="py-2 flex justify-between items-center text-sm">
                            <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                            <span class="text-xs text-slate-500">{{ $cat->products()->count() }} produk</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Import CSV -->
    @if($showImportModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">Import Produk dari CSV</h3>
                    <button type="button" wire:click="$set('showImportModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <p class="text-xs text-slate-600">
                    Format kolom CSV: <code>SKU, Barcode, Nama Produk, Kategori, Harga Modal, Harga Jual, Stok, Min Stok, Satuan</code>
                </p>

                <form wire:submit="importCsv" class="space-y-4">
                    <input type="file" wire:model="csvFile" accept=".csv,.txt" class="w-full text-sm text-slate-600">
                    @error('csvFile') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showImportModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-sm cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium cursor-pointer">Import</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
