<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow();

        $todayPaidQuery = Transaction::query()
            ->paid()
            ->whereBetween('paid_at', [$todayStart, $todayEnd]);

        $todayRevenue = (int) (clone $todayPaidQuery)->sum('total_amount');
        $todayTransactionCount = (int) (clone $todayPaidQuery)->count();

        $monthRevenue = (int) Transaction::query()
            ->paid()
            ->whereBetween('paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('total_amount');

        $totalProducts = Product::query()->active()->count();
        $lowStockCount = Product::query()->active()->lowStock()->count();
        $outOfStockCount = Product::query()->active()->outOfStock()->count();

        // Produk terlaris (30 hari terakhir, hanya transaksi lunas)
        $topProducts = TransactionItem::query()
            ->select('product_name', 'product_sku', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_sales'))
            ->whereHas('transaction', function ($q) {
                $q->where('payment_status', PaymentStatus::Paid->value)
                    ->where('status', TransactionStatus::Completed->value)
                    ->where('paid_at', '>=', Carbon::now()->subDays(30));
            })
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Grafik penjualan 7 hari terakhir
        $dailySales = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $total = (int) Transaction::query()
                ->paid()
                ->whereDate('paid_at', $date)
                ->sum('total_amount');

            $dailySales->push([
                'label' => $date->translatedFormat('D, d M'),
                'short' => $date->format('d/m'),
                'total' => $total,
            ]);
        }
        $maxDailySale = max(1, (int) $dailySales->max('total'));

        // Ringkasan metode pembayaran hari ini
        $paymentSummary = Transaction::query()
            ->select('payment_method', DB::raw('COUNT(*) as tx_count'), DB::raw('SUM(total_amount) as total_amount'))
            ->paid()
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->groupBy('payment_method')
            ->get()
            ->keyBy(fn ($row) => $row->payment_method->value);

        $cashTotal = (int) ($paymentSummary->get(PaymentMethod::Cash->value)?->total_amount ?? 0);
        $cashCount = (int) ($paymentSummary->get(PaymentMethod::Cash->value)?->tx_count ?? 0);
        $qrisTotal = (int) ($paymentSummary->get(PaymentMethod::Qris->value)?->total_amount ?? 0);
        $qrisCount = (int) ($paymentSummary->get(PaymentMethod::Qris->value)?->tx_count ?? 0);

        $recentTransactions = Transaction::query()
            ->with('user')
            ->latest()
            ->limit(6)
            ->get();

        return view('livewire.dashboard', [
            'todayRevenue' => $todayRevenue,
            'todayTransactionCount' => $todayTransactionCount,
            'monthRevenue' => $monthRevenue,
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'topProducts' => $topProducts,
            'dailySales' => $dailySales,
            'maxDailySale' => $maxDailySale,
            'cashTotal' => $cashTotal,
            'cashCount' => $cashCount,
            'qrisTotal' => $qrisTotal,
            'qrisCount' => $qrisCount,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
