<?php

namespace App\Http\Controllers;

use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\ProfitAdjustment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    /**
     * Display the investor ledger report — a unified timeline of all
     * capital movements, profit distributions, and adjustments for an investor.
     */
    public function investorLedger(Request $request): Response
    {
        // Get all active + inactive investors for the selector
        $investors = Investor::orderBy('name')->get(['id', 'name', 'reference', 'deed_ratio']);

        $selectedId = $request->get('investor_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $ledger = [];
        $investor = null;
        $openingBalance = 0;
        $openingProfitDue = 0;

        if ($selectedId) {
            $investor = Investor::with(['dueLedger', 'profitDueLedger'])->find($selectedId);

            if ($investor) {
                $openingBalance = (float) ($investor->dueLedger?->due ?? 0);
                $openingProfitDue = (float) ($investor->profitDueLedger?->due ?? 0);

                // 1. Investment transactions (capital add/withdraw)
                $txQuery = InvestmentTransaction::where('investor_id', $selectedId)
                    ->with('creator:id,username');
                if ($dateFrom) {
                    $txQuery->where('transaction_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $txQuery->where('transaction_date', '<=', $dateTo);
                }
                $transactions = $txQuery->orderBy('transaction_date')->get();

                foreach ($transactions as $tx) {
                    $signedAmount = $tx->signedAmount();
                    $ledger[] = [
                        'date' => $tx->transaction_date?->format('Y-m-d'),
                        'type' => 'capital',
                        'subtype' => $tx->type->value,
                        'description' => $tx->type->value === 'add' ? 'Investment Added' : 'Investment Withdrawn',
                        'amount' => $signedAmount,
                        'amount_display' => abs($signedAmount),
                        'is_positive' => $signedAmount > 0,
                        'remarks' => $tx->remarks,
                        'created_by' => $tx->creator?->username ?? '—',
                    ];
                }

                // 2. Monthly profit details (profit distribution)
                $profitQuery = InvestorMonthlyProfitDetail::where('investor_id', $selectedId);
                if ($dateFrom) {
                    $profitQuery->where('profit_month', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $profitQuery->where('profit_month', '<=', $dateTo);
                }
                $profitDetails = $profitQuery->orderBy('profit_month')->get();

                foreach ($profitDetails as $detail) {
                    $ledger[] = [
                        'date' => $detail->profit_month,
                        'type' => 'profit',
                        'subtype' => 'distribution',
                        'description' => "Profit Distribution (Tier {$detail->deed_ratio}%)",
                        'amount' => (float) $detail->actual_profit_due,
                        'amount_display' => (float) $detail->actual_profit_due,
                        'is_positive' => true,
                        'remarks' => "Primary: {$detail->primary_profit_share}, Advance Diff: {$detail->advance_difference}, Retained: {$detail->retained_earnings_credit}",
                        'created_by' => '—',
                    ];

                    // Also show the advance difference as a separate entry
                    if ((float) $detail->advance_difference != 0) {
                        $ledger[] = [
                            'date' => $detail->profit_month,
                            'type' => 'adjustment',
                            'subtype' => 'advance_diff',
                            'description' => 'Advance Difference (AH)',
                            'amount' => -(float) $detail->advance_difference,
                            'amount_display' => abs((float) $detail->advance_difference),
                            'is_positive' => (float) $detail->advance_difference < 0,
                            'remarks' => 'Over-payment settlement',
                            'created_by' => '—',
                        ];
                    }

                    // Retained earnings credit
                    if ((float) $detail->retained_earnings_credit > 0) {
                        $ledger[] = [
                            'date' => $detail->profit_month,
                            'type' => 'retained',
                            'subtype' => 'retained_credit',
                            'description' => 'Retained Earnings Credit (AJ)',
                            'amount' => (float) $detail->retained_earnings_credit,
                            'amount_display' => (float) $detail->retained_earnings_credit,
                            'is_positive' => true,
                            'remarks' => '71% investor portion',
                            'created_by' => '—',
                        ];
                    }
                }

                // 3. Profit adjustments (Fund A/B/Direct)
                $adjQuery = ProfitAdjustment::where('investor_id', $selectedId);
                if ($dateFrom) {
                    $adjQuery->where('transaction_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $adjQuery->where('transaction_date', '<=', $dateTo);
                }
                $adjustments = $adjQuery->orderBy('transaction_date')->get();

                foreach ($adjustments as $adj) {
                    $ledger[] = [
                        'date' => $adj->transaction_date?->format('Y-m-d'),
                        'type' => 'adjustment',
                        'subtype' => $adj->type->value,
                        'description' => "{$adj->type->label()} Adjustment",
                        'amount' => -(float) $adj->amount,
                        'amount_display' => (float) $adj->amount,
                        'is_positive' => false,
                        'remarks' => $adj->remarks,
                        'created_by' => $adj->creator?->username ?? '—',
                    ];
                }

                // Sort by date
                usort($ledger, fn ($a, $b) => strcmp($a['date'], $b['date']));

                // Compute running balance
                $running = 0;
                foreach ($ledger as &$entry) {
                    $running += $entry['amount'];
                    $entry['running_balance'] = $running;
                }
                unset($entry);
            }
        }

        return Inertia::render('Reports/InvestorLedger', [
            'investors' => $investors->map(fn (Investor $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
                'deed_ratio' => $i->deed_ratio,
            ]),
            'selectedId' => (int) $selectedId,
            'investor' => $investor ? [
                'id' => $investor->id,
                'name' => $investor->name,
                'reference' => $investor->reference,
                'deed_ratio' => $investor->deed_ratio,
                'opening_balance' => $openingBalance,
                'opening_profit_due' => $openingProfitDue,
            ] : null,
            'ledger' => $ledger,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'summary' => [
                'total_entries' => count($ledger),
                'total_inflow' => array_sum(array_map(fn ($e) => $e['amount'] > 0 ? $e['amount'] : 0, $ledger)),
                'total_outflow' => abs(array_sum(array_map(fn ($e) => $e['amount'] < 0 ? $e['amount'] : 0, $ledger))),
                'net_balance' => array_sum(array_map(fn ($e) => $e['amount'], $ledger)),
            ],
        ]);
    }
}
