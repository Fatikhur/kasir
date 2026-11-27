<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->isAdminOrSuperadmin()) {
            return true;
        }

        return $transaction->user_id === $user->id;
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        if ($user->isAdminOrSuperadmin()) {
            return $transaction->canBeCancelled();
        }

        // Kasir hanya boleh membatalkan transaksi miliknya yang masih pending
        return $transaction->user_id === $user->id
            && $transaction->status->value === 'pending';
    }

    public function refund(User $user, Transaction $transaction): bool
    {
        return $user->isAdminOrSuperadmin() && $transaction->canBeRefunded();
    }
}
