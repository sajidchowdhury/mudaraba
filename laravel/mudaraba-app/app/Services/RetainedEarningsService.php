<?php

namespace App\Services;

use App\Models\InvestorMonthlyProfitDetail;
use App\Models\RetainedEarnings;
use App\Models\RetainedEarningsDistribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RetainedEarningsService — implements Phases 5-7 of the 8-phase engine.
 *
 * === Retained Earnings Mechanism (per plan §5.1) ===
 *
 * PHASE 5 — Retained earnings allocation
 *   RE_total = 200,000              (configurable per month, Excel AI3)
 *   RE_investors = RE_total × 71%   (Excel AJ4 = 142,000)
 *   RE_my        = RE_total × 29%   (Excel AK4 = 58,000)
 *   retained_credit[i] = RE_investors × ratio[i]   (Excel AJ per investor)
 *
 * PHASE 6 — Net settlement
 *   net[i] = advance_diff[i] − retained_credit[i]   (Excel AK)
 *   positive → investor owes M/Y (after retained credit)
 *   negative → M/Y owes investor
 *
 * PHASE 7 — Aggregates
 *   AJ182 = Σ retained_credit[i]   (total investor retained credit)
 *   AH182 = Σ advance_diff[i]       (total investor over-payment)
 *
 * This service is called AFTER ProfitCalculatorService has computed
 * Phases 1-4 (ratio, primary, actual_due, advance_diff). It:
 *   1. Creates/updates the retained_earnings row for the month
 *   2. Distributes the investor portion by ratio
 *   3. Updates each investor_monthly_profit_details row with
 *      retained_earnings_credit (AJ) and net_settlement (AK)
 *   4. Updates monthly_profit_summary with AJ182, AH182
 */
class RetainedEarningsService
{
    /**
     * Default retained earnings amount per month (BDT 200,000).
     */
    private const DEFAULT_TOTAL = 200000;

    /**
     * Default split percentages.
     */
    private const DEFAULT_INVESTOR_PCT = 71.0;

    private const DEFAULT_MY_PCT = 29.0;

    /**
     * Allocate retained earnings for a finalized month.
     *
     * @param  string  $profitMonth  'YYYY-MM-DD' (1st of month)
     * @param  float  $totalAmount  Total retained earnings (default 200,000)
     * @param  float  $investorPct  Investor portion % (default 71)
     * @param  float  $myPct  M/Y portion % (default 29)
     * @param  int  $userId  The user triggering the allocation
     * @return array{total: float, investor_portion: float, my_portion: float, distributed_count: int}
     */
    public function allocate(
        string $profitMonth,
        int $userId,
        float $totalAmount = self::DEFAULT_TOTAL,
        float $investorPct = self::DEFAULT_INVESTOR_PCT,
        float $myPct = self::DEFAULT_MY_PCT,
    ): array {
        $batchUuid = Str::uuid()->toString();

        // PHASE 5 — Create/update the retained_earnings row for this month
        $retainedEarnings = RetainedEarnings::updateOrCreate(
            ['profit_month' => $profitMonth],
            [
                'total_amount' => $totalAmount,
                'investor_portion_pct' => $investorPct,
                'my_portion_pct' => $myPct,
                'created_by' => $userId,
            ],
        );

        // Compute the split amounts (Excel AJ4, AK4)
        $investorPortion = $retainedEarnings->investor_portion_amount; // 142,000
        $myPortion = $retainedEarnings->my_portion_amount;             // 58,000

        // Load all investor profit details for this month (already computed by ProfitCalculatorService)
        $details = InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->get();

        if ($details->isEmpty()) {
            throw new \RuntimeException("No investor profit details found for month {$profitMonth}. Run ProfitCalculatorService first.");
        }

        // PHASE 5 — Distribute investor portion by ratio + compute net settlement
        DB::transaction(function () use (
            $details, $investorPortion, $profitMonth, $batchUuid
        ) {
            // Delete old distributions for this month (clean recompute)
            RetainedEarningsDistribution::where('profit_month', $profitMonth)->delete();

            $totalRetainedCredit = 0;
            $totalAdvanceDiff = 0;

            foreach ($details as $detail) {
                // Phase 5: retained_earnings_credit = investor_portion × ratio (Excel AJ)
                $retainedCredit = $investorPortion * (float) $detail->investment_ratio;
                $totalRetainedCredit += $retainedCredit;

                // Phase 6: net_settlement = advance_difference − retained_credit (Excel AK)
                $advanceDiff = (float) $detail->advance_difference;
                $totalAdvanceDiff += $advanceDiff;
                $netSettlement = $advanceDiff - $retainedCredit;

                // Update the detail row
                $detail->update([
                    'retained_earnings_credit' => round($retainedCredit, 2),
                    'net_settlement' => round($netSettlement, 2),
                ]);

                // Create the distribution record
                RetainedEarningsDistribution::create([
                    'profit_month' => $profitMonth,
                    'investor_id' => $detail->investor_id,
                    'investment_ratio' => $detail->investment_ratio,
                    'amount' => round($retainedCredit, 2),
                    'batch_uuid' => $batchUuid,
                ]);
            }

            // PHASE 7 — Update monthly_profit_summary with aggregates
            DB::table('monthly_profit_summary')->where('profit_month', $profitMonth)->update([
                'total_investor_advance_diff' => round($totalAdvanceDiff, 2),    // AH182
                'total_investor_retained' => round($totalRetainedCredit, 2), // AJ182
                'updated_at' => now(),
            ]);
        });

        Log::info('Retained earnings allocated', [
            'month' => $profitMonth,
            'total' => $totalAmount,
            'investor_portion' => $investorPortion, // AJ4
            'my_portion' => $myPortion,       // AK4
            'distributed_count' => $details->count(),
            'batch_uuid' => $batchUuid,
        ]);

        return [
            'total' => $totalAmount,
            'investor_portion' => $investorPortion,
            'my_portion' => $myPortion,
            'distributed_count' => $details->count(),
        ];
    }
}
