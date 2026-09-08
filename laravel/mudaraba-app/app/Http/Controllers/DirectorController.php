<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDirectorRequest;
use App\Http\Requests\UpdateDirectorRequest;
use App\Models\Director;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DirectorController extends Controller
{
    /**
     * Display a paginated, searchable list of directors (M/Y partners).
     */
    public function index(Request $request): Response
    {
        $directors = Director::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->with('dueLedger')
            ->orderByDesc('is_my')      // primary M/Y shows first
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Directors/Index', [
            'directors' => $directors->through(fn (Director $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'mobile' => $d->mobile,
                'address' => $d->address,
                'is_my' => (bool) $d->is_my,
                'current_balance' => (float) ($d->dueLedger?->due ?? 0),
                'created_at' => $d->created_at?->format('Y-m-d'),
            ]),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Directors/Create');
    }

    public function store(StoreDirectorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Only one director can be the primary M/Y at a time
        if (($data['is_my'] ?? false)) {
            Director::where('is_my', true)->update(['is_my' => false]);
        }

        $director = Director::create($data);

        return redirect()
            ->route('directors.show', $director)
            ->with('success', "Director {$director->name} created successfully.");
    }

    /**
     * Display the director's full profile + tabs.
     */
    public function show(Director $director): Response
    {
        $director->load([
            'directorTransactions' => fn ($q) => $q->orderBy('transaction_date', 'desc')->limit(20),
            'dueLedger',
        ]);

        $balance = (float) ($director->dueLedger?->due ?? 0);

        return Inertia::render('Directors/Show', [
            'director' => [
                'id' => $director->id,
                'name' => $director->name,
                'mobile' => $director->mobile,
                'address' => $director->address,
                'is_my' => (bool) $director->is_my,
                'created_at' => $director->created_at?->format('Y-m-d'),
                'updated_at' => $director->updated_at?->format('Y-m-d'),
            ],
            'stats' => [
                'current_balance' => $balance,
                'transaction_count' => $director->directorTransactions->count(),
            ],
            'recentTransactions' => $director->directorTransactions->map(fn ($t) => [
                'id' => $t->id,
                'amount' => (float) $t->amount,
                'type' => $t->type->value,
                'transaction_date' => $t->transaction_date?->format('Y-m-d'),
                'remarks' => $t->remarks,
            ]),
        ]);
    }

    public function edit(Director $director): Response
    {
        return Inertia::render('Directors/Edit', [
            'director' => [
                'id' => $director->id,
                'name' => $director->name,
                'mobile' => $director->mobile,
                'address' => $director->address,
                'is_my' => (bool) $director->is_my,
            ],
        ]);
    }

    public function update(UpdateDirectorRequest $request, Director $director): RedirectResponse
    {
        $data = $request->validated();

        // Only one director can be the primary M/Y at a time
        if (($data['is_my'] ?? false) && ! $director->is_my) {
            Director::where('is_my', true)->where('id', '!=', $director->id)->update(['is_my' => false]);
        }

        $director->update($data);

        return redirect()
            ->route('directors.show', $director)
            ->with('success', "Director {$director->name} updated successfully.");
    }

    /**
     * Soft-delete (preserves all financial records).
     */
    public function destroy(Director $director): RedirectResponse
    {
        $name = $director->name;
        $director->delete();

        return redirect()
            ->route('directors.index')
            ->with('success', "Director {$name} deactivated.");
    }
}
