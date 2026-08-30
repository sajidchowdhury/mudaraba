<?php

namespace App\Services;

use App\Models\Investor;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlySectorProfit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ProfitCalculatorService — the heart of the 8-phase calculation engine.
 *
 * This service implements the complete profit distribution algorithm that
 * mirrors the Excel "July, 2026 For Sajid" sheet. It is triggered when the
 * M/Y finalizes sector profits for a month.
 *
 * After computing Phases 1-4, it delegates to RetainedEarningsService for
 * Phases 5-7 (retained earnings allocation + net settlement).
 *
 * === The 8-Phase Calculation Flow (per plan §5.1) ===
 *
 * PHASE 1 — Totals (Excel Z2, X2, Y2, D181)
 * PHASE 2 — Per-investor ratio + primary share (Excel D, E, Q, N)
 * PHASE 3 — Tier application (Excel AF, AG)
 * PHASE 4 — Advance difference (Excel AH)
 * PHASE 5 — Retained earnings allocation (Excel AI3, AJ4, AK4, AJ)
 * PHASE 6 — Net settlement (Excel AK = AH − AJ)
 * PHASE 7 — Aggregates (AH182, AJ182)
 * PHASE 8 — M/Y profit (Excel AG184, AG186)
 */
class ProfitCalculatorService
{
    public function __construct(
        private readonly RetainedEarningsService $retainedEarningsService,
    ) {}

    /**
     * Calculate and store investor profit details for a given month.
     *
     * @param  string  $profitMonth  'YYYY-MM-DD' (1st of month)
     * @param  int  $userId  The user triggering the calculation
     * @return array{summary: array, details_count: int}
     */
    public function calculate(string $profitMonth, int $userId): array
    {
        $batchUuid = Str::uuid()->toString();

        // PHASE 1 — Load sector profits + compute totals
        $sectorProfits = MonthlySectorProfit::forMonth($profitMonth)->get();

        if ($sectorProfits->isEmpty()) {
            throw new \RuntimeException("No sector profits found for month {$profitMonth}");
        }

        $totalEstimated = $sectorProfits->sum(fn ($s) => (float) $s->estimated_profit); // Z2
        $totalActual = $sectorProfits->sum(fn ($s) => (float) $s->actual_profit);     // X2
        $totalVariance = $totalEstimated - $totalActual;                                   // Y2

        // PHASE 2 — Load active investors + compute total investment (D181)
        $investors = Investor::where('status', 'active')
            ->where(function ($q) use ($profitMonth) {
                $q->whereNull('start_profit_month')
                    ->orWhere('start_profit_month', '<=', $profitMonth);
            })
            ->where(function ($q) use ($profitMonth) {
                $q->whereNull('end_profit_month')
                    ->orWhere('end_profit_month', '>=', $profitMonth);
            })
            ->with('dueLedger')
            ->get();

        if ($investors->isEmpty()) {
            throw new \RuntimeException('No active investors found for profit calculation');
        }

        $totalInvestment = $investors->sum(fn (Investor $i) => (float) ($i->dueLedger?->due ?? 0));

        if ($totalInvestment <= 0) {
            throw new \RuntimeException('Total investment is zero — cannot compute ratios');
        }

        // PHASE 2-4 — Compute per-investor details
        $details = [];
        $totalActualDue = 0;    // AG182
        $totalAdvanceDiff = 0;  // AH182

        foreach ($investors as $investor) {
            $investment = (float) ($investor->dueLedger?->due ?? 0);

            if ($investment <= 0) {
                continue;
            }

            // Phase 2: ratio + primary share
            $ratio = $investment / $totalInvestment;
            $primaryProfitShare = $ratio * $totalEstimated;   // Q = E × Z2
            $actualProfitAtFull = $ratio * $totalActual;       // N = E × X2

            // Phase 3: tier application
            $deedRatio = (float) $investor->deed_ratio;
            $actualProfitDue = $actualProfitAtFull * ($deedRatio / 100); // AG

            // Phase 4: advance difference
            $advanceDifference = $primaryProfitShare - $actualProfitDue;  // AH

            $totalActualDue += $actualProfitDue;
            $totalAdvanceDiff += $advanceDifference;

            $details[] = [
                'profit_month' => $profitMonth,
                'transaction_date' => now(),
                'investor_id' => $investor->id,
                'investment' => $investment,
                'investment_ratio' => $ratio,
                'primary_profit_share' => $primaryProfitShare,
                'actual_profit_at_full' => $actualProfitAtFull,
                'deed_ratio' => $deedRatio,
                'actual_profit_due' => $actualProfitDue,
                'advance_difference' => $advanceDifference,
                'retained_earnings_credit' => 0, // Phase 5 — filled by RetainedEarningsService
                'net_settlement' => $advanceDifference, // Phase 6 — adjusted by RetainedEarningsService
                'batch_uuid' => $batchUuid,
                'created_by' => $userId,
                'created_at' => now(),
            ];
        }

        // PHASE 8 — M/Y profit = X2 − AG182 (Excel AG184)
        $myProfit = $totalActual - $totalActualDue;
        $myProfitRatio = $totalActual > 0 ? ($myProfit / $totalActual) * 100 : 0;

        // Write Phases 1-4 + 8 in a transaction
        DB::transaction(function () use (
            $profitMonth, $details, $userId,
            $totalEstimated, $totalActual, $totalVariance, $totalInvestment,
            $totalActualDue, $totalAdvanceDiff, $myProfit, $myProfitRatio
        ) {
            InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->delete();
            InvestorMonthlyProfitDetail::insert($details);

            DB::table('monthly_profit_summary')->updateOrInsert(
                ['profit_month' => $profitMonth],
                [
                    'transaction_date' => now(),
                    'total_estimated_profit' => $totalEstimated,
                    'total_actual_profit' => $totalActual,
                    'total_advance_difference' => $totalVariance,
                    'total_investor_advance_diff' => round($totalAdvanceDiff, 2), // AH182
                    'total_investor_profit_due' => $totalActualDue,
                    'total_investor_retained' => 0, // Phase 5 — filled below
                    'my_profit' => $myProfit,
                    'my_profit_ratio' => round($myProfitRatio, 2),
                    'total_mudaraba_investment' => $totalInvestment,
                    'active_investor_count' => count($details),
                    'status' => 'finalized',
                    'finalized_by' => $userId,
                    'finalized_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        // PHASES 5-7 — Retained earnings allocation + net settlement
        $retainedResult = $this->retainedEarningsService->allocate($profitMonth, $userId);

        Log::info('Profit calculation completed (8 phases)', [
            'month' => $profitMonth,
            'investors' => count($details),
            'total_estimated' => $totalEstimated,
            'total_actual' => $totalActual,
            'total_due' => $totalActualDue,
            'total_advance' => $totalAdvanceDiff,
            'retained_total' => $retainedResult['total'],
            'my_profit' => $myProfit,
            'my_ratio' => round($myProfitRatio, 2),
            'batch_uuid' => $batchUuid,
        ]);

        return [
            'summary' => [
                'total_estimated' => $totalEstimated,
                'total_actual' => $totalActual,
                'total_variance' => $totalVariance,
                'total_investment' => $totalInvestment,
                'total_investor_due' => $totalActualDue,
                'total_advance_diff' => round($totalAdvanceDiff, 2),
                'retained_total' => $retainedResult['total'],
                'retained_investor' => $retainedResult['investor_portion'],
                'retained_my' => $retainedResult['my_portion'],
                'my_profit' => $myProfit,
                'my_profit_ratio' => round($myProfitRatio, 2),
            ],
            'details_count' => count($details),
        ];
    }
}
