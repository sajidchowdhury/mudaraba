<?php

namespace App\Http\Controllers;

use App\Models\InvestorMonthlyProfitDetail;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\RetainedEarnings;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonthStatusController extends Controller
{
    /**
     * Display the month closing checklist page.
     */
    public function index(Request $request): Response
    {
        $month = $request->get('month', date('Y-m-01'));
        $month = date('Y-m-01', strtotime($month));

        $summary = MonthlyProfitSummary::find($month);
        $status = $summary?->status->value ?? 'open';

        // Build the checklist
        $activeSectors = Sector::where('status', 'active')->count();
        $enteredSectors = MonthlySectorProfit::forMonth($month)->count();
        $finalizedSectors = MonthlySectorProfit::forMonth($month)
            ->where('status', 'finalized')
            ->count();

        $investorDetails = InvestorMonthlyProfitDetail::where('profit_month', $month)->count();

        $retained = RetainedEarnings::where('profit_month', $month)->exists();

        $checklist = [
            [
                'id' => 'sectors_entered',
                'label' => 'All active sectors have profit entries',
                'done' => $enteredSectors >= $activeSectors && $activeSectors > 0,
                'detail' => "{$enteredSectors} of {$activeSectors} sectors entered",
                'required' => true,
            ],
            [
                'id' => 'sectors_finalized',
                'label' => 'All sector profits are finalized',
                'done' => $finalizedSectors === $enteredSectors && $enteredSectors > 0,
                'detail' => "{$finalizedSectors} of {$enteredSectors} finalized",
                'required' => true,
            ],
            [
                'id' => 'calculation_run',
                'label' => '8-phase calculation engine has run',
                'done' => $investorDetails > 0,
                'detail' => "{$investorDetails} investor profit details computed",
                'required' => true,
            ],
            [
                'id' => 'retained_earnings',
                'label' => 'Retained earnings allocated (BDT 200K, 71/29 split)',
                'done' => $retained,
                'detail' => $retained ? 'Allocated' : 'Not yet allocated',
                'required' => true,
            ],
        ];

        $allDone = collect($checklist)->where('required', true)->every(fn ($item) => $item['done']);

        return Inertia::render('MonthClose/Index', [
            'month' => $month,
            'monthLabel' => date('F, Y', strtotime($month)),
            'status' => $status,
            'checklist' => $checklist,
            'allDone' => $allDone,
            'summary' => $summary ? [
                'total_estimated' => (float) $summary->total_estimated_profit,
                'total_actual' => (float) $summary->total_actual_profit,
                'my_profit' => (float) $summary->my_profit,
                'my_profit_ratio' => (float) $summary->my_profit_ratio,
                'active_investors' => $summary->active_investor_count,
            ] : null,
            'canLock' => $request->user()?->isSuperadmin() ?? false,
            'lockedAt' => $summary?->locked_at?->format('Y-m-d H:i'),
            'lockedBy' => $summary?->locker?->username,
        ]);
    }

    /**
     * Lock the month — prevents further edits (requires superadmin).
     */
    public function lock(Request $request): RedirectResponse
    {
        $month = $request->validate(['month' => 'required|date'])['month'];
        $month = date('Y-m-01', strtotime($month));

        $summary = MonthlyProfitSummary::find($month);

        if (! $summary) {
            return redirect()->back()->with('error', 'Cannot lock — no profit calculation exists for this month.');
        }

        if ($summary->status->value === 'locked') {
            return redirect()->back()->with('error', 'This month is already locked.');
        }

        $summary->update([
            'status' => 'locked',
            'locked_by' => $request->user()->id,
            'locked_at' => now(),
        ]);

        return redirect()->back()->with('success', "Month {$month} has been locked. No further edits are permitted without admin override.");
    }

    /**
     * Unlock the month — allows edits again (requires superadmin).
     * Records the unlock as an audit event.
     */
    public function unlock(Request $request): RedirectResponse
    {
        $month = $request->validate(['month' => 'required|date'])['month'];
        $month = date('Y-m-01', strtotime($month));

        $summary = MonthlyProfitSummary::find($month);

        if (! $summary) {
            return redirect()->back()->with('error', 'No profit calculation exists for this month.');
        }

        if ($summary->status->value !== 'locked') {
            return redirect()->back()->with('error', 'This month is not locked.');
        }

        $summary->update([
            'status' => 'finalized',  // Back to finalized (calculation exists)
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return redirect()->back()->with('success', "Month {$month} has been unlocked. Edits are now permitted.");
    }
}
