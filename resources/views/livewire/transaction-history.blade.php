<div class="space-y-5">
    <!-- Filter -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" wire:model.live.debounce.250ms="search"
               placeholder="No. transaksi atau kasir..."
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">

        <input type="date" wire:model.live="dateFrom"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">

        <input type="date" wire:model.live="dateTo"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">

        <select wire:model.live="filterMethod" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
            <option value="">Semua Metode</option>
            @foreach($paymentMethods as $method)
                <option value="{{ $method->value }}">{{ $method->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
            <option value="">Semua Status</option>
            @foreach($paymentStatuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <!-- Tabel Transaksi -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">No. Transaksi</th>
                        <th class="px-4 py-3">Tanggal & Waktu</th>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Status Pembayaran</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900">{{ $tx->transaction_number }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $tx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $tx->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $tx->payment_method->label() }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">
                                Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tx->payment_status->badgeClass() }}">
                                    {{ $tx->payment_status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="showDetail({{ $tx->id }})"
                                        class="text-xs font-medium text-emerald-700 hover:underline cursor-pointer">Detail</button>

                                @if($tx->payment_method->value === 'qris' && $tx->payment_status->value === 'pending')
                                    <button type="button" wire:click="syncPendingQris({{ $tx->id }})"
                                            class="text-xs font-medium text-amber-700 hover:underline cursor-pointer">Cek QRIS</button>
                                @endif

                                @if($tx->payment_status->value === 'paid')
                                    <a href="{{ route('transactions.receipt', ['transaction' => $tx, 'paper' => '80']) }}" target="_blank"
                                       class="text-xs font-medium text-slate-600 hover:underline">Struk</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Belum ada transaksi ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Modal Detail Transaksi -->
    @if($selectedTransaction)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $selectedTransaction->transaction_number }}</h3>
                        <p class="text-xs text-slate-500">{{ $selectedTransaction->created_at->format('d M Y H:i:s') }} &middot; Kasir: {{ $selectedTransaction->user?->name }}</p>
                    </div>
                    <button type="button" wire:click="closeDetail" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                    @foreach($selectedTransaction->items as $item)
                        <div class="py-2 flex justify-between text-sm">
                            <div>
                                <p class="font-medium text-slate-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-slate-500 tabular-nums">{{ $item->quantity }} {{ $item->unit }} &times; Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                            </div>
                            <span class="font-medium tabular-nums text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span class="tabular-nums">Rp {{ number_format($selectedTransaction->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($selectedTransaction->discount_amount > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Diskon</span>
                            <span class="tabular-nums text-rose-600">-Rp {{ number_format($selectedTransaction->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-slate-900 pt-1 border-t border-slate-200">
                        <span>Total</span>
                        <span class="tabular-nums">Rp {{ number_format($selectedTransaction->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 pt-1">
                        <span>Metode / Status</span>
                        <span>{{ $selectedTransaction->payment_method->label() }} &middot; {{ $selectedTransaction->payment_status->label() }}</span>
                    </div>
                </div>

                @if($selectedTransaction->cancel_reason)
                    <div class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
                        <strong>Catatan Pembatalan/Refund:</strong> {{ $selectedTransaction->cancel_reason }}
                    </div>
                @endif

                <div class="flex flex-wrap justify-between gap-2 pt-2 border-t border-slate-200">
                    <div class="flex gap-2">
                        @if($selectedTransaction->payment_status->value === 'paid')
                            <a href="{{ route('transactions.receipt', ['transaction' => $selectedTransaction, 'paper' => '58']) }}" target="_blank"
                               class="px-3 py-2 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Cetak 58mm
                            </a>
                            <a href="{{ route('transactions.receipt', ['transaction' => $selectedTransaction, 'paper' => '80']) }}" target="_blank"
                               class="px-3 py-2 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Cetak 80mm
                            </a>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        @can('cancel', $selectedTransaction)
                            <button type="button" wire:click="openActionModal({{ $selectedTransaction->id }}, 'cancel')"
                                    class="px-3 py-2 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-medium cursor-pointer">
                                Batalkan
                            </button>
                        @endcan
                        @can('refund', $selectedTransaction)
                            <button type="button" wire:click="openActionModal({{ $selectedTransaction->id }}, 'refund')"
                                    class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium cursor-pointer">
                                Refund
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Batalkan / Refund -->
    @if($actionTransactionId)
        <div class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 space-y-4">
                <h3 class="text-base font-semibold text-slate-900">
                    {{ $actionType === 'refund' ? 'Konfirmasi Refund Transaksi' : 'Konfirmasi Pembatalan Transaksi' }}
                </h3>

                @if($errorMessage)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">{{ $errorMessage }}</div>
                @endif

                <form wire:submit="executeAction" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Alasan *</label>
                        <input type="text" wire:model="actionReason" placeholder="Masukkan alasan untuk catatan audit..."
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('actionReason') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($actionType === 'refund')
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="restockItems" class="rounded border-slate-300 text-emerald-600">
                            <span>Kembalikan stok barang ke inventori</span>
                        </label>
                    @endif

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('actionTransactionId', null)" class="px-4 py-2 rounded-lg border border-slate-300 text-sm cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium cursor-pointer">
                            Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
