<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ReceiptController;
use App\Livewire\Dashboard;
use App\Livewire\PosTerminal;
use App\Livewire\ProductManager;
use App\Livewire\SalesReport;
use App\Livewire\StockManager;
use App\Livewire\StoreSettingsManager;
use App\Livewire\TransactionHistory;
use App\Livewire\UserAndAuditManager;
use Illuminate\Support\Facades\Route;

// Webhook Payment Gateway (tanpa auth session, diverifikasi dengan signature/token & rate-limited)
Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:qris-webhook')
    ->name('webhooks.payment');

// Guest / Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Authenticated Routes (Owner & Kasir)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/kasir', PosTerminal::class)->name('pos.index');
    Route::get('/transaksi', TransactionHistory::class)->name('transactions.index');
    Route::get('/transaksi/{transaction}/struk', [ReceiptController::class, 'show'])->name('transactions.receipt');

    // Owner-only Routes
    Route::middleware('role:owner')->group(function () {
        Route::get('/produk', ProductManager::class)->name('products.index');
        Route::get('/stok', StockManager::class)->name('stock.index');
        Route::get('/laporan', SalesReport::class)->name('reports.index');
        Route::get('/pengguna', UserAndAuditManager::class)->name('users.index');
        Route::get('/pengaturan', StoreSettingsManager::class)->name('settings.index');
    });
});
