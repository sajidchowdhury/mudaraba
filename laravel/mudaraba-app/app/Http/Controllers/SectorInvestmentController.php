<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\SectorInvestment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectorInvestmentController extends Controller
{
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
