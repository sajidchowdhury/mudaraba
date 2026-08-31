<?php

namespace Database\Seeders;

use App\Models\Director;
use App\Models\DirectorDueLedger;
use App\Models\Employee;
use App\Models\Investor;
use App\Models\InvestorDueLedger;
use App\Models\InvestorProfitDueLedger;
use App\Models\MonthlySectorProfit;
use App\Models\Sector;
use App\Models\SectorDueLedger;
use App\Models\SectorProfitDueLedger;
use App\Models\User;
use Illuminate\Database\Seeder;

class January2026Seeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/january_2026_data.json')), true);

        // 1. Create superadmin user
        $employee = Employee::firstOrCreate(
            ['name' => 'Mohammad'],
            ['designation' => 'Senior IT Executive', 'phone_number' => '8801911599014', 'status' => 'Active']
        );

        $user = User::firstOrCreate(
            ['username' => 'E0001'],
            [
                'employee_id' => $employee->id,
                'email' => 'admin@mudaraba.test',
                'password_hash' => bcrypt('Mudaraba@2026'),
                'role' => 'superadmin',
                'status' => 'Active',
                'login_start' => '00:00:00',
                'login_end' => '23:59:59',
            ]
        );

        // 2. Create primary M/Y director
        $director = Director::firstOrCreate(
            ['name' => 'Sajid (M/Y)'],
            ['is_my' => true]
        );
        DirectorDueLedger::firstOrCreate(
            ['director_id' => $director->id],
            ['due' => 0]
        );

        // 3. Create all sectors from the Excel sheet
        foreach ($data['sectors'] as $s) {
            $sector = Sector::firstOrCreate(
                ['name' => $s['name']],
                ['status' => 'active']
            );

            // Set sector capital due (investment balance)
            SectorDueLedger::firstOrCreate(
                ['sector_id' => $sector->id],
                ['due' => $s['inv']]
            );
            SectorProfitDueLedger::firstOrCreate(
                ['sector_id' => $sector->id],
                ['due' => 0]
            );

            // Create January 2026 sector profit entry
            // estimated = Z column (advance profit given), actual = Y column (realized)
            MonthlySectorProfit::firstOrCreate(
                ['sector_id' => $sector->id, 'profit_month' => '2026-01-01'],
                [
                    'estimated_profit' => $s['estimated'],
                    'actual_profit' => $s['actual'],
                    'status' => 'finalized',
                    'transaction_date' => '2026-01-14',
                    'created_by' => $user->id,
                ]
            );
        }

        // 4. Create all investors from the Excel sheet
        foreach ($data['investors'] as $inv) {
            $investor = Investor::firstOrCreate(
                ['name' => $inv['name']],
                [
                    'reference' => $inv['ref'] ?: null,
                    'mobile' => null,
                    'address' => null,
                    'deed_ratio' => $inv['deed'],
                    'start_profit_month' => '2025-01-01',
                    'end_profit_month' => '2030-12-31',
                    'status' => 'active',
                ]
            );

            // Set investor capital due (investment balance = D column)
            InvestorDueLedger::firstOrCreate(
                ['investor_id' => $investor->id],
                ['due' => $inv['inv']]
            );
            InvestorProfitDueLedger::firstOrCreate(
                ['investor_id' => $investor->id],
                ['due' => 0]
            );
        }

        // 5. Create menus + permissions
        $this->call(MenuSeeder::class);
        $this->call(UserPermissionSeeder::class);

        echo "\n";
        echo "═══════════════════════════════════════════════════\n";
        echo "  🎉 January 2026 data loaded!\n\n";
        echo '  Investors: '.count($data['investors'])."\n";
        echo '  Sectors: '.count($data['sectors'])."\n";
        echo '  Total Investment: '.number_format($data['totals']['total_investment'])."\n";
        echo '  Estimated Profit (Z2): '.number_format($data['totals']['z2_estimated'])."\n";
        echo '  Actual Profit (Y2): '.number_format($data['totals']['y2_actual'])."\n";
        echo "═══════════════════════════════════════════════════\n";
    }
}
