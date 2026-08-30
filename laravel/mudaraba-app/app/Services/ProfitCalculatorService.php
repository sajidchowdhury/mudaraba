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
 * === The 8-Phase Calculation Flow (per plan §5.1) ===
 *
 * INPUT:
 *   - 17 sectors × (estimated_profit, actual_profit) for month M
 *   - 151 investors × (investment balance, deed_ratio) as of month M
 *
 * PHASE 1 — Totals (Excel Z2, X2, Y2)
 *   Z2 = Σ sector.estimated_profit       (total primary / advance pool)
 *   X2 = Σ sector.actual_profit          (total realized)
 *   Y2 = Z2 − X2                          (sector advance difference)
 *   D181 = Σ investor.investment          (total mudaraba pool)
 *
 * PHASE 2 — Per-investor ratio + primary share (Excel D, E, Q)
 *   ratio[i]      = investment[i] / D181
 *   primary[i]    = ratio[i] × Z2         (paid as advance, start of month)
 *   actual_full[i] = ratio[i] × X2        (100% proportional entitlement)
 *
 * PHASE 3 — Tier application (Excel AF, AG)
 *   actual_due[i] = actual_full[i] × deed_ratio[i] / 100
 *   (deed_ratio ∈ {100, 80, 60})
 *
 * PHASE 4 — Advance difference (Excel AH)
 *   advance_diff[i] = primary[i] − actual_due[i]
 *   positive → investor was over-paid, owes M/Y
 *   negative → investor under-paid, M/Y owes them
 *
 * (Phases 5-8 — Retained Earnings + Net Settlement + M/Y Profit — are
 *  implemented in Session 4.3 RetainedEarningsService. This service
 *  handles Phases 1-4 and writes the monthly_profit_summary with M/Y
 *  profit = X2 − Σactual_due.)
 *
 * OUTPUT:
 *   - Per-investor rows in investor_monthly_profit_details
 *   - Monthly summary in monthly_profit_summary (Z2, X2, Y2, AG182, AG184)
 */
class ProfitCalculatorService
{
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
                // Only investors whose profit window includes this month
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

        // Total investment = sum of all investor due ledger balances (D181)
        $totalInvestment = $investors->sum(fn (Investor $i) => (float) ($i->dueLedger?->due ?? 0));

        if ($totalInvestment <= 0) {
            throw new \RuntimeException('Total investment is zero — cannot compute ratios');
        }

        // PHASE 2-4 — Compute per-investor details
        $details = [];
        $totalActualDue = 0; // AG182
        $totalPrimaryProfit = 0;

        foreach ($investors as $investor) {
            $investment = (float) ($investor->dueLedger?->due ?? 0);

            if ($investment <= 0) {
                continue; // Skip investors with zero/negative balance
            }

            // Phase 2: ratio + primary share
            $ratio = $investment / $totalInvestment;                               // E = D/D181
            $primaryProfitShare = $ratio * $totalEstimated;                         // Q = E × Z2
            $actualProfitAtFull = $ratio * $totalActual;                            // N = E × X2

            // Phase 3: tier application
            $deedRatio = (float) $investor->deed_ratio;                             // AF (100/80/60)
            $actualProfitDue = $actualProfitAtFull * ($deedRatio / 100);             // AG = N × AF

            // Phase 4: advance difference
            $advanceDifference = $primaryProfitShare - $actualProfitDue;             // AH = Q − AG

            $totalActualDue += $actualProfitDue;
            $totalPrimaryProfit += $primaryProfitShare;

            $details[] = [
                'profit_month' => $profitMonth,
                'transaction_date' => now(),
                'investor_id' => $investor->id,
                'investment' => $investment,                               // D
                'investment_ratio' => $ratio,                                     // E
                'primary_profit_share' => $primaryProfitShare,                       // Q/F
                'actual_profit_at_full' => $actualProfitAtFull,                        // N
                'deed_ratio' => $deedRatio,                                 // AF
                'actual_profit_due' => $actualProfitDue,                           // AG
                'advance_difference' => $advanceDifference,                        // AH
                'retained_earnings_credit' => 0, // Phase 5 — Session 4.3
                'net_settlement' => $advanceDifference, // Phase 6 (before retained) — Session 4.3 adjusts
                'batch_uuid' => $batchUuid,
                'created_by' => $userId,
                'created_at' => now(),
            ];
        }

        // PHASE 8 — M/Y profit = X2 − AG182 (Excel AG184)
        // Also equals AH182 − Y2 = (Z2 − AG182) − (Z2 − X2) = X2 − AG182
        $myProfit = $totalActual - $totalActualDue;                                  // AG184
        $myProfitRatio = $totalActual > 0 ? ($myProfit / $totalActual) * 100 : 0;   // AG186

        // Write everything in a single transaction
        DB::transaction(function () use (
            $profitMonth, $details, $userId,
            $totalEstimated, $totalActual, $totalVariance, $totalInvestment,
            $totalActualDue, $myProfit, $myProfitRatio
        ) {
            // 1. Delete existing details for this month (clean recompute)
            InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->delete();

            // 2. Bulk insert new details
            InvestorMonthlyProfitDetail::insert($details);

            // 3. Upsert monthly_profit_summary
            DB::table('monthly_profit_summary')->updateOrInsert(
                ['profit_month' => $profitMonth],
                [
                    'transaction_date' => now(),
                    'total_estimated_profit' => $totalEstimated,              // Z2
                    'total_actual_profit' => $totalActual,                  // X2
                    'total_advance_difference' => $totalVariance,                // Y2
                    'total_investor_advance_diff' => 0, // Phase 5 — Session 4.3
                    'total_investor_profit_due' => $totalActualDue,               // AG182
                    'total_investor_retained' => 0, // Phase 5 — Session 4.3
                    'my_profit' => $myProfit,                     // AG184
                    'my_profit_ratio' => round($myProfitRatio, 2),      // AG186
                    'total_mudaraba_investment' => $totalInvestment,              // D181
                    'active_investor_count' => count($details),
                    'status' => 'finalized',
                    'finalized_by' => $userId,
                    'finalized_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        Log::info('Profit calculation completed', [
            'month' => $profitMonth,
            'investors' => count($details),
            'total_estimated' => $totalEstimated,    // Z2
            'total_actual' => $totalActual,        // X2
            'total_due' => $totalActualDue,     // AG182
            'my_profit' => $myProfit,            // AG184
            'my_ratio' => round($myProfitRatio, 2), // AG186
            'batch_uuid' => $batchUuid,
        ]);

        return [
            'summary' => [
                'total_estimated' => $totalEstimated,        // Z2
                'total_actual' => $totalActual,            // X2
                'total_variance' => $totalVariance,          // Y2
                'total_investment' => $totalInvestment,        // D181
                'total_investor_due' => $totalActualDue,         // AG182
                'my_profit' => $myProfit,              // AG184
                'my_profit_ratio' => round($myProfitRatio, 2), // AG186
            ],
            'details_count' => count($details),
        ];
    }
}
