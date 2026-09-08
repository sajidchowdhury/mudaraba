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
    <h1>Sector Ledger Report</h1>
    <p><strong>{{ $sector->name }}</strong></p>
    <p>Opening Capital: ৳{{ number_format($sector->dueLedger?->due ?? 0, 2) }} | Opening Profit Due: ৳{{ number_format($sector->profitDueLedger?->due ?? 0, 2) }}</p>

    <h2>Investments ({{ $investments->count() }})</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th class="text-right">Amount</th><th>Remarks</th></tr></thead>
        <tbody>
            @foreach ($investments as $inv)
            <tr>
                <td>{{ $inv->transaction_date?->format('Y-m-d') }}</td>
                <td>{{ ucfirst($inv->type->value) }}</td>
                <td class="text-right">{{ $inv->type->value === 'add' ? '+' : '-' }}৳{{ number_format($inv->amount, 2) }}</td>
                <td>{{ $inv->remarks ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if ($profits->isNotEmpty())
    <h2>Monthly Profits ({{ $profits->count() }})</h2>
    <table>
        <thead><tr><th>Month</th><th class="text-right">Estimated (Z)</th><th class="text-right">Actual (X)</th><th class="text-right">Variance (Y)</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($profits as $p)
            <tr>
                <td>{{ $p->profit_month }}</td>
                <td class="text-right">৳{{ number_format($p->estimated_profit, 2) }}</td>
                <td class="text-right">৳{{ number_format($p->actual_profit, 2) }}</td>
                <td class="text-right">৳{{ number_format($p->estimated_profit - $p->actual_profit, 2) }}</td>
                <td>{{ $p->status->label() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if ($adjustments->isNotEmpty())
    <h2>Adjustments ({{ $adjustments->count() }})</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th class="text-right">Amount</th></tr></thead>
        <tbody>
            @foreach ($adjustments as $adj)
            <tr>
                <td>{{ $adj->transaction_date?->format('Y-m-d') }}</td>
                <td>{{ $adj->type->label() }}</td>
                <td class="text-right">-৳{{ number_format($adj->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top: 30px; font-size: 10px; color: #888;">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
