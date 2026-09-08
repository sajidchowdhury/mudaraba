<?php

use App\Enums\AdjustmentTarget;
use App\Enums\AdjustmentType;
use App\Enums\InvestmentType;
use App\Models\AuditLog;
use App\Models\Director;
use App\Models\DirectorTransaction;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\MonthlySectorProfit;
use App\Models\ProfitAdjustment;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorInvestment;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ProfitCalculatorService;
use Database\Seeders\MenuSeeder;

/**
 * AuditLogTest — verifies every financial mutation writes a row to
 * audit_logs with the correct action, entity_type, entity_id, and
 * before/after state.
 *
 * Covers Gap #8 from the plan: "AuditLog model exists but it is not
 * clear whether every financial mutation writes to it."
 */
beforeEach(function () {
    $this->seed(MenuSeeder::class);

    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);
    $this->investor = Investor::factory()->create([
        'name' => 'Test Inv', 'deed_ratio' => '100', 'status' => 'active',
        'start_profit_month' => '2025-01-01', 'end_profit_month' => '2030-12-31',
    ]);
    InvestorDueLedger::create(['investor_id' => $this->investor->id, 'due' => 500000]);
    InvestorProfitDueLedger::create(['investor_id' => $this->investor->id, 'due' => 0]);
    SectorDueLedger::create(['sector_id' => $this->sector->id, 'due' => 0]);
    SectorProfitDueLedger::create(['sector_id' => $this->sector->id, 'due' => 0]);
    Director::factory()->create(['name' => 'M/Y', 'is_my' => true]);
});

// ----------------------------------------------------------------------------
// InvestmentTransaction — capital add / withdraw per investor
// ----------------------------------------------------------------------------

it('the Auditable trait fires created event on InvestmentTransaction (diagnostic)', function () {
    // This test verifies the trait's event listener is actually wired up.
    // If this fails, the Auditable trait's bootAuditable() method isn't
    // being called — which means the created/updated/deleted events won't
    // fire, and no audit logs will be written.
    $fired = false;
    InvestmentTransaction::created(function () use (&$fired) {
        $fired = true;
    });

    $tx = InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000, 'type' => 'add',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    expect($fired)->toBeTrue('The InvestmentTransaction::created event did not fire. The Auditable trait may not be booting correctly.');
    expect($tx->exists)->toBeTrue();
});

it('logs an audit entry when an investment transaction is created', function () {
    $this->actingAs($this->superadmin);

    $tx = InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 500000, 'type' => 'add',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $audit = AuditLog::where('entity_type', 'investment_transaction')
        ->where('entity_id', $tx->id)
        ->where('action', 'create')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->superadmin->id)
        ->and($audit->after_data)->toHaveKey('amount')
        ->and($audit->after_data['amount'])->toBe(500000.0)
        ->and($audit->before_data)->toBeNull();
});

