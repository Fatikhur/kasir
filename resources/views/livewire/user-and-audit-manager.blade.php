<div class="space-y-6">
    <!-- Daftar Pengguna -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Manajemen Pengguna</h2>
            <button type="button" wire:click="openCreateModal"
                    class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">
                + Tambah Pengguna
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $user->role->label() }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" wire:click="openEditModal({{ $user->id }})" class="text-xs font-medium text-emerald-700 hover:underline cursor-pointer">Edit</button>
                                @if($user->id !== auth()->id())
                                    <button type="button" wire:click="toggleActive({{ $user->id }})" class="text-xs font-medium text-slate-600 hover:underline cursor-pointer">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Log Audit Aktivitas -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Catatan Aktivitas (Audit Log)</h2>
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
                        <tr>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $log->action }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->description }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada catatan aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $auditLogs->links() }}
        </div>
    </div>

    <!-- Modal Tambah / Edit Pengguna -->
    @if($showUserModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-base font-semibold text-slate-900">{{ $editingUserId ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h3>
                    <button type="button" wire:click="$set('showUserModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <form wire:submit="saveUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Email *</label>
                        <input type="email" wire:model="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Role *</label>
                            <select wire:model="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                                <option value="kasir">Kasir</option>
                                <option value="owner">Pemilik / Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Telepon</label>
                            <input type="text" wire:model="phone" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">
                            Kata Sandi {{ $editingUserId ? '(Kosongkan jika tidak diubah)' : '*' }}
                        </label>
                        <input type="password" wire:model="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600">
                        <span>Akun aktif</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button type="button" wire:click="$set('showUserModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-sm cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium cursor-pointer">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
