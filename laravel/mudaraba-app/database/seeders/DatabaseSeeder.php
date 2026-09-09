<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default: clean empty state — all investors/sectors at 0 balance.
        // Use this for step-by-step testing of the system workflow.
        //
        // To load the canonical July 2026 reference data instead:
        //   php artisan db:seed --class=July2026Seeder
        //
        // To load only January 2026 data:
        //   php artisan db:seed --class=January2026Seeder
        $this->call([
            CleanStartSeeder::class,
        ]);
    }
}
