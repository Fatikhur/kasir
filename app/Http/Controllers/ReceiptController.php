<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Request $request, Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['items', 'latestPayment', 'user']);
        $store = StoreSetting::current();
        $paper = $request->query('paper') === '58' ? '58' : '80';

        return view('receipts.thermal', [
            'transaction' => $transaction,
            'store' => $store,
            'paper' => $paper,
        ]);
    }
}
