<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentTarget;
use App\Enums\AdjustmentType;
use App\Http\Requests\StoreBatchAdjustmentRequest;
use App\Http\Requests\StoreDirectAdjustmentRequest;
use App\Models\Investor;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfitAdjustmentController extends Controller
{
    /**
     * Display the unified profit adjustments page.
     */
    public function index(Request $request): Response
    {
        $query = ProfitAdjustment::query()
            ->with(['investor:id,name', 'sector:id,name', 'creator:id,username'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at');

        if ($request->type && in_array($request->type, ['fund_a', 'fund_b', 'direct'])) {
            $query->where('type', $request->type);
        }

        $adjustments = $query->paginate(20)->withQueryString();

        // Fund balances (computed on-the-fly, never stored)
        $fundABalance = ProfitAdjustment::fundBalance(AdjustmentType::FundA);
        $fundBBalance = ProfitAdjustment::fundBalance(AdjustmentType::FundB);

        // Data for batch forms
        $investors = Investor::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'reference', 'deed_ratio']);

        $sectors = Sector::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('ProfitAdjustments/Index', [
            'adjustments' => $adjustments->through(fn (ProfitAdjustment $a) => [
                'id' => $a->id,
                'type' => $a->type->value,
                'type_label' => $a->type->label(),
                'target_type' => $a->target_type->value,
                'target_name' => $a->investor?->name ?? $a->sector?->name ?? '—',
                'amount' => (float) $a->amount,
                'transaction_date' => $a->transaction_date?->format('Y-m-d'),
                'profit_month' => $a->profit_month?->format('Y-m-d'),
                'remarks' => $a->remarks,
                'created_by' => $a->creator?->username ?? '—',
                'created_at' => $a->created_at?->format('Y-m-d H:i'),
            ]),
            'investors' => $investors->map(fn (Investor $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
                'deed_ratio' => $i->deed_ratio,
            ]),
            'sectors' => $sectors->map(fn (Sector $s) => [
                'id' => $s->id,
                'name' => $s->name,
            ]),
            'fundBalances' => [
                'fund_a' => $fundABalance,
                'fund_b' => $fundBBalance,
            ],
            'filters' => $request->only(['type']),
            'canEdit' => $request->user()?->isSuperadmin() || $request->user()?->canEdit('adjustments.type-c') ?? false,
        ]);
    }

    /**
     * Store a batch adjustment (Fund A or Fund B).
     *
     * Creates investor-side + sector-side adjustment records, updates the
     * investor profit due + sector profit due ledgers, and the fund balance
     * is automatically correct (computed from the records).
     *
     * Equation: Fund balance = Σ(investor amounts) − Σ(sector amounts)
     *   - Each investor adjustment: profit_due decreases by amount
     *   - Each sector adjustment: sector_profit_due decreases by amount
     */
    public function storeBatch(StoreBatchAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = AdjustmentType::from($data['type']);
        $batchUuid = Str::uuid()->toString();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $type, $batchUuid, $userId) {
            // 1. Process investor adjustments
            foreach ($data['investor_items'] ?? [] as $item) {
                $amount = (float) $item['amount'];
                if ($amount <= 0) {
                    continue;
                }

                ProfitAdjustment::create([
                    'type' => $type,
                    'target_type' => AdjustmentTarget::Investor,
                    'investor_id' => $item['investor_id'],
                    'amount' => $amount,
                    'transaction_date' => $data['transaction_date'],
                    'profit_month' => $data['profit_month'],
                    'remarks' => $data['remarks'] ?? null,
                    'batch_uuid' => $batchUuid,
                    'created_by' => $userId,
                ]);

                // Decrease investor profit due (matches PHP: updateDue(-amount))
                $this->updateInvestorProfitDue($item['investor_id'], -$amount, $data['profit_month']);
            }

            // 2. Process sector adjustments
            foreach ($data['sector_items'] ?? [] as $item) {
                $amount = (float) $item['amount'];
                if ($amount <= 0) {
                    continue;
                }

                ProfitAdjustment::create([
                    'type' => $type,
                    'target_type' => AdjustmentTarget::Sector,
                    'sector_id' => $item['sector_id'],
                    'amount' => $amount,
                    'transaction_date' => $data['transaction_date'],
                    'profit_month' => $data['profit_month'],
                    'remarks' => $data['remarks'] ?? null,
                    'batch_uuid' => $batchUuid,
                    'created_by' => $userId,
                ]);

                // Decrease sector profit due (matches PHP: updateDue(-amount))
                $this->updateSectorProfitDue($item['sector_id'], -$amount, $data['profit_month']);
            }
        });

        $fundLabel = $type->label();
        $balance = ProfitAdjustment::fundBalance($type);

        return redirect()->back()->with('success',
            "{$fundLabel} batch adjustment saved. Current {$fundLabel} balance: ৳".number_format($balance, 2));
    }

    /**
     * Store a direct (single investor) adjustment — no sector side, no fund tracking.
     */
    public function storeDirect(StoreDirectAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId) {
            ProfitAdjustment::create([
                'type' => AdjustmentType::Direct,
                'target_type' => AdjustmentTarget::Investor,
                'investor_id' => $data['investor_id'],
                'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'profit_month' => $data['profit_month'],
                'remarks' => $data['remarks'] ?? null,
                'batch_uuid' => Str::uuid()->toString(),
                'created_by' => $userId,
            ]);

            // Decrease investor profit due
            $this->updateInvestorProfitDue($data['investor_id'], -(float) $data['amount'], $data['profit_month']);
        });

        return redirect()->back()->with('success', 'Direct adjustment saved successfully.');
    }

    /**
     * Soft-delete an adjustment + roll back the ledger entry.
     */
    public function destroy(ProfitAdjustment $adjustment): RedirectResponse
    {
        DB::transaction(function () use ($adjustment) {
            $amount = (float) $adjustment->amount;
            $month = $adjustment->profit_month?->format('Y-m-d');

            // Reverse the ledger entry
            if ($adjustment->target_type === AdjustmentTarget::Investor && $adjustment->investor_id) {
                $this->updateInvestorProfitDue($adjustment->investor_id, +$amount, $month);
            } elseif ($adjustment->target_type === AdjustmentTarget::Sector && $adjustment->sector_id) {
                $this->updateSectorProfitDue($adjustment->sector_id, +$amount, $month);
            }

            $adjustment->delete();
        });

        return redirect()->back()->with('success', 'Adjustment deleted, ledger rolled back.');
    }

    /*
    |--------------------------------------------------------------------------
    | Raw DB upsert helpers (same pattern as LedgerUpdateService)
    |--------------------------------------------------------------------------
    */
    private function updateInvestorProfitDue(int $investorId, float $amount, string $month): void
    {
        $this->upsertMonthly('investor_profit_monthly_due', 'investor_id', $investorId, $amount, $month);
        $this->upsertCumulative('investor_profit_due_ledger', 'investor_id', $investorId, $amount);
    }

    private function updateSectorProfitDue(int $sectorId, float $amount, string $month): void
    {
        $this->upsertMonthly('sector_profit_monthly_due', 'sector_id', $sectorId, $amount, $month);
        $this->upsertCumulative('sector_profit_due_ledger', 'sector_id', $sectorId, $amount);
    }

    private function upsertMonthly(string $table, string $column, int $entityId, float $amount, string $month): void
    {
        $existing = DB::table($table)
            ->where($column, $entityId)
            ->where('due_month', $month)
            ->first();

        if ($existing) {
            DB::table($table)
                ->where($column, $entityId)
                ->where('due_month', $month)
                ->update(['due' => DB::raw("due + {$amount}")]);
        } else {
            DB::table($table)->insert([
                $column => $entityId,
                'due_month' => $month,
                'due' => $amount,
            ]);
        }
    }

    private function upsertCumulative(string $table, string $column, int $entityId, float $amount): void
    {
        $existing = DB::table($table)->where($column, $entityId)->first();

        if ($existing) {
            DB::table($table)
                ->where($column, $entityId)
                ->update(['due' => DB::raw("due + {$amount}"), 'updated_at' => now()]);
        } else {
            DB::table($table)->insert([
                $column => $entityId,
                'due' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
