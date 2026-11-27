<div class="space-y-5">
    <!-- Filter Audit Log -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" wire:model.live.debounce.250ms="search"
               placeholder="Cari pengguna, aktivitas, keterangan, IP..."
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">

        <select wire:model.live="filterAction" class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
            <option value="">Semua Aktivitas</option>
            @foreach($actions as $act)
                <option value="{{ $act }}">{{ $act }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="dateFrom"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">

        <input type="date" wire:model.live="dateTo"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-emerald-600">
    </div>

    <!-- Tabel Audit Log -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Catatan Aktivitas (Audit Log)</h2>
            <p class="text-xs text-slate-500">Rekaman aktivitas penting pengguna, perubahan harga, stok, dan transaksi.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $log->action }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->description }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada catatan aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-200">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
