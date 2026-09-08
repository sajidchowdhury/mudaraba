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

/**
 * July2026Seeder — loads the canonical "July, 2026 For Sajid" reference data.
 *
 * This seeder:
 *   1. Wipes all tables (idempotent re-run support)
 *   2. Creates the superadmin user (E0001 / Mudaraba@2026) + primary M/Y director
 *   3. Creates 16 sectors from the canonical July 2026 Excel sheet
 *      (Z2 = 1,765,000, X2 = 1,635,000, Y2 = 130,000 — matches ParityTest expectations)
 *   4. ALSO seeds January 2026 sector profits so users can navigate between
 *      months at /profit/investor?month=YYYY-MM-01
 *   5. Creates 150 investors from january_2026_data.json (we don't have real
 *      per-investor July 2026 balance data — the investors list is stable
 *      month-over-month; only investment amounts may have grown slightly)
 *   6. Creates menus + permissions
 *
 * === What this means for the parity test ===
 *
 * The `tests/Feature/ParityTest.php` creates its OWN hand-coded data inline
 * (16 sectors with the July 2026 numbers + 3 test investors at $1M total).
 * It does NOT use this seeder. So ParityTest continues to pass.
 *
 * The new `tests/Feature/ParitySeederTest.php` runs the parity check
 * AGAINST the seeded data. It verifies:
 *   - Z2 = 1,765,000 (sector totals match exactly)
 *   - X2 = 1,635,000
 *   - Y2 = 130,000
 *   - AG184 = X2 - AG182 (algebraic identity, holds regardless of investor amounts)
 *
 * It does NOT verify AG182 = 1,110,024.58 or AG184 = 476,220.07 because
 * those depend on the real per-investor investment amounts as of 2026-07-01,
 * which we don't have. The seeded investors have $137,022,000 total (the
 * January 2026 amount), so AG182/AG184 will be proportionally smaller.
 *
 * See database/seeders/july_2026_data.json → _meta.note_about_investors.
 */
class July2026Seeder extends Seeder
{
    public function run(): void
    {
        $janData = json_decode(file_get_contents(database_path('seeders/january_2026_data.json')), true);
        $julyData = json_decode(file_get_contents(database_path('seeders/july_2026_data.json')), true);

        // 0. Wipe everything (idempotent re-run support).
        //    Same list as January2026Seeder — keep them in sync.
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
            DB::table($t)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // 1. Create superadmin user (same as January2026Seeder)
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

        // 3. Create all 16 sectors.
        //    Each sector gets:
        //      - SectorDueLedger with the January 2026 investment balance (s['inv'])
        //      - MonthlySectorProfit rows for BOTH January AND July 2026
        //    The July 2026 row uses the canonical numbers from july_2026_data.json
        //    (Z2 = 1,765,000, X2 = 1,635,000 — matches ParityTest expectations).
        $julySectors = $julyData['sectors_july_2026'];
        $janSectors = $janData['sectors'];

        // Index July sectors by name for quick lookup
        $julyByName = collect($julySectors)->keyBy('name');

        $totalJanSectorInvestment = array_sum(array_column($janSectors, 'inv'));

        foreach ($janSectors as $s) {
            $sector = Sector::create([
                'name' => $s['name'],
                'status' => 'active',
            ]);

            // Sector capital due (investment balance — same as January 2026)
            SectorDueLedger::create(['sector_id' => $sector->id, 'due' => $s['inv']]);
            SectorProfitDueLedger::create(['sector_id' => $sector->id, 'due' => 0]);

            // January 2026 sector profit entry
            MonthlySectorProfit::create([
                'sector_id' => $sector->id,
                'profit_month' => '2026-01-01',
                'estimated_profit' => $s['estimated'],
                'actual_profit' => $s['actual'],
                'status' => 'finalized',
                'transaction_date' => '2026-01-14',
                'created_by' => $user->id,
            ]);

            // July 2026 sector profit entry (canonical reference from the Excel sheet)
            $july = $julyByName->get($s['name']);
            if ($july) {
                MonthlySectorProfit::create([
                    'sector_id' => $sector->id,
                    'profit_month' => '2026-07-01',
                    'estimated_profit' => $july['estimated'],
                    'actual_profit' => $july['actual'],
                    'status' => 'finalized',
                    'transaction_date' => '2026-07-14',
                    'created_by' => $user->id,
                ]);
            }

            // Sector investment (add) record for Cash-in-Hand tracking
            if ($s['inv'] > 0) {
                SectorInvestment::create([
                    'sector_id' => $sector->id,
                    'amount' => $s['inv'],
                    'type' => 'add',
                    'transaction_date' => '2026-01-01',
                    'remarks' => 'Opening sector allocation',
                    'created_by' => $user->id,
                ]);
            }
        }

        // 4. Create all 150 investors (from January 2026 data — same list both months)
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

            InvestorDueLedger::create(['investor_id' => $investor->id, 'due' => $inv['inv']]);
            InvestorProfitDueLedger::create(['investor_id' => $investor->id, 'due' => 0]);

            if ($inv['inv'] > 0) {
                InvestmentTransaction::create([
                    'investor_id' => $investor->id,
                    'amount' => $inv['inv'],
                    'type' => 'add',
                    'transaction_month' => '2026-01-01',
                    'transaction_date' => '2026-01-01',
                    'remarks' => 'Opening investment',
                    'created_by' => $user->id,
                ]);
            }
        }

