<?php

namespace Database\Seeders;

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Employee;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CleanStartSeeder — creates a fresh, empty-state database for testing
 * the system step-by-step.
 *
 * What this seeder creates:
 *   1. Superadmin user (E0001 / Mudaraba@2026)
 *   2. Primary M/Y director (Sajid) with 0 due
 *   3. 16 empty sectors (names from the Excel sheet, but 0 investment)
 *   4. 158 empty investors (names from the January 2026 data, but 0 investment)
 *   5. Menus + permissions (for the sidebar)
 *
 * What this seeder does NOT create:
 *   - No investment transactions (investors have 0 balance)
 *   - No sector investments (sectors have 0 balance)
 *   - No sector profits (no monthly_sector_profit rows)
 *   - No monthly profit summaries
 *   - No retained earnings
 *   - No director transactions (M/Y has 0 profit/return)
 *   - No profit adjustments
 *   - No audit logs
 *
 * This gives you a blank canvas. You then go through the workflow:
 *   1. Add investment to investors (Investment → Investor page)
 *   2. Allocate funds to sectors (Investment → Sector page)
 *   3. Enter sector profits (Profit → Sector Profit page)
 *   4. Set retained earnings amount (on the Sector Profit page)
 *   5. Finalize the month (triggers the 8-phase calculation)
 *   6. Review the investor profit grid (Profit → Investor Profit page)
 *   7. Export to Excel
 *   8. Lock the month
 *
 * Usage:
 *   php artisan migrate:fresh --seed --class=CleanStartSeeder
 *   OR set DatabaseSeeder to call CleanStartSeeder, then run:
 *   php artisan migrate:fresh --seed
 */
class CleanStartSeeder extends Seeder
{
    public function run(): void
    {
        // ── 0. Wipe everything ──────────────────────────────────────────
        $this->command->info('Wiping all data…');
        Schema::disableForeignKeyConstraints();
        $tables = [
            'profit_adjustments',
            'sector_profit_monthly_due', 'sector_profit_due_ledger',
            'sector_monthly_due', 'sector_due_ledger',
            'investor_profit_monthly_due', 'investor_profit_due_ledger',
            'investor_monthly_due', 'investor_due_ledger',
            'investor_monthly_profit_details',
            'monthly_profit_summary', 'monthly_sector_profit',
            'sector_investments', 'investment_transactions',
            'retained_earnings_distributions', 'retained_earnings',
            'director_monthly_due', 'director_due_ledger',
            'director_transactions',
            'audit_logs',
            'investors', 'sectors', 'directors',
            'users', 'employees', 'menus', 'user_permissions',
        ];
        foreach ($tables as $t) {
            DB::table($t)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // ── 1. Create superadmin user ───────────────────────────────────
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

        // ── 2. Create primary M/Y director (0 due) ──────────────────────
        $director = Director::create(['name' => 'Sajid (M/Y)', 'is_my' => true]);
        DirectorDueLedger::create(['director_id' => $director->id, 'due' => 0]);

        // ── 3. Create 16 sectors (0 investment) ─────────────────────────
        $sectorNames = [
            'PK M', 'DTF', 'Poshra', 'SKS', 'JFT', 'JF Online',
            'Bike Décor', 'Moto Craft', 'JFMR', 'China House BD',
            'Bike X', 'Dubai', 'EiD Inv PB', 'PT', 'PC', 'A/R',
        ];
        foreach ($sectorNames as $name) {
            $sector = Sector::create(['name' => $name, 'status' => 'active']);
            SectorDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
            SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);
        }

        // ── 4. Create 158 investors (0 investment, all tiers) ───────────
        // Load investor names from the January 2026 data file (names + deed ratios only)
        $janData = json_decode(file_get_contents(database_path('seeders/january_2026_data.json')), true);
        foreach ($janData['investors'] as $inv) {
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
            // 0 investment — empty ledgers
            InvestorDueLedger::create(['investor_id' => $investor->id, 'due' => 0]);
            InvestorProfitDueLedger::create(['investor_id' => $investor->id, 'due' => 0]);
        }

        // ── 5. Create menus + permissions ──────────────────────────────
        $this->call(MenuSeeder::class);
        $this->call(UserPermissionSeeder::class);

        // ── Summary ─────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('  ✅ Clean start — empty state ready!');
        $this->command->info('');
        $this->command->info('  Sectors:              16 (all with 0 balance)');
        $this->command->info('  Investors:            '.count($janData['investors']).' (all with 0 balance)');
        $this->command->info('  M/Y Director:         1 (Sajid, 0 due)');
        $this->command->info('  Total Investment:     0');
        $this->command->info('  Sector Allocated:     0');
        $this->command->info('  Cash in Hand:         0');
        $this->command->info('');
        $this->command->info('  No sector profits, no retained earnings, no transactions.');
        $this->command->info('  Everything is at ZERO — ready for step-by-step testing.');
        $this->command->info('');
        $this->command->info('  Login: E0001 / Mudaraba@2026');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('  See USAGE_GUIDE.md for the step-by-step workflow.');
        $this->command->info('═══════════════════════════════════════════════════');
    }
}
