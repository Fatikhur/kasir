<div class="max-w-3xl space-y-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Informasi Toko & Struk</h2>
            <p class="text-xs text-slate-500">Informasi ini akan ditampilkan pada header dan footer struk belanja.</p>
        </div>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Toko *</label>
                <input type="text" wire:model="store_name" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
                @error('store_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Alamat Toko</label>
                <textarea wire:model="address" rows="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nomor Telepon</label>
                    <input type="text" wire:model="phone" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Email Toko</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Catatan Kaki Struk (Footer)</label>
                <input type="text" wire:model="receipt_footer" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Batas Default Stok Menipis</label>
                    <input type="number" min="0" wire:model="low_stock_threshold" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm tabular-nums">
                </div>

                <div class="pt-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="qris_enabled" class="rounded border-slate-300 text-emerald-600">
                        <span>Aktifkan opsi pembayaran QRIS</span>
                    </label>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>

    <!-- Status Integrasi Payment Gateway QRIS -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Status Integrasi Payment Gateway QRIS</h2>
                <p class="text-xs text-slate-500">Credential payment gateway hanya disimpan di environment server (<code>.env</code>).</p>
            </div>
            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $qrisConfigured ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20' }}">
                {{ $qrisConfigured ? 'Terhubung' : 'Belum Aktif' }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm bg-slate-50 border border-slate-200 rounded-lg p-4">
            <div>
                <p class="text-xs text-slate-500">Provider Aktif</p>
                <p class="font-semibold text-slate-900">{{ $gatewayProvider }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Mode Lingkungan</p>
                <p class="font-semibold text-slate-900">{{ $gatewayMode }}</p>
            </div>
        </div>

        @if(!$qrisConfigured)
            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3">
                Untuk mengaktifkan pembayaran QRIS dinamis, isi variabel <code>MIDTRANS_SERVER_KEY</code> (atau <code>XENDIT_SECRET_KEY</code> &amp; <code>XENDIT_CALLBACK_TOKEN</code>) pada file <code>.env</code> dan arahkan webhook ke <code>/webhooks/payment/{{ strtolower($gatewayProvider) }}</code>.
            </p>
        @endif
    </div>
</div>