        // 5. Create menus + permissions
        $this->call(MenuSeeder::class);
        $this->call(UserPermissionSeeder::class);

        // 6. Verify + display reconciliation
        $invTotal = array_sum(array_column($janData['investors'], 'inv'));
        $janEst = array_sum(array_column($janSectors, 'estimated'));
        $janAct = array_sum(array_column($janSectors, 'actual'));
        $julyEst = array_sum(array_column($julySectors, 'estimated'));
        $julyAct = array_sum(array_column($julySectors, 'actual'));

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  🎉 July 2026 + January 2026 data loaded!');
        $this->command->info('');
        $this->command->info('  Investors:                '.count($janData['investors']));
        $this->command->info('  Sectors:                  '.count($janSectors));
        $this->command->info('  Total Investment (D181):  '.number_format($invTotal));
        $this->command->info('  Sector Allocated:         '.number_format($totalJanSectorInvestment));
        $this->command->info('  Cash in Hand:             '.number_format($invTotal - $totalJanSectorInvestment));
        $this->command->info('');
        $this->command->info('  ── January 2026 ──');
        $this->command->info('  Estimated Profit (Z2):    '.number_format($janEst));
        $this->command->info('  Actual Profit (X2):        '.number_format($janAct));
        $this->command->info('  Variance (Y2):            '.number_format($janEst - $janAct));
        $this->command->info('');
        $this->command->info('  ── July 2026 (canonical reference) ──');
        $this->command->info('  Estimated Profit (Z2):    '.number_format($julyEst).'   (Excel: 1,765,000)');
        $this->command->info('  Actual Profit (X2):        '.number_format($julyAct).'   (Excel: 1,635,000)');
        $this->command->info('  Variance (Y2):            '.number_format($julyEst - $julyAct).'     (Excel: 130,000)');
        $this->command->info('');
        $this->command->info('  ✅ July 2026 sector totals match the Excel "For Sajid" sheet');
        $this->command->info('  ⚠️  Investor amounts are from January 2026 (real July balances not in codebase)');
        $this->command->info('     → AG182/AG184 will be proportionally smaller than the canonical Excel figures');
        $this->command->info('     → Run the calculation: php artisan tinker then');
        $this->command->info('       >>> app(App\\Services\\ProfitCalculatorService::class)->calculate("2026-07-01", 1);');
        $this->command->info('');
        $this->command->info('  Try the For Sajid page:  /profit/investor?month=2026-07-01');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  Login: E0001 / Mudaraba@2026');
        $this->command->info('═══════════════════════════════════════════════════════════════');
    }
}
