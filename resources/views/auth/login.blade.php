<x-guest-layout title="Masuk ke Sistem Kasir">
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xl">
                K
            </div>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">KasirKita POS</h1>
                <p class="text-xs text-slate-500">Masuk untuk memulai operasional kasir</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none"
                       placeholder="nama@toko.id">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors cursor-pointer">
                Masuk
            </button>
        </form>
    </div>
</x-guest-layout>
