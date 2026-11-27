<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Laporan Penjualan')]
class SalesReport extends Component
{
    public string $period = 'month';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('manage-reports');
        $this->applyPeriod('month');
    }

    public function applyPeriod(string $period): void
    {
        $this->period = $period;

        if ($period === 'today') {
            $this->dateFrom = Carbon::today()->format('Y-m-d');
            $this->dateTo = Carbon::today()->format('Y-m-d');
        } elseif ($period === 'week') {
            $this->dateFrom = Carbon::now()->startOfWeek()->format('Y-m-d');
            $this->dateTo = Carbon::now()->endOfWeek()->format('Y-m-d');
        } elseif ($period === 'month') {
            $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatedDateFrom(): void
    {
        $this->period = 'custom';
    }

    public function updatedDateTo(): void
    {
        $this->period = 'custom';
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('manage-reports');

        $transactions = Transaction::query()
            ->with('user')
            ->paid()
            ->whereDate('paid_at', '>=', $this->dateFrom)
            ->whereDate('paid_at', '<=', $this->dateTo)
            ->orderBy('paid_at')
            ->get();

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No Transaksi', 'Tanggal Bayar', 'Kasir', 'Metode', 'Subtotal', 'Diskon', 'Total']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->transaction_number,
                    $tx->paid_at?->format('Y-m-d H:i:s'),
                    $tx->user?->name ?? '-',
                    $tx->payment_method->label(),
                    $tx->subtotal,
                    $tx->discount_amount,
                    $tx->total_amount,
                ]);
            }
            fclose($handle);
        }, "laporan-penjualan-{$this->dateFrom}-sd-{$this->dateTo}.csv");
    }

    public function render()
    {
        $baseQuery = Transaction::query()
            ->paid()
            ->whereDate('paid_at', '>=', $this->dateFrom)
            ->whereDate('paid_at', '<=', $this->dateTo);

        $totalSales = (int) (clone $baseQuery)->sum('total_amount');
        $totalDiscount = (int) (clone $baseQuery)->sum('discount_amount');
        $totalTransactions = (int) (clone $baseQuery)->count();

        $itemStats = TransactionItem::query()
            ->select(
                DB::raw('COALESCE(SUM((unit_price - cost_price) * quantity), 0) as gross_profit'),
                DB::raw('COALESCE(SUM(quantity), 0) as total_items_sold')
            )
            ->whereHas('transaction', function ($q) {
                $q->where('payment_status', PaymentStatus::Paid->value)
                    ->where('status', TransactionStatus::Completed->value)
                    ->whereDate('paid_at', '>=', $this->dateFrom)
                    ->whereDate('paid_at', '<=', $this->dateTo);
            })
            ->first();

        $estimatedProfit = max(0, (int) ($itemStats->gross_profit ?? 0) - $totalDiscount);
        $totalItemsSold = (int) ($itemStats->total_items_sold ?? 0);

        $byMethod = Transaction::query()
            ->select('payment_method', DB::raw('COUNT(*) as tx_count'), DB::raw('SUM(total_amount) as total_amount'))
            ->paid()
            ->whereDate('paid_at', '>=', $this->dateFrom)
            ->whereDate('paid_at', '<=', $this->dateTo)
            ->groupBy('payment_method')
            ->get();

        $topProducts = TransactionItem::query()
            ->select(
                'product_name',
                'product_sku',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('SUM((unit_price - cost_price) * quantity) as total_profit')
            )
            ->whereHas('transaction', function ($q) {
                $q->where('payment_status', PaymentStatus::Paid->value)
                    ->where('status', TransactionStatus::Completed->value)
                    ->whereDate('paid_at', '>=', $this->dateFrom)
                    ->whereDate('paid_at', '<=', $this->dateTo);
            })
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $byCategory = TransactionItem::query()
            ->leftJoin('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                DB::raw("COALESCE(categories.name, 'Tanpa Kategori') as category_name"),
                DB::raw('SUM(transaction_items.quantity) as total_qty'),
                DB::raw('SUM(transaction_items.subtotal) as total_sales')
            )
            ->whereHas('transaction', function ($q) {
                $q->where('payment_status', PaymentStatus::Paid->value)
                    ->where('status', TransactionStatus::Completed->value)
                    ->whereDate('paid_at', '>=', $this->dateFrom)
                    ->whereDate('paid_at', '<=', $this->dateTo);
            })
            ->groupBy('category_name')
            ->orderByDesc('total_sales')
            ->get();

        return view('livewire.sales-report', [
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'estimatedProfit' => $estimatedProfit,
            'totalItemsSold' => $totalItemsSold,
            'byMethod' => $byMethod,
            'topProducts' => $topProducts,
            'byCategory' => $byCategory,
        ]);
    }
}
