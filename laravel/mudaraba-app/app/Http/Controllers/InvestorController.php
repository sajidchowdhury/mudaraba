<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestorRequest;
use App\Http\Requests\UpdateInvestorRequest;
use App\Models\Investor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorController extends Controller
{
    /**
     * Display a paginated, searchable list of investors.
     */
    public function index(Request $request): Response
    {
        // Use 'like' for portability (works on SQLite, MySQL, Postgres).
        $investors = Investor::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->deed_ratio, fn ($q, $ratio) => $q->where('deed_ratio', $ratio))
            ->with('dueLedger')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Investors/Index', [
            'investors' => $investors->through(fn (Investor $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
                'mobile' => $i->mobile,
                'deed_ratio' => $i->deed_ratio,
                'status' => $i->status,
                'start_profit_month' => $i->start_profit_month?->format('Y-m-d'),
                'end_profit_month' => $i->end_profit_month?->format('Y-m-d'),
                'current_balance' => (float) ($i->dueLedger?->due ?? 0),
                'created_at' => $i->created_at?->format('Y-m-d'),
            ]),
            'filters' => $request->only(['search', 'status', 'deed_ratio']),
        ]);
    }

    /**
     * Show the form for creating a new investor.
     */
    public function create(): Response
    {
        return Inertia::render('Investors/Create');
    }

    /**
     * Store a newly created investor.
     */
    public function store(StoreInvestorRequest $request): RedirectResponse
    {
        $investor = Investor::create($request->validated());

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', "Investor {$investor->name} created successfully.");
    }

    /**
     * Display the investor's full profile + tabs.
     */
    public function show(Investor $investor): Response
    {
        $investor->load([
            'investmentTransactions' => fn ($q) => $q->orderBy('transaction_date', 'desc')->limit(20),
            'monthlyProfitDetails' => fn ($q) => $q->orderBy('profit_month', 'desc')->limit(12),
            'dueLedger',
            'profitDueLedger',
        ]);

        $balance = (float) ($investor->dueLedger?->due ?? 0);
        $profitDue = (float) ($investor->profitDueLedger?->due ?? 0);

        return Inertia::render('Investors/Show', [
            'investor' => [
                'id' => $investor->id,
                'name' => $investor->name,
                'reference' => $investor->reference,
                'mobile' => $investor->mobile,
                'address' => $investor->address,
                'deed_ratio' => $investor->deed_ratio,
                'status' => $investor->status,
                'start_profit_month' => $investor->start_profit_month?->format('Y-m-d'),
                'end_profit_month' => $investor->end_profit_month?->format('Y-m-d'),
                'created_at' => $investor->created_at?->format('Y-m-d'),
                'updated_at' => $investor->updated_at?->format('Y-m-d'),
            ],
            'stats' => [
                'current_balance' => $balance,
                'profit_due' => $profitDue,
                'transaction_count' => $investor->investmentTransactions->count(),
                'profit_records' => $investor->monthlyProfitDetails->count(),
            ],
            'recentTransactions' => $investor->investmentTransactions->map(fn ($t) => [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'type' => $t->type->value,
                'transaction_date' => $t->transaction_date?->format('Y-m-d'),
                'remarks' => $t->remarks,
            ]),
            'recentProfit' => $investor->monthlyProfitDetails->map(fn ($p) => [
                'profit_month' => $p->profit_month,
                'investment' => (float) $p->investment,
                'actual_profit_due' => (float) $p->actual_profit_due,
                'advance_difference' => (float) $p->advance_difference,
                'net_settlement' => (float) $p->net_settlement,
            ]),
        ]);
    }

    /**
     * Show the form for editing the investor.
     */
    public function edit(Investor $investor): Response
    {
        return Inertia::render('Investors/Edit', [
            'investor' => [
                'id' => $investor->id,
                'name' => $investor->name,
                'reference' => $investor->reference,
                'mobile' => $investor->mobile,
                'address' => $investor->address,
                'deed_ratio' => $investor->deed_ratio,
                'status' => $investor->status,
                'start_profit_month' => $investor->start_profit_month?->format('Y-m-d'),
                'end_profit_month' => $investor->end_profit_month?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update the investor.
     */
    public function update(UpdateInvestorRequest $request, Investor $investor): RedirectResponse
    {
        $investor->update($request->validated());

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', "Investor {$investor->name} updated successfully.");
    }

    /**
     * Soft-delete the investor (never hard-delete financial records).
     */
    public function destroy(Investor $investor): RedirectResponse
    {
        $name = $investor->name;
        $investor->delete();

        return redirect()
            ->route('investors.index')
            ->with('success', "Investor {$name} deactivated.");
    }
}
