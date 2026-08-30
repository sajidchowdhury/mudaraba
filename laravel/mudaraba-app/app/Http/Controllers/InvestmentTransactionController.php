<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentTransactionRequest;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentTransactionController extends Controller
{
    /**
     * Display the investment transactions page with form + history.
     */
    public function index(Request $request): Response
    {
        $query = InvestmentTransaction::query()
            ->with(['investor:id,name', 'creator:id,username'])
            ->orderByDesc('transaction_date');

        // Filter by investor if selected
        if ($request->investor_id) {
            $query->where('investor_id', $request->investor_id);
        }

        // Filter by type if selected
        if ($request->type && in_array($request->type, ['add', 'withdraw'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Get all active investors for the dropdown
        $investors = Investor::orderBy('name')
            ->get(['id', 'name', 'reference']);

        return Inertia::render('Investments/Index', [
            'transactions' => $transactions->through(fn (InvestmentTransaction $t) => [
                'id' => $t->id,
                'investor_name' => $t->investor?->name ?? '—',
                'investor_id' => $t->investor_id,
                'amount' => (float) $t->amount,
                'type' => $t->type->value,
                'transaction_month' => $t->transaction_month?->format('Y-m-d'),
                'transaction_date' => $t->transaction_date?->format('Y-m-d'),
                'remarks' => $t->remarks,
                'created_by' => $t->creator?->username ?? '—',
                'created_at' => $t->created_at?->format('Y-m-d H:i'),
            ]),
            'investors' => $investors->map(fn (Investor $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
            ]),
            'filters' => $request->only(['investor_id', 'type']),
            'canBackdate' => $request->user()?->isSuperadmin() || $request->user()?->canBackdate('investments.index') ?? false,
        ]);
    }

    /**
     * Store a new investment transaction.
     * Updates the investor's due ledger via the DueManager trait.
     */
    public function store(StoreInvestmentTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // Create the transaction record
        $transaction = InvestmentTransaction::create($data);

        // Update the investor's due ledger via DueManager.
        // Add → +amount, Withdraw → -amount (via signedAmount()).
        $transaction->updateDue(
            $transaction->investor_id,
            $transaction->signedAmount(),
            $transaction->transaction_month->format('Y-m-d'),
        );

        $investorName = $transaction->investor?->name ?? 'Unknown';
        $typeLabel = $transaction->type->value === 'add' ? 'added to' : 'withdrawn from';

        return redirect()
            ->back()
            ->with('success', '৳'.number_format($transaction->amount, 2)." {$typeLabel} {$investorName}.");
    }

    /**
     * Soft-delete a transaction.
     * Rolls back the due ledger entry via DueManager.
     */
    public function destroy(InvestmentTransaction $transaction): RedirectResponse
    {
        // Rollback the due ledger entry before deleting
        $transaction->rollbackDue(
            $transaction->investor_id,
            $transaction->signedAmount(),
            $transaction->transaction_month->format('Y-m-d'),
        );

        $transaction->delete();

        return redirect()
            ->back()
            ->with('success', 'Transaction deleted and ledger rolled back.');
    }

    /**
     * Get the current balance for an investor (AJAX endpoint for balance preview).
     */
    public function balance(Investor $investor): array
    {
        $balance = (float) ($investor->dueLedger?->due ?? 0);

        return [
            'investor_id' => $investor->id,
            'name' => $investor->name,
            'balance' => $balance,
        ];
    }
}