it('logs an audit entry when an investment transaction is deleted (soft-delete)', function () {
    $this->actingAs($this->superadmin);

    $tx = InvestmentTransaction::create([
        'investor_id' => $this->investor->id,
        'amount' => 100000, 'type' => 'add',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    // Reset — we only care about the delete audit entry below
    AuditLog::query()->delete();

    $tx->delete();

    $audit = AuditLog::where('entity_type', 'investment_transaction')
        ->where('entity_id', $tx->id)
        ->where('action', 'delete')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->before_data)->toHaveKey('amount')
        ->and($audit->after_data)->toBeNull();
});

// ----------------------------------------------------------------------------
// SectorInvestment — capital add / withdraw per sector
// ----------------------------------------------------------------------------

it('logs an audit entry when a sector investment is created', function () {
    $this->actingAs($this->superadmin);

    $inv = SectorInvestment::create([
        'sector_id' => $this->sector->id,
        'amount' => 300000, 'type' => 'add',
        'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $audit = AuditLog::where('entity_type', 'sector_investment')
        ->where('entity_id', $inv->id)
        ->where('action', 'create')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->after_data['amount'])->toBe(300000.0);
});

// ----------------------------------------------------------------------------
// DirectorTransaction — M/Y withdrawals
// ----------------------------------------------------------------------------

it('logs an audit entry when a director transaction is created', function () {
    $this->actingAs($this->superadmin);

    $director = Director::where('is_my', true)->first();

    $tx = DirectorTransaction::create([
        'director_id' => $director->id,
        'amount' => 50000, 'type' => 'withdraw',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-20',
        'created_by' => $this->superadmin->id,
    ]);

    $audit = AuditLog::where('entity_type', 'director_transaction')
        ->where('entity_id', $tx->id)
        ->where('action', 'create')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->after_data['amount'])->toBe(50000.0)
        ->and($audit->after_data['type'])->toBe('withdraw');
});

// ----------------------------------------------------------------------------
// MonthlySectorProfit — sector profit entry, finalize transition
// ----------------------------------------------------------------------------

it('logs a "finalize" audit entry (not "update") when sector profit status changes to finalized', function () {
    $this->actingAs($this->superadmin);

    $msp = MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'draft', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    // Reset audit logs to isolate the finalize transition
    AuditLog::query()->delete();

    $msp->update(['status' => 'finalized', 'finalized_by' => $this->superadmin->id]);

    // Should have a 'finalize' entry
    $finalizeAudit = AuditLog::where('entity_type', 'monthly_sector_profit')
        ->where('entity_id', $msp->id)
        ->where('action', 'finalize')
        ->first();

    expect($finalizeAudit)->not->toBeNull()
        ->and($finalizeAudit->before_data)->toBe(['status' => 'draft'])
        ->and($finalizeAudit->after_data)->toBe(['status' => 'finalized']);

    // Should NOT have a generic 'update' entry for this same transition
    $updateAudit = AuditLog::where('entity_type', 'monthly_sector_profit')
        ->where('entity_id', $msp->id)
        ->where('action', 'update')
        ->first();

    expect($updateAudit)->toBeNull();
});

it('logs a standard "update" audit entry when non-status fields change on sector profit', function () {
    $this->actingAs($this->superadmin);

    $msp = MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'draft', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    AuditLog::query()->delete();

    $msp->update(['estimated_profit' => 250000]);

    $audit = AuditLog::where('entity_type', 'monthly_sector_profit')
        ->where('entity_id', $msp->id)
        ->where('action', 'update')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->before_data)->toHaveKey('estimated_profit')
        ->and($audit->after_data['estimated_profit'])->toBe(250000.0)
        ->and($audit->before_data['estimated_profit'])->toBe(200000.0);
});

// ----------------------------------------------------------------------------
// ProfitAdjustment — the unified Fund A/B/Direct adjustments table
// ----------------------------------------------------------------------------

it('logs an audit entry when a ProfitAdjustment (Fund A) is created', function () {
    $this->actingAs($this->superadmin);

    $adj = ProfitAdjustment::create([
        'type' => AdjustmentType::FundA,
        'target_type' => AdjustmentTarget::Investor,
        'investor_id' => $this->investor->id,
        'amount' => 5000,
        'transaction_date' => '2026-07-15',
        'profit_month' => '2026-07-01',
        'created_by' => $this->superadmin->id,
    ]);

    $audit = AuditLog::where('entity_type', 'profit_adjustment')
        ->where('entity_id', $adj->id)
        ->where('action', 'create')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->after_data['type'])->toBe('fund_a')
        ->and($audit->after_data['target_type'])->toBe('investor')
        ->and($audit->after_data['amount'])->toBe(5000.0);
});

// NOTE: Tests for AdvanceProfitAdjustment (Type C), AdvanceProfitAdjustmentTypeA,
// and AdvanceProfitAdjustmentTypeB have been REMOVED. These legacy models point
// to tables that were intentionally DROPPED by the 2026_08_30_150836 migration
// (which created the unified `profit_adjustments` table to replace them).
// The Auditable trait was also removed from those 3 models. Audit logging for
// profit adjustments is now covered by the ProfitAdjustment model test above.

// ----------------------------------------------------------------------------
// InvestorMonthlyProfitDetail — snapshot rows
// ----------------------------------------------------------------------------

