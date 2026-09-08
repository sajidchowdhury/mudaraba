<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OpeningBalanceController extends Controller
{
    /**
     * Display the opening balances page — M/Y, Investor, and Sector in one place.
     */
    public function index(Request $request): Response
    {
        // Directors (M/Y) with their current due ledger
        $directors = Director::with('dueLedger')
            ->orderByDesc('is_my')
            ->orderBy('name')
            ->get()
            ->map(fn (Director $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'is_my' => (bool) $d->is_my,
                'due' => (float) ($d->dueLedger?->due ?? 0),
                'has_ledger' => (bool) $d->dueLedger,
            ]);

        // Investors with their capital + profit due ledgers
        $investors = Investor::with(['dueLedger', 'profitDueLedger'])
            ->where('status', '!=', 'closed')
            ->orderBy('name')
            ->get()
            ->map(fn (Investor $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
                'deed_ratio' => $i->deed_ratio,
                'capital_due' => (float) ($i->dueLedger?->due ?? 0),
                'profit_due' => (float) ($i->profitDueLedger?->due ?? 0),
                'has_capital_ledger' => (bool) $i->dueLedger,
                'has_profit_ledger' => (bool) $i->profitDueLedger,
            ]);

        // Sectors with their due ledgers
        $sectors = Sector::with(['dueLedger', 'profitDueLedger'])
            ->where('status', '!=', 'closed')
            ->orderBy('name')
            ->get()
            ->map(fn (Sector $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'capital_due' => (float) ($s->dueLedger?->due ?? 0),
                'profit_due' => (float) ($s->profitDueLedger?->due ?? 0),
                'has_capital_ledger' => (bool) $s->dueLedger,
                'has_profit_ledger' => (bool) $s->profitDueLedger,
            ]);

        // Totals
        $totalInvestorCapital = $investors->sum('capital_due');
        $totalInvestorProfit = $investors->sum('profit_due');
        $totalSectorCapital = $sectors->sum('capital_due');
        $totalSectorProfit = $sectors->sum('profit_due');
        $totalDirectorDue = $directors->sum('due');

        return Inertia::render('OpeningBalances/Index', [
            'directors' => $directors,
            'investors' => $investors,
            'sectors' => $sectors,
            'totals' => [
                'investor_capital' => $totalInvestorCapital,
                'investor_profit' => $totalInvestorProfit,
                'sector_capital' => $totalSectorCapital,
                'sector_profit' => $totalSectorProfit,
                'director_due' => $totalDirectorDue,
            ],
            'canEdit' => $request->user()?->isSuperadmin() ?? false,
        ]);
    }

    /**
     * Update M/Y (director) opening balance.
     */
    public function updateDirector(Request $request, Director $director): RedirectResponse
    {
        $data = $request->validate([
            'due' => ['required', 'numeric'],
        ]);

        DirectorDueLedger::updateOrCreate(
            ['director_id' => $director->id],
            ['due' => (float) $data['due'], 'updated_at' => now()],
        );

        return redirect()->back()->with('success', "Opening balance for {$director->name} updated.");
    }

    /**
     * Bulk update investor opening balances (capital + profit).
     * Accepts an array of {id, capital_due, profit_due}.
     */
    public function updateInvestors(Request $request): RedirectResponse
    {
        $items = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:investors,id'],
            'items.*.capital_due' => ['required', 'numeric'],
            'items.*.profit_due' => ['nullable', 'numeric'],
        ])['items'];

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                InvestorDueLedger::updateOrCreate(
                    ['investor_id' => $item['id']],
                    ['due' => (float) $item['capital_due'], 'updated_at' => now()],
                );

                if (isset($item['profit_due']) && (float) $item['profit_due'] !== 0.0) {
                    InvestorProfitDueLedger::updateOrCreate(
                        ['investor_id' => $item['id']],
                        ['due' => (float) $item['profit_due'], 'updated_at' => now()],
                    );
                }
            }
        });

        $count = count($items);

        return redirect()->back()->with('success', "{$count} investor opening balances updated.");
    }

    /**
     * Bulk update sector opening balances (capital + profit).
     */
    public function updateSectors(Request $request): RedirectResponse
    {
        $items = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:sectors,id'],
            'items.*.capital_due' => ['required', 'numeric'],
            'items.*.profit_due' => ['nullable', 'numeric'],
        ])['items'];

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                SectorDueLedger::updateOrCreate(
                    ['sector_id' => $item['id']],
                    ['due' => (float) $item['capital_due'], 'updated_at' => now()],
                );

                if (isset($item['profit_due']) && (float) $item['profit_due'] !== 0.0) {
                    SectorProfitDueLedger::updateOrCreate(
                        ['sector_id' => $item['id']],
                        ['due' => (float) $item['profit_due'], 'updated_at' => now()],
                    );
                }
            }
        });

        $count = count($items);

        return redirect()->back()->with('success', "{$count} sector opening balances updated.");
    }
}
