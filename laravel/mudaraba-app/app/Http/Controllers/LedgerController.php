<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\DirectorTransaction;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\ProfitAdjustment;
use App\Models\RetainedEarnings;
use App\Models\Sector;
use App\Models\SectorInvestment;
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

    /**
     * Display the sector ledger report — a unified timeline of all
     * sector investments, monthly sector profits, and profit adjustments.
     */
    public function sectorLedger(Request $request): Response
    {
        $sectors = Sector::orderBy('name')->get(['id', 'name']);

        $selectedId = $request->get('sector_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $ledger = [];
        $sector = null;
        $openingBalance = 0;
        $openingProfitDue = 0;

        if ($selectedId) {
            $sector = Sector::with(['dueLedger', 'profitDueLedger'])->find($selectedId);

            if ($sector) {
                $openingBalance = (float) ($sector->dueLedger?->due ?? 0);
                $openingProfitDue = (float) ($sector->profitDueLedger?->due ?? 0);

                // 1. Sector investments (capital add/withdraw)
                $invQuery = SectorInvestment::where('sector_id', $selectedId)
                    ->with('creator:id,username');
                if ($dateFrom) {
                    $invQuery->where('transaction_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $invQuery->where('transaction_date', '<=', $dateTo);
                }
                $investments = $invQuery->orderBy('transaction_date')->get();

                foreach ($investments as $inv) {
                    $signed = $inv->signedAmount();
                    $ledger[] = [
                        'date' => $inv->transaction_date?->format('Y-m-d'),
                        'type' => 'capital',
                        'subtype' => $inv->type->value,
                        'description' => $inv->type->value === 'add' ? 'Capital Added' : 'Capital Withdrawn',
                        'amount' => $signed,
                        'amount_display' => abs($signed),
                        'is_positive' => $signed > 0,
                        'remarks' => $inv->remarks,
                        'created_by' => $inv->creator?->username ?? '—',
                    ];
                }

                // 2. Monthly sector profits (estimated vs actual + variance)
                $profitQuery = MonthlySectorProfit::where('sector_id', $selectedId);
                if ($dateFrom) {
                    $profitQuery->where('profit_month', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $profitQuery->where('profit_month', '<=', $dateTo);
                }
                $profits = $profitQuery->orderBy('profit_month')->get();

                foreach ($profits as $p) {
                    $variance = (float) $p->estimated_profit - (float) $p->actual_profit;

                    $ledger[] = [
                        'date' => $p->profit_month,
                        'type' => 'profit',
                        'subtype' => 'estimated',
                        'description' => "Estimated Profit (Z) — {$p->status->label()}",
                        'amount' => (float) $p->estimated_profit,
                        'amount_display' => (float) $p->estimated_profit,
                        'is_positive' => true,
                        'remarks' => 'Primary shared profit estimate',
                        'created_by' => $p->creator?->username ?? '—',
                    ];

                    if ((float) $p->actual_profit > 0) {
                        $ledger[] = [
                            'date' => $p->profit_month,
                            'type' => 'profit',
                            'subtype' => 'actual',
                            'description' => 'Actual Profit (X)',
                            'amount' => (float) $p->actual_profit,
                            'amount_display' => (float) $p->actual_profit,
                            'is_positive' => true,
                            'remarks' => 'Realized sector profit',
                            'created_by' => '—',
                        ];
                    }

                    if ($variance != 0) {
                        $ledger[] = [
                            'date' => $p->profit_month,
                            'type' => 'variance',
                            'subtype' => 'advance_diff',
                            'description' => 'Variance (Y = Z − X)',
                            'amount' => -$variance,
                            'amount_display' => abs($variance),
                            'is_positive' => $variance < 0,
                            'remarks' => $variance > 0 ? 'Over-paid in advance' : 'Under-paid',
                            'created_by' => '—',
                        ];
                    }
                }

                // 3. Profit adjustments targeting this sector
                $adjQuery = ProfitAdjustment::where('sector_id', $selectedId);
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

        return Inertia::render('Reports/SectorLedger', [
            'sectors' => $sectors->map(fn (Sector $s) => [
                'id' => $s->id,
                'name' => $s->name,
            ]),
            'selectedId' => (int) $selectedId,
            'sector' => $sector ? [
                'id' => $sector->id,
                'name' => $sector->name,
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

    /**
     * Display the M/Y (director) ledger report — a unified timeline of all
     * director transactions, M/Y profit from monthly summaries, and
     * retained earnings (29% portion).
     */
    public function myLedger(Request $request): Response
    {
        $directors = Director::orderByDesc('is_my')->orderBy('name')->get(['id', 'name', 'is_my']);

        $selectedId = $request->get('director_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $ledger = [];
        $director = null;
        $openingDue = 0;

        if ($selectedId) {
            $director = Director::with('dueLedger')->find($selectedId);

            if ($director) {
                $openingDue = (float) ($director->dueLedger?->due ?? 0);

                // 1. Director transactions (withdraw / return)
                $txQuery = DirectorTransaction::where('director_id', $selectedId)
                    ->with('creator:id,username');
                if ($dateFrom) {
                    $txQuery->where('transaction_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $txQuery->where('transaction_date', '<=', $dateTo);
                }
                $transactions = $txQuery->orderBy('transaction_date')->get();

                foreach ($transactions as $tx) {
                    $signed = $tx->signedAmount();
                    $ledger[] = [
                        'date' => $tx->transaction_date?->format('Y-m-d'),
                        'type' => 'transaction',
                        'subtype' => $tx->type->value,
                        'description' => $tx->type->value === 'withdraw' ? 'M/Y Withdrawal' : 'M/Y Return',
                        'amount' => $signed,
                        'amount_display' => abs($signed),
                        'is_positive' => $signed > 0,
                        'remarks' => $tx->remarks,
                        'created_by' => $tx->creator?->username ?? '—',
                    ];
                }

                // 2. M/Y profit from monthly summaries (AG184)
                $summaryQuery = MonthlyProfitSummary::query();
                if ($dateFrom) {
                    $summaryQuery->where('profit_month', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $summaryQuery->where('profit_month', '<=', $dateTo);
                }
                $summaries = $summaryQuery->orderBy('profit_month')->get();

                foreach ($summaries as $summary) {
                    if ((float) $summary->my_profit != 0) {
                        $ledger[] = [
                            'date' => $summary->profit_month,
                            'type' => 'profit',
                            'subtype' => 'my_profit',
                            'description' => 'M/Y Profit (AG184)',
                            'amount' => (float) $summary->my_profit,
                            'amount_display' => (float) $summary->my_profit,
                            'is_positive' => true,
                            'remarks' => 'Ratio: '.(float) $summary->my_profit_ratio.'% | Actual: '.(float) $summary->total_actual_profit,
                            'created_by' => '—',
                        ];
                    }

                    // Retained earnings 29% portion (AK4)
                    $retained = RetainedEarnings::where('profit_month', $summary->profit_month)->first();
                    if ($retained && (float) $retained->my_portion_amount > 0) {
                        $ledger[] = [
                            'date' => $summary->profit_month,
                            'type' => 'retained',
                            'subtype' => 'my_portion',
                            'description' => 'Retained Earnings M/Y Portion (AK4)',
                            'amount' => (float) $retained->my_portion_amount,
                            'amount_display' => (float) $retained->my_portion_amount,
                            'is_positive' => true,
                            'remarks' => '29% of '.(float) $retained->total_amount,
                            'created_by' => '—',
                        ];
                    }
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

        return Inertia::render('Reports/MYLedger', [
            'directors' => $directors->map(fn (Director $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'is_my' => (bool) $d->is_my,
            ]),
            'selectedId' => (int) $selectedId,
            'director' => $director ? [
                'id' => $director->id,
                'name' => $director->name,
                'is_my' => (bool) $director->is_my,
                'opening_due' => $openingDue,
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

    /**
     * Display the Investment Profit Report — a cross-investor comparative view
     * showing investment, ratio, profit due, and profit ratio over time.
     *
     * Unlike the per-investor ledger (7.2), this report compares ALL investors
     * side-by-side for a selected month, with optional tier filter.
     */
    public function investmentProfit(Request $request): Response
    {
        $month = $request->get('month', date('Y-m-01'));
        $month = date('Y-m-01', strtotime($month));
        $tierFilter = $request->get('tier', 'all');

        // Load all active investors with their due ledger
        $investorQuery = Investor::where('status', 'active')
            ->with(['dueLedger', 'profitDueLedger']);

        if ($tierFilter !== 'all') {
            $investorQuery->where('deed_ratio', $tierFilter);
        }

        $investors = $investorQuery->orderByDesc('name')->get();

        // Load profit details for this month
        $details = InvestorMonthlyProfitDetail::where('profit_month', $month)
            ->get()
            ->keyBy('investor_id');

        // Load monthly summary for totals
        $summary = MonthlyProfitSummary::find($month);

        // Build the comparative grid
        $grid = [];
        foreach ($investors as $investor) {
            $detail = $details->get($investor->id);
            $investment = (float) ($investor->dueLedger?->due ?? 0);

            if ($investment <= 0 && ! $detail) {
                continue; // Skip investors with no investment AND no profit detail
            }

            $grid[] = [
                'investor_id' => $investor->id,
                'name' => $investor->name,
                'reference' => $investor->reference,
                'deed_ratio' => $investor->deed_ratio,
                'investment' => $investment,
                'investment_ratio' => $detail ? (float) $detail->investment_ratio : 0,
                'primary_profit_share' => $detail ? (float) $detail->primary_profit_share : 0,
                'actual_profit_due' => $detail ? (float) $detail->actual_profit_due : 0,
                'advance_difference' => $detail ? (float) $detail->advance_difference : 0,
                'retained_credit' => $detail ? (float) $detail->retained_earnings_credit : 0,
                'net_settlement' => $detail ? (float) $detail->net_settlement : 0,
                'profit_ratio' => $detail && $detail->actual_profit_at_full > 0
                    ? round(((float) $detail->actual_profit_due / (float) $detail->actual_profit_at_full) * 100, 2)
                    : 0,
                'has_detail' => (bool) $detail,
            ];
        }

        // Sort by investment descending
        usort($grid, fn ($a, $b) => $b['investment'] <=> $a['investment']);

        // Compute totals
        $totalInvestment = array_sum(array_column($grid, 'investment'));
        $totalProfitDue = array_sum(array_column($grid, 'actual_profit_due'));
        $totalAdvanceDiff = array_sum(array_column($grid, 'advance_difference'));
        $totalRetained = array_sum(array_column($grid, 'retained_credit'));

        return Inertia::render('Reports/InvestmentProfit', [
            'month' => $month,
            'monthLabel' => date('F, Y', strtotime($month)),
            'grid' => $grid,
            'totals' => [
                'investment' => $totalInvestment,
                'profit_due' => $totalProfitDue,
                'advance_diff' => $totalAdvanceDiff,
                'retained' => $totalRetained,
                'investor_count' => count($grid),
                'my_profit' => $summary ? (float) $summary->my_profit : 0,
                'my_profit_ratio' => $summary ? (float) $summary->my_profit_ratio : 0,
                'total_actual' => $summary ? (float) $summary->total_actual_profit : 0,
            ],
            'tierFilter' => $tierFilter,
            'hasData' => count($grid) > 0,
        ]);
    }
}
