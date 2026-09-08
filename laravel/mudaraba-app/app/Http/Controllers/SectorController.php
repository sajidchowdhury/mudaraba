<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectorRequest;
use App\Http\Requests\UpdateSectorRequest;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectorController extends Controller
{
    /**
     * Display a paginated, searchable list of sectors.
     */
    public function index(Request $request): Response
    {
        // Use 'like' for portability across SQLite/MySQL/Postgres
        $sectors = Sector::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->with('dueLedger')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sectors/Index', [
            'sectors' => $sectors->through(fn (Sector $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'mobile' => $s->mobile,
                'status' => $s->status,
                'current_balance' => (float) ($s->dueLedger?->due ?? 0),
                'created_at' => $s->created_at?->format('Y-m-d'),
            ]),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sectors/Create');
    }

    public function store(StoreSectorRequest $request): RedirectResponse
    {
        $sector = Sector::create($request->validated());

        return redirect()
            ->route('sectors.show', $sector)
            ->with('success', "Sector {$sector->name} created successfully.");
    }

    /**
     * Display the sector's full profile + tabs.
     */
    public function show(Sector $sector): Response
    {
        $sector->load([
            'sectorInvestments' => fn ($q) => $q->orderBy('transaction_date', 'desc')->limit(20),
            'monthlySectorProfits' => fn ($q) => $q->orderBy('profit_month', 'desc')->limit(12),
            'dueLedger',
            'profitDueLedger',
        ]);

        $balance = (float) ($sector->dueLedger?->due ?? 0);
        $profitDue = (float) ($sector->profitDueLedger?->due ?? 0);

        return Inertia::render('Sectors/Show', [
            'sector' => [
                'id' => $sector->id,
                'name' => $sector->name,
                'mobile' => $sector->mobile,
                'address' => $sector->address,
                'status' => $sector->status,
                'created_at' => $sector->created_at?->format('Y-m-d'),
                'updated_at' => $sector->updated_at?->format('Y-m-d'),
            ],
            'stats' => [
                'current_balance' => $balance,
                'profit_due' => $profitDue,
                'investment_count' => $sector->sectorInvestments->count(),
                'profit_records' => $sector->monthlySectorProfits->count(),
            ],
            'recentInvestments' => $sector->sectorInvestments->map(fn ($i) => [
                'id' => $i->id,
                'amount' => (float) $i->amount,
                'type' => $i->type->value,
                'transaction_date' => $i->transaction_date?->format('Y-m-d'),
                'remarks' => $i->remarks,
            ]),
            'recentProfit' => $sector->monthlySectorProfits->map(fn ($p) => [
                'profit_month' => $p->profit_month,
                'estimated_profit' => (float) $p->estimated_profit,
                'actual_profit' => (float) $p->actual_profit,
                'advance_difference' => (float) $p->advanceDifference(),
                'status' => $p->status->value,
            ]),
        ]);
    }

    public function edit(Sector $sector): Response
    {
        return Inertia::render('Sectors/Edit', [
            'sector' => [
                'id' => $sector->id,
                'name' => $sector->name,
                'mobile' => $sector->mobile,
                'address' => $sector->address,
                'status' => $sector->status,
            ],
        ]);
    }

    public function update(UpdateSectorRequest $request, Sector $sector): RedirectResponse
    {
        $sector->update($request->validated());

        return redirect()
            ->route('sectors.show', $sector)
            ->with('success', "Sector {$sector->name} updated successfully.");
    }

    /**
     * Soft-delete (never hard-delete financial records).
     */
    public function destroy(Sector $sector): RedirectResponse
    {
        $name = $sector->name;
        $sector->delete();

        return redirect()
            ->route('sectors.index')
            ->with('success', "Sector {$name} deactivated.");
    }
}
