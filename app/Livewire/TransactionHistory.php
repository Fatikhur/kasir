<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Transaction;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Riwayat Transaksi')]
class TransactionHistory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $filterMethod = '';

    public string $filterStatus = '';

    public ?int $selectedTransactionId = null;

    // Cancel / Refund modal
    public ?int $actionTransactionId = null;

    public string $actionType = ''; // 'cancel' or 'refund'

    public string $actionReason = '';

    public bool $restockItems = true;

    public ?string $errorMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMethod(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function showDetail(int $transactionId): void
    {
        $this->selectedTransactionId = $transactionId;
    }

    public function closeDetail(): void
    {
        $this->selectedTransactionId = null;
    }

    public function openActionModal(int $transactionId, string $type): void
    {
        $this->actionTransactionId = $transactionId;
        $this->actionType = $type;
        $this->actionReason = '';
        $this->restockItems = true;
        $this->errorMessage = null;
    }

    public function executeAction(TransactionService $transactionService): void
    {
        $this->errorMessage = null;

        $this->validate([
            'actionReason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'actionReason.required' => 'Alasan wajib diisi untuk catatan audit.',
            'actionReason.min' => 'Alasan minimal 3 karakter.',
        ]);

        $transaction = Transaction::query()->findOrFail($this->actionTransactionId);
        $user = Auth::user();

        try {
            if ($this->actionType === 'cancel') {
                if (! $user->can('cancel', $transaction)) {
                    abort(403, 'Anda tidak berwenang membatalkan transaksi ini.');
                }
                $transactionService->cancelTransaction($transaction, $user, $this->actionReason);
                session()->flash('success', "Transaksi {$transaction->transaction_number} berhasil dibatalkan.");
            } elseif ($this->actionType === 'refund') {
                if (! $user->can('refund', $transaction)) {
                    abort(403, 'Hanya pemilik toko yang dapat melakukan refund.');
                }
                $transactionService->refundTransaction($transaction, $user, $this->actionReason, $this->restockItems);
                session()->flash('success', "Refund transaksi {$transaction->transaction_number} berhasil diproses.");
            }

            $this->actionTransactionId = null;
            $this->selectedTransactionId = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function syncPendingQris(int $transactionId, TransactionService $transactionService): void
    {
        $tx = Transaction::query()->with(['latestPayment', 'items'])->findOrFail($transactionId);
        $transactionService->checkAndSyncQrisStatus($tx);
        session()->flash('success', "Status transaksi {$tx->transaction_number} telah diperbarui.");
    }

    public function render()
    {
        $user = Auth::user();

        $transactions = Transaction::query()
            ->with(['user', 'latestPayment'])
            ->when(! $user->isOwner(), fn ($q) => $q->where('user_id', $user->id))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('transaction_number', 'like', $term)
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $term));
                });
            })
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->filterMethod !== '', fn ($q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('payment_status', $this->filterStatus))
            ->latest()
            ->paginate(15);

        $selectedTransaction = $this->selectedTransactionId
            ? Transaction::query()->with(['items', 'latestPayment', 'user', 'cancelledBy', 'refunds.user'])->find($this->selectedTransactionId)
            : null;

        return view('livewire.transaction-history', [
            'transactions' => $transactions,
            'selectedTransaction' => $selectedTransaction,
            'paymentMethods' => PaymentMethod::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }
}
