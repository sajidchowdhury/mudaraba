<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\SectorDueLedger;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        // Real sectors from PHP SQL dump with their opening balances
        $sectors = [
            ['name' => 'Moto Craft',      'mobile' => '1764921045', 'balance' => 6636450],
            ['name' => 'Bike X',          'mobile' => '1768363187', 'balance' => 44603550],
            ['name' => 'China House BD', 'mobile' => '1768363206', 'balance' => 46155000],
            ['name' => 'JFT Mirpur',     'mobile' => '1768363214', 'balance' => 9147000],
        ];

        foreach ($sectors as $data) {
            $balance = $data['balance'];
            unset($data['balance']);

            $sector = Sector::firstOrCreate(['name' => $data['name']], array_merge($data, [
                'address' => 'n/a',
                'status' => 'active',
            ]));

            // Seed the due ledger with the opening balance
            SectorDueLedger::firstOrCreate(
                ['sector_id' => $sector->id],
                ['due' => $balance],
            );
        }

        // Add 2 more random active sectors
        Sector::factory()->count(2)->active()->create()->each(function (Sector $sector) {
            SectorDueLedger::create([
                'sector_id' => $sector->id,
                'due' => fake()->numberBetween(100000, 5000000),
            ]);
        });
    }
}
