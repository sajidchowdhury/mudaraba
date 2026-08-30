<?php

namespace App\Http\Controllers;

use App\Enums\SectorProfitStatus;
use App\Http\Requests\StoreSectorProfitRequest;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SectorProfitController extends Controller
{
    /**
     * Display the sector profit entry page for a given month.
     *
     * Shows an Excel-like grid: 17 sectors × (estimated_profit, actual_profit)
     * with live totals (Z2, X2, Y2).
     */
    public function index(Request $request): Response
    {
        $month = $request->get('month', date('Y-m-01'));

        // Ensure month is in 'YYYY-MM-01' format
        $month = date('Y-m-01', strtotime($month));

        // Get all active sectors
        $sectors = Sector::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get existing profit entries for this month
        $existing = MonthlySectorProfit::forMonth($month)
            ->get()
            ->keyBy('sector_id');

        // Build the grid data
        $grid = $sectors->map(function (Sector $sector) use ($existing) {
            $entry = $existing->get($sector->id);

            return [
                'sector_id' => $sector->id,
                'sector_name' => $sector->name,
                'estimated_profit' => $entry ? (float) $entry->estimated_profit : 0,
                'actual_profit' => $entry ? (float) $entry->actual_profit : 0,
                'status' => $entry ? $entry->status->value : 'draft',
                'exists' => (bool) $entry,
            ];
        });

        // Compute live totals
        $totalEstimated = $grid->sum('estimated_profit');
        $totalActual = $grid->sum('actual_profit');
        $totalVariance = $totalEstimated - $totalActual; // Y2 = Z2 - X2

        // Check if the month is finalized
        $finalizedCount = $existing->filter(fn ($e) => $e->status === SectorProfitStatus::Finalized)->count();
        $isFinalized = $finalizedCount > 0 && $finalizedCount === $sectors->count();

        return Inertia::render('SectorProfit/Index', [
            'month' => $month,
            'monthLabel' => date('F, Y', strtotime($month)),
            'grid' => $grid,
            'totals' => [
                'estimated' => (float) $totalEstimated,  // Excel Z2
                'actual' => $totalActual,     // Excel X2
                'variance' => $totalVariance,   // Excel Y2
            ],
            'isFinalized' => $isFinalized,
            'canEdit' => $request->user()?->isSuperadmin() || $request->user()?->canEdit('profit.sector') ?? false,
        ]);
    }

    /**
     * Store (or update) sector profit entries for a month — batch save.
     *
     * On 'finalize': sets status to 'finalized' for all entries.
     * On 'draft': saves with status 'draft' (can be re-saved later).
     */
    public function store(StoreSectorProfitRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $month = $data['profit_month'];
        $finalize = $data['finalize'] ?? false;
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $month, $finalize, $userId) {
            foreach ($data['items'] as $item) {
                // Skip entries with both estimated and actual = 0 (no data entered)
                if ((float) $item['estimated_profit'] === 0.0 && (float) ($item['actual_profit'] ?? 0) === 0.0) {
                    continue;
                }

                MonthlySectorProfit::updateOrCreate(
                    [
                        'sector_id' => $item['sector_id'],
                        'profit_month' => $month,
                    ],
                    [
                        'estimated_profit' => $item['estimated_profit'],
                        'actual_profit' => $item['actual_profit'] ?? 0,
                        'status' => $finalize ? SectorProfitStatus::Finalized : SectorProfitStatus::Draft,
                        'transaction_date' => now(),
                        'finalized_by' => $finalize ? $userId : null,
                        'finalized_at' => $finalize ? now() : null,
                        'created_by' => $userId,
                    ],
                );
            }
        });

        $action = $finalize ? 'finalized' : 'saved as draft';
        $monthLabel = date('F, Y', strtotime($month));

        return redirect()
            ->back()
            ->with('success', "Sector profits for {$monthLabel} {$action} successfully.");
    }
}
