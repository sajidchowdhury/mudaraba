<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    public function definition(): array
    {
        $sectors = ['China House BD', 'Bike X', 'SKS', 'JFT', 'JF Online', 'Moto Craft', 'JFMR', 'Dubai Branch', 'PT'];

        return [
            'name' => fake()->unique()->randomElement($sectors).' '.fake()->numberBetween(1, 99),
            'mobile' => fake()->optional(0.7)->numerify('01#########'),
            'address' => fake()->optional(0.4)->address(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'closed']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
