<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\SectorInvestment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SectorInvestmentController extends Controller
{
    /**
     * Display the sector investments page with form + history.
     * This is the sector equivalent of the Investor investments page —
     * the M/Y uses it to allocate investor funds to sectors.
     */
    public function index(Request $request): Response
    {
        $query = SectorInvestment::query()
            ->with(['sector:id,name', 'creator:id,username'])
            ->orderByDesc('transaction_date');

        // Filter by sector if selected
        if ($request->sector_id) {
            $query->where('sector_id', $request->sector_id);
        }

        // Filter by type if selected
        if ($request->type && in_array($request->type, ['add', 'withdraw'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Get all active sectors for the dropdown
        $sectors = Sector::orderBy('name')
            ->where('status', 'active')
            ->get(['id', 'name']);

        return Inertia::render('SectorInvestments/Index', [
            'transactions' => $transactions->through(fn (SectorInvestment $t) => [
                'id' => $t->id,
                'sector_name' => $t->sector?->name ?? '—',
                'sector_id' => $t->sector_id,
                'amount' => (float) $t->amount,
                'type' => $t->type->value,
                'transaction_date' => $t->transaction_date?->format('Y-m-d'),
                'remarks' => $t->remarks,
                'created_by' => $t->creator?->username ?? '—',
                'created_at' => $t->created_at?->format('Y-m-d H:i'),
            ]),
            'sectors' => $sectors->map(fn (Sector $s) => [
                'id' => $s->id,
                'name' => $s->name,
            ]),
            'filters' => $request->only(['sector_id', 'type']),
        ]);
    }

    /**
     * Store a new sector investment (add money to / withdraw from a sector).
     *
     * Updates the sector's due ledger via the DueManager trait.
     * The flow: money comes from investors → M/Y assigns it to sectors.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sector_id' => ['required', 'exists:sectors,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::in(['add', 'withdraw'])],
            'transaction_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $investment = SectorInvestment::create($validated);

        // Update the sector's due ledger via DueManager.
        // Add → +amount, Withdraw → -amount (via signedAmount()).
        $investment->updateDue(
            $investment->sector_id,
            $investment->signedAmount(),
            $investment->transaction_date->format('Y-m-d'),
        );

        $sectorName = $investment->sector?->name ?? 'Unknown';
        $typeLabel = $investment->type->value === 'add' ? 'allocated to' : 'withdrawn from';

        return redirect()
            ->back()
            ->with('success', "৳".number_format($investment->amount, 2)." {$typeLabel} {$sectorName}.");
    }

    /**
     * Soft-delete a sector investment.
     * Rolls back the due ledger entry via DueManager.
     */
    public function destroy(SectorInvestment $investment): RedirectResponse
    {
        $sectorName = $investment->sector?->name ?? 'Unknown';

        // Rollback the due ledger entry before deleting
        $investment->rollbackDue(
            $investment->sector_id,
            $investment->signedAmount(),
            $investment->transaction_date->format('Y-m-d'),
        );

        $investment->delete();

        return redirect()
            ->back()
            ->with('success', "Sector investment deleted and ledger rolled back for {$sectorName}.");
    }
}
