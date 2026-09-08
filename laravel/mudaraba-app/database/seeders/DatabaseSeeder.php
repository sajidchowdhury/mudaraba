<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default: load the canonical "July, 2026 For Sajid" reference data.
        // Also loads January 2026 sector profits so users can navigate between
        // months at /profit/investor?month=YYYY-MM-01.
        //
        // To load ONLY January 2026 (the original seeder), run:
        //   php artisan db:seed --class=January2026Seeder
        //
        // See laravel/MUDARABA_LARAVEL_PROJECT_PLAN.md §8.1 for the canonical
        // reference values (Z2=1,765,000, X2=1,635,000, Y2=130,000).
        $this->call([
            July2026Seeder::class,
        ]);
    }
}
