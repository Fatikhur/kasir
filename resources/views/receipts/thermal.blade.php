<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f1f5f9;
            color: #0f172a;
            padding: 20px 12px;
            font-size: {{ $paper === '58' ? '11px' : '12px' }};
            line-height: 1.4;
        }
        .actions {
            max-width: {{ $paper === '58' ? '58mm' : '80mm' }};
            margin: 0 auto 12px auto;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .btn {
            padding: 6px 12px;
            font-family: system-ui, sans-serif;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: #059669;
            border-color: #059669;
            color: #fff;
        }
        .receipt {
            width: {{ $paper === '58' ? '58mm' : '80mm' }};
            margin: 0 auto;
            background: #fff;
            padding: {{ $paper === '58' ? '4mm' : '6mm' }};
            border: 1px solid #e2e8f0;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #64748b; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .item-name { font-weight: bold; margin-top: 4px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .receipt { border: none; width: {{ $paper === '58' ? '58mm' : '80mm' }}; margin: 0; }
            @page { margin: 0; size: {{ $paper === '58' ? '58mm' : '80mm' }} auto; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()" class="btn btn-primary">Cetak Struk</button>
        <a href="{{ route('transactions.receipt', ['transaction' => $transaction, 'paper' => $paper === '58' ? '80' : '58']) }}" class="btn">
            Ganti ke {{ $paper === '58' ? '80mm' : '58mm' }}
        </a>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="bold" style="font-size: 14px;">{{ $store->store_name }}</div>
            @if($store->address)
                <div>{{ $store->address }}</div>
            @endif
            @if($store->phone)
                <div>Telp: {{ $store->phone }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <div>
            <div class="row"><span>No:</span><span class="bold">{{ $transaction->transaction_number }}</span></div>
            <div class="row"><span>Tgl:</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
            @php
                $cashierName = trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($transaction->user?->name ?? '-')));
            @endphp
            <div class="row"><span>Kasir:</span><span>{{ $cashierName ?: '-' }}</span></div>
        </div>

        <div class="divider"></div>

        <div>
            @foreach($transaction->items as $item)
                <div class="item-name">{{ $item->product_name }}</div>
                <div class="row">
                    <span>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="divider"></div>

        <div>
            <div class="row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($transaction->discount_amount > 0)
                <div class="row">
                    <span>Diskon</span>
                    <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="row bold" style="font-size: 13px; margin-top: 3px;">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <div>
            <div class="row">
                <span>Metode</span>
                <span>{{ $transaction->payment_method->label() }}</span>
            </div>
            <div class="row">
                <span>Status</span>
                <span class="bold">{{ $transaction->payment_status->label() }}</span>
            </div>
            @if($transaction->payment_method->value === 'cash' && $transaction->latestPayment)
                <div class="row">
                    <span>Tunai</span>
                    <span>Rp {{ number_format($transaction->latestPayment->cash_received, 0, ',', '.') }}</span>
                </div>
                <div class="row bold">
                    <span>Kembali</span>
                    <span>Rp {{ number_format($transaction->latestPayment->change_amount, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>

        @if($store->receipt_footer)
            <div class="divider"></div>
            <div class="center">{{ $store->receipt_footer }}</div>
        @endif
    </div>
</body>
</html>