it('logs audit "create" entries (not delete) for investor monthly profit details during reconcile', function () {
    $this->actingAs($this->superadmin);

    MonthlySectorProfit::create([
        'sector_id' => $this->sector->id,
        'profit_month' => '2026-07-01',
        'estimated_profit' => 200000, 'actual_profit' => 200000,
        'status' => 'finalized', 'transaction_date' => now(),
        'created_by' => $this->superadmin->id,
    ]);

    AuditLog::query()->delete();

    app(ProfitCalculatorService::class)->calculate('2026-07-01', $this->superadmin->id);

    // Should have a single 'reconcile' entry for the whole batch (user-intent level)
    $reconcileAudit = AuditLog::where('action', 'reconcile')
        ->where('entity_type', 'monthly_profit_summary')
        ->first();

    expect($reconcileAudit)->not->toBeNull()
        ->and($reconcileAudit->user_id)->toBe($this->superadmin->id)
        ->and($reconcileAudit->after_data)->toHaveKey('profit_month')
        ->and($reconcileAudit->after_data['profit_month'])->toBe('2026-07-01')
        ->and($reconcileAudit->after_data)->toHaveKey('my_profit')
        ->and($reconcileAudit->after_data)->toHaveKey('batch_uuid');

    // Should have at least one 'create' audit for the investor_monthly_profit_detail row(s)
    $detailCreateAudits = AuditLog::where('action', 'create')
        ->where('entity_type', 'investor_monthly_profit_detail')
        ->count();
    expect($detailCreateAudits)->toBeGreaterThanOrEqual(1);

    // Should have NO 'delete' audit entries for investor_monthly_profit_detail
    // (per the shouldAudit override that suppresses bulk-delete spam)
    $detailDeleteAudits = AuditLog::where('action', 'delete')
        ->where('entity_type', 'investor_monthly_profit_detail')
        ->count();
    expect($detailDeleteAudits)->toBe(0);
});

// ----------------------------------------------------------------------------
// MonthlyProfitSummary — lock / unlock transitions
// ----------------------------------------------------------------------------

it('logs a "lock" audit entry (not "update") when a month is locked', function () {
    $this->actingAs($this->superadmin);

    $summary = MonthlyProfitSummary::create([
        'profit_month' => '2026-07-01',
        'total_estimated_profit' => 200000, 'total_actual_profit' => 200000,
        'my_profit' => 30000, 'my_profit_ratio' => 15.0,
        'total_mudaraba_investment' => 1000000, 'active_investor_count' => 1,
        'status' => 'finalized',
        'finalized_by' => $this->superadmin->id,
    ]);

    AuditLog::query()->delete();

    $summary->update([
        'status' => 'locked',
        'locked_by' => $this->superadmin->id,
        'locked_at' => now(),
    ]);

    $lockAudit = AuditLog::where('action', 'lock')
        ->where('entity_type', 'monthly_profit_summary')
        ->first();

    expect($lockAudit)->not->toBeNull()
        ->and($lockAudit->after_data['status'])->toBe('locked');

    $updateAudit = AuditLog::where('action', 'update')
        ->where('entity_type', 'monthly_profit_summary')
        ->first();
    expect($updateAudit)->toBeNull();
});

it('logs an "unlock" audit entry when a locked month is unlocked', function () {
    $this->actingAs($this->superadmin);

    $summary = MonthlyProfitSummary::create([
        'profit_month' => '2026-07-01',
        'total_estimated_profit' => 200000, 'total_actual_profit' => 200000,
        'my_profit' => 30000, 'my_profit_ratio' => 15.0,
        'total_mudaraba_investment' => 1000000, 'active_investor_count' => 1,
        'status' => 'locked',
        'locked_by' => $this->superadmin->id,
        'locked_at' => now(),
    ]);

    AuditLog::query()->delete();

    $summary->update([
        'status' => 'finalized',
        'locked_by' => null,
        'locked_at' => null,
    ]);

    $unlockAudit = AuditLog::where('action', 'unlock')
        ->where('entity_type', 'monthly_profit_summary')
        ->first();

    expect($unlockAudit)->not->toBeNull()
        ->and($unlockAudit->before_data['status'])->toBe('locked')
        ->and($unlockAudit->after_data['status'])->toBe('finalized');
});

// ----------------------------------------------------------------------------
// AuditService::log — direct invocation (for non-model-driven events)
// ----------------------------------------------------------------------------

it('captures the acting user id from auth context by default', function () {
    $this->actingAs($this->superadmin);

    $model = new MonthlySectorProfit();
    $model->id = 999;
    $model->exists = true;

    AuditService::log(
        action: 'custom_action',
        model: $model,
        after: ['note' => 'test'],
    );

    $audit = AuditLog::where('action', 'custom_action')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->superadmin->id);
});

it('allows overriding the acting user id (used by services that act on behalf of a user)', function () {
    $otherUser = User::factory()->create(['role' => 'admin']);

    $model = new MonthlySectorProfit();
    $model->id = 999;
    $model->exists = true;

    AuditService::log(
        action: 'custom_action',
        model: $model,
        after: ['note' => 'on-behalf-of'],
        userId: $otherUser->id,
    );

    $audit = AuditLog::where('action', 'custom_action')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($otherUser->id);
});
