<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; color: #10B981; }
        h2 { font-size: 14px; margin-top: 20px; border-bottom: 2px solid #10B981; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #10B981; color: white; padding: 6px; text-align: left; font-size: 11px; }
        td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 11px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>M / Y Ledger Report</h1>
    <p><strong>{{ $director->name }}</strong> @if ($director->is_my) 👑 Primary M/Y @endif</p>
    <p>Opening Due: ৳{{ number_format($director->dueLedger?->due ?? 0, 2) }}</p>

    <h2>Director Transactions ({{ $transactions->count() }})</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th class="text-right">Amount</th><th>Remarks</th></tr></thead>
        <tbody>
            @foreach ($transactions as $tx)
            <tr>
                <td>{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                <td>{{ ucfirst($tx->type->value) }}</td>
                <td class="text-right">{{ $tx->type->value === 'withdraw' ? '-' : '+' }}৳{{ number_format($tx->amount, 2) }}</td>
                <td>{{ $tx->remarks ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if ($summaries->isNotEmpty())
    <h2>M/Y Profit Summary ({{ $summaries->count() }})</h2>
    <table>
        <thead><tr><th>Month</th><th class="text-right">Actual (X2)</th><th class="text-right">M/Y Profit (AG184)</th><th class="text-right">Ratio (AG186)</th></tr></thead>
        <tbody>
            @foreach ($summaries as $s)
            <tr>
                <td>{{ $s->profit_month }}</td>
                <td class="text-right">৳{{ number_format($s->total_actual_profit, 2) }}</td>
                <td class="text-right">৳{{ number_format($s->my_profit, 2) }}</td>
                <td class="text-right">{{ $s->my_profit_ratio }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 30px; font-size: 10px; color: #888;">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
