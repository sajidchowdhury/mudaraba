<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\RetainedEarnings;
use App\Models\RetainedEarningsDistribution;
use App\Services\AuditService;
use App\Services\LedgerUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MonthResetController extends Controller
{
    public function __construct(
        private readonly LedgerUpdateService $ledgerUpdateService,
    ) {}

    /**
     * Display the Month Reset page.
     * Shows which months have calculation data and allows deleting them.
     */
    public function index(Request $request): Response
    {
        // Find all months that have calculation data
        $months = MonthlyProfitSummary::orderByDesc('profit_month')
            ->get()
            ->map(function ($summary) {
                $detailCount = InvestorMonthlyProfitDetail::where('profit_month', $summary->profit_month)->count();
                $sectorProfitCount = MonthlySectorProfit::where('profit_month', $summary->profit_month)->count();
                $hasRetainedEarnings = RetainedEarnings::where('profit_month', $summary->profit_month)->exists();
                $isLocked = $summary->status->value === 'locked';

                return [
                    'profit_month' => $summary->profit_month,
                    'month_label' => date('F, Y', strtotime($summary->profit_month)),
                    'status' => $summary->status->value,
                    'total_estimated' => (float) $summary->total_estimated_profit,
                    'total_actual' => (float) $summary->total_actual_profit,
                    'my_profit' => (float) $summary->my_profit,
                    'my_profit_ratio' => (float) $summary->my_profit_ratio,
                    'investor_count' => $detailCount,
                    'sector_count' => $sectorProfitCount,
                    'has_retained_earnings' => $hasRetainedEarnings,
                    'is_locked' => $isLocked,
                    'can_delete' => !$isLocked,
                ];
            });

        return Inertia::render('MonthReset/Index', [
            'months' => $months,
        ]);
    }

    /**
     * Delete a month's entire calculation data.
     *
     * This is a DESTRUCTIVE operation that:
     * 1. Rolls back ALL ledger changes (investor profit due, sector profit due, M/Y due)
     * 2. Deletes investor_monthly_profit_details for the month
     * 3. Deletes monthly_profit_summary for the month
     * 4. Deletes retained_earnings + distributions for the month
     * 5. Resets monthly_sector_profit to "draft" status
     * 6. Writes an audit log entry
     *
     * Safety:
     * - Blocked if the month is LOCKED (must unlock first via Month Close page)
     * - Wrapped in a DB transaction (atomic — if anything fails, nothing changes)
     * - Writes an audit log entry with action 'reset_month'
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'profit_month' => ['required', 'date'],
        ]);

        $profitMonth = date('Y-m-01', strtotime($request->input('profit_month')));
        $userId = $request->user()->id;

        // ── Safety check: block locked months ──────────────────────────
        $summary = MonthlyProfitSummary::find($profitMonth);
        if ($summary && $summary->status->value === 'locked') {
            return redirect()
                ->back()
                ->with('error', "Cannot delete — month is LOCKED. Unlock it first via the Month Close page.");
        }

        // ── Safety check: require confirmation text ────────────────────
        $confirmText = $request->input('confirm_text');
        $monthLabel = date('F Y', strtotime($profitMonth));
        if ($confirmText !== "DELETE {$monthLabel}") {
            return redirect()
                ->back()
                ->with('error', "Confirmation text must be exactly: DELETE {$monthLabel}");
        }

        // ── Snapshot what we're about to delete (for audit log) ────────
        $detailCount = InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->count();
        $sectorProfitCount = MonthlySectorProfit::where('profit_month', $profitMonth)->count();
        $retainedEarnings = RetainedEarnings::where('profit_month', $profitMonth)->first();

        if (!$summary && $detailCount === 0) {
            return redirect()
                ->back()
                ->with('error', 'No calculation data found for this month.');
        }

        // ── Do the deletion in a transaction ──────────────────────────
        try {
            DB::transaction(function () use (
                $profitMonth, $userId, $summary, $detailCount, $sectorProfitCount, $retainedEarnings
            ) {
                // 1. Rollback ALL ledger changes (reverses investor profit due,
                //    sector profit due, M/Y due — uses the proven rollback() method)
                $this->ledgerUpdateService->rollback($profitMonth);

                // 2. Delete investor monthly profit details
                InvestorMonthlyProfitDetail::where('profit_month', $profitMonth)->delete();

                // 3. Delete monthly profit summary
                if ($summary) {
                    $summary->delete();
                }

                // 4. Delete retained earnings + distributions
                if ($retainedEarnings) {
                    RetainedEarningsDistribution::where('profit_month', $profitMonth)->delete();
                    $retainedEarnings->delete();
                }

                // 5. Reset monthly sector profits to "draft" status
                //    (keep the estimated/actual values, but un-finalize them
                //    so they can be re-edited)
                MonthlySectorProfit::where('profit_month', $profitMonth)
                    ->update([
                        'status' => 'draft',
                        'finalized_by' => null,
                        'finalized_at' => null,
                    ]);

                // 6. Write audit log entry
                AuditService::log(
                    action: 'reset_month',
                    model: $summary ?? new MonthlyProfitSummary(['profit_month' => $profitMonth]),
                    before: [
                        'profit_month' => $profitMonth,
                        'investor_details_deleted' => $detailCount,
                        'sector_profits_reset' => $sectorProfitCount,
                        'retained_earnings_deleted' => $retainedEarnings ? (float) $retainedEarnings->total_amount : 0,
                        'summary_deleted' => $summary ? true : false,
                    ],
                    after: null,
                    userId: $userId,
                );
            });

            Log::info('Month calculation deleted', [
                'month' => $profitMonth,
                'investor_details_deleted' => $detailCount,
                'sector_profits_reset' => $sectorProfitCount,
                'user_id' => $userId,
            ]);

            return redirect()
                ->back()
                ->with('success', "All calculation data for {$monthLabel} has been deleted. Sector profits reset to draft. Ledger entries rolled back.");

        } catch (\Exception $e) {
            Log::error('Month reset failed', [
                'month' => $profitMonth,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Delete failed: ' . $e->getMessage() . ' — all changes rolled back (nothing was deleted).');
        }
    }
}
