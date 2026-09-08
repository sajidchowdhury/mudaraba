<?php

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\MonthlyProfitSummary;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorInvestment;
use App\Models\User;
use Database\Seeders\MenuSeeder;

beforeEach(function () {
    $this->seed(MenuSeeder::class);
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->investor = Investor::factory()->create(['name' => 'Test Inv', 'status' => 'active', 'deed_ratio' => '100']);
    InvestorDueLedger::create(['investor_id' => $this->investor->id, 'due' => 500000]);
    InvestmentTransaction::create([
        'investor_id' => $this->investor->id, 'amount' => 500000, 'type' => 'add',
        'transaction_month' => '2026-07-01', 'transaction_date' => '2026-07-15',
        'created_by' => $this->superadmin->id,
    ]);

    $this->sector = Sector::factory()->create(['name' => 'Test Sector', 'status' => 'active']);
    SectorDueLedger::create(['sector_id' => $this->sector->id, 'due' => 1000000]);
    SectorInvestment::create([
        'sector_id' => $this->sector->id, 'amount' => 1000000, 'type' => 'add',
        'transaction_date' => '2026-07-15', 'created_by' => $this->superadmin->id,
    ]);

    $this->director = Director::factory()->create(['name' => 'M/Y', 'is_my' => true]);
    DirectorDueLedger::create(['director_id' => $this->director->id, 'due' => 136162]);

    MonthlyProfitSummary::create([
        'profit_month' => '2026-07-01',
        'total_estimated_profit' => 200000, 'total_actual_profit' => 200000,
        'total_advance_difference' => 0, 'total_investor_profit_due' => 168000,
        'my_profit' => 32000, 'my_profit_ratio' => 16.0,
        'total_mudaraba_investment' => 1000000, 'active_investor_count' => 1,
        'status' => 'finalized',
    ]);
});

it('exports investor ledger as PDF', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/exports/investor-ledger?investor_id={$this->investor->id}");

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

it('exports sector ledger as PDF', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/exports/sector-ledger?sector_id={$this->sector->id}");

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

it('exports M/Y ledger as PDF', function () {
    $response = $this->actingAs($this->superadmin)
        ->get("/exports/my-ledger?director_id={$this->director->id}");

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

it('exports investment profit as Excel', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/exports/investment-profit?month=2026-07-01');

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/exports/investor-ledger');
    $response->assertRedirect('/login');
});

it('requires investor_id for investor ledger PDF', function () {
    $response = $this->actingAs($this->superadmin)
        ->get('/exports/investor-ledger');

    $response->assertStatus(404);
});
