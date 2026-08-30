<?php

namespace App\Services;

use App\Models\Director;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlySectorProfit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LedgerUpdateService — post-calculation ledger synchronization.
 *
 * After the 8-phase ProfitCalculatorService completes, this service updates
 * the due ledgers to reflect the computed profit distribution:
 *
 * 1. INVESTOR PROFIT DUE LEDGER — advance_difference (AH) per investor
 * 2. SECTOR PROFIT DUE LEDGER — variance (Y = Z − X) per sector
 * 3. DIRECTOR (M/Y) DUE LEDGER — M/Y profit (AG184)
 *
 * Rollback-on-re-finalize: reads old values from monthly due tables BEFORE
 * the new calculation, reverses them, then applies the new values.
 */
class LedgerUpdateService
{
    /**
     * Rollback old ledger entries for a month (called BEFORE re-calculation).
     *
     * Only runs if previous calculation results exist (investor_monthly_profit_details).
     * Reads old values from the monthly due tables and reverses them.
     */
    public function rollback(string $profitMonth): void
    {
        $oldDetails = InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->get();

        if ($oldDetails->isEmpty()) {
            return; // First finalization — nothing to roll back
        }

        // 1. Rollback investor profit due ledgers (old advance_difference)
        foreach ($oldDetails as $detail) {
            $oldAdvanceDiff = (float) $detail->advance_difference;
            if ($oldAdvanceDiff != 0) {
                $this->updateInvestorProfitDue($detail->investor_id, -$oldAdvanceDiff, $profitMonth);
            }
        }

        // 2. Rollback sector profit due ledgers (read old monthly entries)
        $oldSectorMonthlyDues = DB::table('sector_profit_monthly_due')
            ->where('due_month', $profitMonth)
            ->get();
        foreach ($oldSectorMonthlyDues as $smd) {
            $oldDue = (float) $smd->due;
            if ($oldDue != 0) {
                $this->updateSectorProfitDue($smd->sector_id, -$oldDue, $profitMonth);
            }
        }

        // 3. Rollback M/Y profit from director due ledger
        $oldSummary = DB::table('monthly_profit_summary')->where('profit_month', $profitMonth)->first();
        if ($oldSummary && (float) $oldSummary->my_profit != 0) {
            $myDirector = Director::where('is_my', true)->first();
            if ($myDirector) {
                $this->updateDirectorDue($myDirector->id, -(float) $oldSummary->my_profit, $profitMonth);
            }
        }

        Log::info('Ledger rollback completed', [
            'month' => $profitMonth,
            'investors_rolled' => $oldDetails->count(),
        ]);
    }

    /**
     * Apply new ledger entries after calculation (called AFTER Phases 1-8 complete).
     *
     * @param  string  $profitMonth  'YYYY-MM-DD' (1st of month)
     * @param  float  $myProfit  M/Y profit (AG184)
     */
    public function apply(string $profitMonth, float $myProfit): void
    {
        // 1. Apply investor profit due ledgers (new advance_difference)
        $details = InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->get();

        foreach ($details as $detail) {
            $advanceDiff = (float) $detail->advance_difference;
            if ($advanceDiff != 0) {
                $this->updateInvestorProfitDue($detail->investor_id, $advanceDiff, $profitMonth);
            }
        }

        // 2. Apply sector profit due ledgers (new Y = Z - X per sector)
        $sectorProfits = MonthlySectorProfit::forMonth($profitMonth)->get();
        foreach ($sectorProfits as $sp) {
            $variance = (float) $sp->estimated_profit - (float) $sp->actual_profit;
            if ($variance != 0) {
                $this->updateSectorProfitDue($sp->sector_id, $variance, $profitMonth);
            }
        }

        // 3. Apply M/Y profit to director due ledger
        if ($myProfit != 0) {
            $myDirector = Director::where('is_my', true)->first();
            if ($myDirector) {
                $this->updateDirectorDue($myDirector->id, $myProfit, $profitMonth);
            }
        }

        Log::info('Ledger apply completed', [
            'month' => $profitMonth,
            'investors_applied' => $details->count(),
            'sectors_applied' => $sectorProfits->count(),
            'my_profit' => $myProfit,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Raw DB upsert helpers (portable across SQLite/MySQL/Postgres)
    |--------------------------------------------------------------------------
    */

    private function updateInvestorProfitDue(int $investorId, float $amount, string $month): void
    {
        $this->upsertMonthly('investor_profit_monthly_due', 'investor_id', $investorId, $amount, $month);
        $this->upsertCumulative('investor_profit_due_ledger', 'investor_id', $investorId, $amount);
    }

    private function updateSectorProfitDue(int $sectorId, float $amount, string $month): void
    {
        $this->upsertMonthly('sector_profit_monthly_due', 'sector_id', $sectorId, $amount, $month);
        $this->upsertCumulative('sector_profit_due_ledger', 'sector_id', $sectorId, $amount);
    }

    private function updateDirectorDue(int $directorId, float $amount, string $month): void
    {
        $this->upsertMonthly('director_monthly_due', 'director_id', $directorId, $amount, $month);
        $this->upsertCumulative('director_due_ledger', 'director_id', $directorId, $amount);
    }

    private function upsertMonthly(string $table, string $column, int $entityId, float $amount, string $month): void
    {
        $existing = DB::table($table)
            ->where($column, $entityId)
            ->where('due_month', $month)
            ->first();

        if ($existing) {
            DB::table($table)
                ->where($column, $entityId)
                ->where('due_month', $month)
                ->update(['due' => DB::raw("due + {$amount}")]);
        } else {
            DB::table($table)->insert([
                $column => $entityId,
                'due_month' => $month,
                'due' => $amount,
            ]);
        }
    }

    private function upsertCumulative(string $table, string $column, int $entityId, float $amount): void
    {
        $existing = DB::table($table)->where($column, $entityId)->first();

        if ($existing) {
            DB::table($table)
                ->where($column, $entityId)
                ->update([
                    'due' => DB::raw("due + {$amount}"),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table($table)->insert([
                $column => $entityId,
                'due' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
