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
        .summary { margin: 15px 0; padding: 10px; background: #f0fdf4; border-radius: 5px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h1>Investor Ledger Report</h1>
    <p><strong>{{ $investor->name }}</strong> (Tier {{ $investor->deed_ratio }}%)</p>
    <p>Opening Capital: ৳{{ number_format($investor->dueLedger?->due ?? 0, 2) }} | Opening Profit Due: ৳{{ number_format($investor->profitDueLedger?->due ?? 0, 2) }}</p>
    @if ($dateFrom || $dateTo)
    <p>Period: {{ $dateFrom ?? 'Start' }} to {{ $dateTo ?? 'Now' }}</p>
    @endif

    <h2>Capital Transactions ({{ $transactions->count() }})</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Type</th><th class="text-right">Amount</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
            <tr>
                <td>{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                <td><span class="badge {{ $tx->type->value === 'add' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($tx->type->value) }}</span></td>
                <td class="text-right">{{ $tx->type->value === 'add' ? '+' : '-' }}৳{{ number_format($tx->amount, 2) }}</td>
                <td>{{ $tx->remarks ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if ($profitDetails->isNotEmpty())
    <h2>Profit Distributions ({{ $profitDetails->count() }})</h2>
    <table>
        <thead>
            <tr><th>Month</th><th class="text-right">Investment</th><th class="text-right">Profit Due</th><th class="text-right">Advance Diff</th><th class="text-right">Net</th></tr>
        </thead>
        <tbody>
            @foreach ($profitDetails as $d)
            <tr>
                <td>{{ $d->profit_month }}</td>
                <td class="text-right">৳{{ number_format($d->investment, 2) }}</td>
                <td class="text-right">৳{{ number_format($d->actual_profit_due, 2) }}</td>
                <td class="text-right">৳{{ number_format($d->advance_difference, 2) }}</td>
                <td class="text-right">৳{{ number_format($d->net_settlement, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if ($adjustments->isNotEmpty())
    <h2>Profit Adjustments ({{ $adjustments->count() }})</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Type</th><th class="text-right">Amount</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            @foreach ($adjustments as $adj)
            <tr>
                <td>{{ $adj->transaction_date?->format('Y-m-d') }}</td>
                <td>{{ $adj->type->label() }}</td>
                <td class="text-right">-৳{{ number_format($adj->amount, 2) }}</td>
                <td>{{ $adj->remarks ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 30px; font-size: 10px; color: #888;">Generated on {{ now()->format('Y-m-d H:i') }} by Mudaraba System</p>
</body>
</html>
