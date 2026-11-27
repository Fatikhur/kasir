<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'KasirKita POS' }} — {{ \App\Models\StoreSetting::current()->store_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-screen flex">
    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" style="display: none;"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:shrink-0">
        <div class="h-16 shrink-0 flex items-center gap-3 px-5 border-b border-slate-200">
            <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-lg">
                K
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-sm text-slate-900 truncate">{{ \App\Models\StoreSetting::current()->store_name }}</p>
                <p class="text-xs text-slate-500">Sistem Kasir POS</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5 text-sm">
            @php
                $navGroups = [
                    [
                        'label' => 'Operasional',
                        'items' => [
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'gate' => null, 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['route' => 'pos.index', 'label' => 'Kasir / POS', 'gate' => null, 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                            ['route' => 'transactions.index', 'label' => 'Riwayat Transaksi', 'gate' => null, 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ],
                    ],
                    [
                        'label' => 'Inventori & Laporan',
                        'items' => [
                            ['route' => 'products.index', 'label' => 'Manajemen Produk', 'gate' => 'manage-products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                            ['route' => 'stock.index', 'label' => 'Manajemen Stok', 'gate' => 'manage-stock', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
                            ['route' => 'reports.index', 'label' => 'Laporan Penjualan', 'gate' => 'manage-reports', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ],
                    ],
                    [
                        'label' => 'Administrasi',
                        'items' => [
                            ['route' => 'users.index', 'label' => 'Pengguna & Audit', 'gate' => 'manage-users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                            ['route' => 'settings.index', 'label' => 'Pengaturan Toko', 'gate' => 'manage-settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                        ],
                    ],
                ];
            @endphp

            @foreach($navGroups as $groupIndex => $group)
                @php
                    $visibleItems = array_filter($group['items'], fn ($item) => $item['gate'] === null || auth()->user()->can($item['gate']));
                @endphp

                @if(count($visibleItems) > 0)
                    <div class="{{ $groupIndex > 0 ? 'pt-4 border-t border-slate-100' : '' }}">
                        <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ $group['label'] }}
                        </p>
                        <div class="space-y-1">
                            @foreach($visibleItems as $item)
                                @php $active = request()->routeIs($item['route']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition-colors {{ $active ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <svg class="w-5 h-5 shrink-0 {{ $active ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                    </svg>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="p-4 shrink-0 border-t border-slate-200 bg-slate-50/70">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Header -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" type="button"
                        class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
            </div>

            <div class="flex items-center gap-3">
                @if(!request()->routeIs('pos.index'))
                    <a href="{{ route('pos.index') }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buka Kasir</span>
                    </a>
                @endif
            </div>
        </header>

        <!-- Page Body -->
        <main class="flex-1 p-4 lg:p-6">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
