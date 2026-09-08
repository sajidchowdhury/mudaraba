<?php

namespace Database\Seeders;

use App\Models\Investor;
use App\Models\InvestorDueLedger;
use Illuminate\Database\Seeder;

class InvestorSeeder extends Seeder
{
    public function run(): void
    {
        // Sample investors inspired by the PHP SQL dump (4 real + 6 faker)
        $investors = [
            ['name' => 'Kazi Afzal Noor', 'reference' => 'MD', 'mobile' => '1', 'deed_ratio' => '100', 'balance' => 4000000],
            ['name' => 'Anwar Noor Topu', 'reference' => 'German', 'mobile' => '2', 'deed_ratio' => '80', 'balance' => 3000000],
            ['name' => 'Papun', 'reference' => 'MD', 'mobile' => '3', 'deed_ratio' => '60', 'balance' => 6000000],
            ['name' => 'Siddik U', 'reference' => 'MD', 'mobile' => '4', 'deed_ratio' => '100', 'balance' => 6000000],
            ['name' => 'Yusha B', 'reference' => 'MD Ushan Rahman', 'mobile' => '5', 'deed_ratio' => '100', 'balance' => 11085000],
            ['name' => 'Muhammad', 'reference' => 'Nahid Parvez', 'mobile' => '6', 'deed_ratio' => '100', 'balance' => 8955000],
        ];

        foreach ($investors as $data) {
            $balance = $data['balance'];
            unset($data['balance']);

            $investor = Investor::firstOrCreate(['name' => $data['name']], array_merge($data, [
                'start_profit_month' => '2025-11-01',
                'end_profit_month' => '2030-11-01',
                'status' => 'active',
            ]));

            // Seed the due ledger with the opening balance
            InvestorDueLedger::firstOrCreate(
                ['investor_id' => $investor->id],
                ['due' => $balance],
            );
        }

        // Add 6 more random investors
        Investor::factory()->count(6)->active()->create()->each(function (Investor $investor) {
            InvestorDueLedger::create([
                'investor_id' => $investor->id,
                'due' => fake()->numberBetween(50000, 5000000),
            ]);
        });
    }
}
