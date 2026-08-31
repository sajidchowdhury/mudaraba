<?php

namespace Database\Seeders;

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Employee;
use App\Models\InvestmentTransaction;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorInvestment;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class January2026Seeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/january_2026_data.json')), true);

        // 0. Wipe everything (idempotent re-run support).
        //    Uses Laravel's DB-agnostic Schema helper — works on both PostgreSQL and SQLite.
        //    (migrate:fresh already drops all tables, but this makes `db:seed` alone safe too.)
        $this->command->info('Wiping existing data…');
        Schema::disableForeignKeyConstraints();
        foreach ([
            'profit_adjustments',
            'sector_profit_monthly_due',
            'sector_profit_due_ledger',
            'sector_monthly_due',
            'sector_due_ledger',
            'investor_profit_monthly_due',
            'investor_profit_due_ledger',
            'investor_monthly_due',
            'investor_due_ledger',
            'investor_monthly_profit_details',
            'monthly_profit_summary',
            'monthly_sector_profit',
            'sector_investments',
            'investment_transactions',
            'retained_earnings_distributions',
            'retained_earnings',
            'director_monthly_due',
            'director_due_ledger',
            'audit_logs',
            'investors',
            'sectors',
            'directors',
            'users',
            'employees',
            'menus',
            'user_permissions',
        ] as $t) {
            // PostgreSQL supports TRUNCATE ... CASCADE; SQLite uses DELETE.
            // DB::table()->truncate() handles both drivers correctly.
            DB::table($t)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // 1. Create superadmin user
        $employee = Employee::create([
            'name' => 'Mohammad',
            'designation' => 'Senior IT Executive',
            'phone_number' => '8801911599014',
            'status' => 'Active',
        ]);
        $user = User::create([
            'username' => 'E0001',
            'employee_id' => $employee->id,
            'email' => 'admin@mudaraba.test',
            'password_hash' => bcrypt('Mudaraba@2026'),
            'role' => 'superadmin',
            'status' => 'Active',
            'login_start' => '00:00:00',
            'login_end' => '23:59:59',
        ]);

        // 2. Create primary M/Y director
        $director = Director::create(['name' => 'Sajid (M/Y)', 'is_my' => true]);
        DirectorDueLedger::create(['director_id' => $director->id, 'due' => 0]);

        // 3. Create all sectors from the Excel sheet
        $totalSectorInvestment = array_sum(array_column($data['sectors'], 'inv'));
        foreach ($data['sectors'] as $s) {
            $sector = Sector::create([
                'name' => $s['name'],
                'status' => 'active',
            ]);

            // Sector capital due (investment balance)
            SectorDueLedger::create(['sector_id' => $sector->id, 'due' => $s['inv']]);
            SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

            // January 2026 sector profit entry — estimated = budget, actual = realized
            MonthlySectorProfit::create([
                'sector_id' => $sector->id,
                'profit_month' => '2026-01-01',
                'estimated_profit' => $s['estimated'],
                'actual_profit' => $s['actual'],
                'status' => 'finalized',
                'transaction_date' => '2026-01-14',
                'created_by' => $user->id,
            ]);

            // Create a SectorInvestment (add) record so Cash-in-Hand tracking reflects deployment
            if ($s['inv'] > 0) {
                SectorInvestment::create([
                    'sector_id' => $sector->id,
                    'amount' => $s['inv'],
                    'type' => 'add',
                    'transaction_date' => '2026-01-01',
                    'remarks' => 'January 2026 opening sector allocation',
                    'created_by' => $user->id,
                ]);
            }
        }

        // 4. Create all investors + corresponding InvestmentTransaction (add) records
        //    Each investor's deposit is recorded as an InvestmentTransaction so the
        //    Dashboard Cash-in-Hand tracking can compute totalCollected correctly.
        foreach ($data['investors'] as $inv) {
            $investor = Investor::create([
                'name' => $inv['name'],
                'reference' => !empty($inv['ref']) ? $inv['ref'] : null,
                'mobile' => null,
                'address' => null,
                'deed_ratio' => $inv['deed'],
                'start_profit_month' => '2025-01-01',
                'end_profit_month' => '2030-12-31',
                'status' => 'active',
            ]);

            // Investor capital due (investment balance = D column in Excel)
            InvestorDueLedger::create(['investor_id' => $investor->id, 'due' => $inv['inv']]);
            InvestorProfitDueLedger::create(['investor_id' => $investor->id, 'due' => 0]);

            // Record the deposit as an InvestmentTransaction (type=add)
            if ($inv['inv'] > 0) {
                InvestmentTransaction::create([
                    'investor_id' => $investor->id,
                    'amount' => $inv['inv'],
                    'type' => 'add',
                    'transaction_month' => '2026-01-01',
                    'transaction_date' => '2026-01-01',
                    'remarks' => 'January 2026 opening investment',
                    'created_by' => $user->id,
                ]);
            }
        }

        // 5. Create menus + permissions
        $this->call(MenuSeeder::class);
        $this->call(UserPermissionSeeder::class);

        // Verify reconciliation
        $invTotal = array_sum(array_column($data['investors'], 'inv'));
        $secTotal = array_sum(array_column($data['sectors'], 'inv'));
        $estTotal = array_sum(array_column($data['sectors'], 'estimated'));
        $actTotal = array_sum(array_column($data['sectors'], 'actual'));

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  🎉 January 2026 data loaded!');
        $this->command->info('');
        $this->command->info('  Investors:              '.count($data['investors']));
        $this->command->info('  Sectors:                '.count($data['sectors']));
        $this->command->info('  Total Investment:       '.number_format($invTotal));
        $this->command->info('  Sector Allocated:       '.number_format($secTotal));
        $this->command->info('  Cash in Hand:           '.number_format($invTotal - $secTotal));
        $this->command->info('  Estimated Profit (D181): '.number_format($estTotal));
        $this->command->info('  Actual Profit (Z2):      '.number_format($actTotal));
        $this->command->info('  Variance:                '.number_format($estTotal - $actTotal));
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  Login: E0001 / Mudaraba@2026');
        $this->command->info('═══════════════════════════════════════════════════');
    }
}
