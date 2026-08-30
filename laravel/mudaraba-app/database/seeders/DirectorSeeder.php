<?php

namespace Database\Seeders;

use App\Models\Director;
use App\Models\DirectorDueLedger;
use Illuminate\Database\Seeder;

class DirectorSeeder extends Seeder
{
    public function run(): void
    {
        // Real directors from PHP SQL dump
        // Mohammad is the primary M/Y (managing owner), Ushan is a co-director
        $directors = [
            ['name' => 'Mohammad', 'mobile' => '1756321023', 'is_my' => true,  'balance' => 136162],
            ['name' => 'Ushan',    'mobile' => '1756321294', 'is_my' => false, 'balance' => 0],
        ];

        foreach ($directors as $data) {
            $balance = $data['balance'];
            unset($data['balance']);

            $director = Director::firstOrCreate(['name' => $data['name']], array_merge($data, [
                'address' => 'n/a',
            ]));

            DirectorDueLedger::firstOrCreate(
                ['director_id' => $director->id],
                ['due' => $balance],
            );
        }
    }
}
