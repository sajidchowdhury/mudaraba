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

        // Investors with adjustable balance (investor_profit_due_ledger.due) + investment amount
        $investors = Investor::where('status', 'active')
            ->leftJoin('investor_profit_due_ledger', 'investors.id', '=', 'investor_profit_due_ledger.investor_id')
            ->leftJoin('investor_due_ledger', 'investors.id', '=', 'investor_due_ledger.investor_id')
            ->orderBy('investors.name')
            ->get([
                'investors.id',
                'investors.name',
                'investors.reference',
                'investors.deed_ratio',
                'investor_profit_due_ledger.due as adjustable_balance',
                'investor_due_ledger.due as investment',
            ]);

        // Sectors with due balance (sector_profit_due_ledger.due)
        $sectors = Sector::where('status', 'active')
            ->leftJoin('sector_profit_due_ledger', 'sectors.id', '=', 'sector_profit_due_ledger.sector_id')
            ->orderBy('sectors.name')
            ->get([
                'sectors.id',
                'sectors.name',
                'sector_profit_due_ledger.due as due_balance',
            ]);

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
            'investors' => $investors->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'reference' => $i->reference,
                'deed_ratio' => $i->deed_ratio,
                'adjustable_balance' => (float) ($i->adjustable_balance ?? 0),
                'investment' => (float) ($i->investment ?? 0),
            ]),
            'sectors' => $sectors->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'due_balance' => (float) ($s->due_balance ?? 0),
            ]),
            'fundBalances' => [
                'fund_a' => $fundABalance,
                'fund_b' => $fundBBalance,
            ],
            'filters' => $request->only(['type']),
            'canEdit' => $request->user()?->isSuperadmin() || $request->user()?->canEdit('adjustments.index') ?? false,
        ]);
    }

    /**
     * Store a batch adjustment (Fund A or Fund B).
     *
     * Fund A (per PHP spec §4):
     *   - Investor side: each investor's adjustable balance is debited (−amount),
     *     the amount is added to the Fund A pool.
     *   - Sector side: each sector's due is debited (−amount), the amount is
     *     deducted from the Fund A pool.
     *   - Fund A balance = Σ(investor) − Σ(sector)
     *
     * Fund B (per PHP spec §5):
     *   - SECTOR-ONLY. investor_items are rejected.
     *   - Each sector's due is debited (−amount), and the amount is ADDED to
     *     the Fund B reserve (opposite sign of Fund A).
     *   - Fund B balance = +Σ(sector)
     */
    public function storeBatch(StoreBatchAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = AdjustmentType::from($data['type']);
        $batchUuid = Str::uuid()->toString();
        $userId = $request->user()->id;

        // Fund B is sector-only — reject investor_items per PHP spec §5.2
        if ($type === AdjustmentType::FundB && !empty($data['investor_items'])) {
            return redirect()->back()->withErrors([
                'investor_items' => 'Fund B is sector-only — investor adjustments are not allowed.',
            ])->withInput();
        }

        DB::transaction(function () use ($data, $type, $batchUuid, $userId) {
            // 1. Process investor adjustments (Fund A only — Fund B rejected above)
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

            // 2. Process sector adjustments (both Fund A and Fund B)
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
     * Store a direct adjustment — sector ↔ investor transfer (no fund ledger).
     *
     * Per PHP spec §6, Direct Funding has 2 modes:
     *
     * 1. investor_wise: single investor + single sector + total_amount.
     *    Both investor_profit_due and sector_profit_due are debited by total_amount.
     *
     * 2. as_per_invest: single sector + total_amount + bulk investors distributed
     *    by investment ratio. Sector is debited total_amount; each investor is
     *    debited their ratio × total_amount share.
     *
     * No fund ledger is touched (unlike Fund A / Fund B).
     */
    public function storeDirect(StoreDirectAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = $request->user()->id;
        $mode = $data['mode'];
        $batchUuid = Str::uuid()->toString();

        DB::transaction(function () use ($data, $userId, $mode, $batchUuid) {
            $sectorId = $data['sector_id'];
            $profitMonth = $data['profit_month'];
            $transactionDate = $data['transaction_date'];
            $remarks = $data['remarks'] ?? null;

            if ($mode === 'investor_wise') {
                // Single investor + single sector + single amount
                $investorId = $data['investor_id'];
                $amount = (float) $data['total_amount'];

                // Investor side
                ProfitAdjustment::create([
                    'type' => AdjustmentType::Direct,
                    'target_type' => AdjustmentTarget::Investor,
                    'investor_id' => $investorId,
                    'amount' => $amount,
                    'transaction_date' => $transactionDate,
                    'profit_month' => $profitMonth,
                    'remarks' => $remarks ?? 'Direct funding (investor-wise)',
                    'batch_uuid' => $batchUuid,
                    'created_by' => $userId,
                ]);
                $this->updateInvestorProfitDue($investorId, -$amount, $profitMonth);

                // Sector side (same amount)
                ProfitAdjustment::create([
                    'type' => AdjustmentType::Direct,
                    'target_type' => AdjustmentTarget::Sector,
                    'sector_id' => $sectorId,
                    'amount' => $amount,
                    'transaction_date' => $transactionDate,
                    'profit_month' => $profitMonth,
                    'remarks' => $remarks ?? 'Direct funding (investor-wise)',
                    'batch_uuid' => $batchUuid,
                    'created_by' => $userId,
                ]);
                $this->updateSectorProfitDue($sectorId, -$amount, $profitMonth);
            } else {
                // as_per_invest — bulk investors by ratio
                $sectorTotal = (float) $data['total_amount'];

                // Sector side — single debit of total
                ProfitAdjustment::create([
                    'type' => AdjustmentType::Direct,
                    'target_type' => AdjustmentTarget::Sector,
                    'sector_id' => $sectorId,
                    'amount' => $sectorTotal,
                    'transaction_date' => $transactionDate,
                    'profit_month' => $profitMonth,
                    'remarks' => $remarks ?? 'Direct funding (as-per-invest)',
                    'batch_uuid' => $batchUuid,
                    'created_by' => $userId,
                ]);
                $this->updateSectorProfitDue($sectorId, -$sectorTotal, $profitMonth);

                // Investor side — per-investor amounts from the request
                $investorTotal = 0;
                foreach ($data['investor_items'] ?? [] as $item) {
                    $amount = (float) $item['amount'];
                    if ($amount <= 0) {
                        continue;
                    }
                    $investorTotal += $amount;

                    ProfitAdjustment::create([
                        'type' => AdjustmentType::Direct,
                        'target_type' => AdjustmentTarget::Investor,
                        'investor_id' => $item['investor_id'],
                        'amount' => $amount,
                        'transaction_date' => $transactionDate,
                        'profit_month' => $profitMonth,
                        'remarks' => $remarks ?? 'Direct funding (as-per-invest)',
                        'batch_uuid' => $batchUuid,
                        'created_by' => $userId,
                    ]);
                    $this->updateInvestorProfitDue($item['investor_id'], -$amount, $profitMonth);
                }
            }
        });

        $modeLabel = $mode === 'investor_wise' ? 'Investor-Wise' : 'As-Per-Invest';
        return redirect()->back()->with('success', "Direct funding ({$modeLabel}) saved successfully.");
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
