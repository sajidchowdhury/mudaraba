<?php

namespace App\Http\Controllers;

use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\RetainedEarnings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorProfitController extends Controller
{
    /**
     * Display the investor profit grid for a given month — the "For Sajid" page.
     *
     * Shows a premium spreadsheet-like grid with all investors × all 8-phase
     * calculation columns, sticky totals (AG182, AH182, AG184, AG186),
     * and color-coded advance_difference.
     */
    public function index(Request $request): Response
    {
        $month = $request->get('month', date('Y-m-01'));
        $month = date('Y-m-01', strtotime($month));

        // Load investor profit details for this month
        $details = InvestorMonthlyProfitDetail::where('profit_month', $month)
            ->with('investor:id,name,reference,deed_ratio')
            ->orderByDesc('investment')
            ->get();

        // Load monthly summary (Z2, X2, Y2, AG182, AH182, AJ182, AG184, AG186, D181)
        $summary = MonthlyProfitSummary::find($month);

        // Load retained earnings for this month (AI3, AJ4, AK4)
        $retainedEarnings = RetainedEarnings::where('profit_month', $month)->first();

        // Build the grid data
        $grid = $details->map(fn ($d) => [
            'investor_id' => $d->investor_id,
            'investor_name' => $d->investor?->name ?? '—',
            'reference' => $d->investor?->reference,
            'deed_ratio' => $d->investor?->deed_ratio ?? $d->deed_ratio,
            // Phase 1-2: Investment + ratio
            'investment' => (float) $d->investment,              // D
            'investment_ratio' => (float) $d->investment_ratio,         // E
            'primary_profit_share' => (float) $d->primary_profit_share,    // Q/F
            // Phase 3: Actual + tier
            'actual_profit_at_full' => (float) $d->actual_profit_at_full,    // N
            'deed_ratio_applied' => (float) $d->deed_ratio,               // AF
            'actual_profit_due' => (float) $d->actual_profit_due,        // AG
            // Phase 4: Advance difference
            'advance_difference' => (float) $d->advance_difference,        // AH
            // Phase 5-6: Retained + net
            'retained_earnings_credit' => (float) $d->retained_earnings_credit, // AJ
            'net_settlement' => (float) $d->net_settlement,            // AK
        ]);

        // Build summary (Excel totals row)
        $totals = $summary ? [
            'total_estimated' => (float) $summary->total_estimated_profit,      // Z2
            'total_actual' => (float) $summary->total_actual_profit,         // X2
            'total_variance' => (float) $summary->total_advance_difference,   // Y2
            'total_investment' => (float) $summary->total_mudaraba_investment,   // D181
            'total_profit_due' => (float) $summary->total_investor_profit_due,   // AG182
            'total_advance_diff' => (float) $summary->total_investor_advance_diff, // AH182
            'total_retained' => (float) $summary->total_investor_retained,     // AJ182
            'my_profit' => (float) $summary->my_profit,                   // AG184
            'my_profit_ratio' => (float) $summary->my_profit_ratio,             // AG186
            'active_investor_count' => (int) $summary->active_investor_count,
            'status' => $summary->status->value,
        ] : null;

        $retained = $retainedEarnings ? [
            'total_amount' => (float) $retainedEarnings->total_amount,        // AI3
            'investor_portion' => $retainedEarnings->investor_portion_amount,   // AJ4
            'my_portion' => $retainedEarnings->my_portion_amount,         // AK4
        ] : null;

        $isCalculated = $details->isNotEmpty() && $summary !== null;

        return Inertia::render('InvestorProfit/Index', [
            'month' => $month,
            'monthLabel' => date('F, Y', strtotime($month)),
            'grid' => $grid,
            'totals' => $totals,
            'retained' => $retained,
            'isCalculated' => $isCalculated,
            'canEdit' => $request->user()?->isSuperadmin() || $request->user()?->canEdit('profit.investor') ?? false,
        ]);
    }
}
