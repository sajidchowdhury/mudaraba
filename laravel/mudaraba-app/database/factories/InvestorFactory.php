<?php

namespace Database\Factories;

use App\Models\Investor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investor>
 */
class InvestorFactory extends Factory
{
    public function definition(): array
    {
        $deedRatios = ['100', '80', '60'];
        $statuses = ['active', 'active', 'active', 'inactive', 'closed'];

        return [
            'name' => fake()->name(),
            'reference' => fake()->optional(0.7)->randomElement(['MD', 'German', 'Family', 'Friend']),
            'mobile' => fake()->optional(0.8)->numerify('01#########'),
            'address' => fake()->optional(0.5)->address(),
            'deed_ratio' => fake()->randomElement($deedRatios),
            'start_profit_month' => fake()->optional(0.8)->dateTimeBetween('-2 years', 'now')?->format('Y-m-01'),
            'end_profit_month' => fake()->optional(0.2)->dateTimeBetween('+1 year', '+5 years')?->format('Y-m-01'),
            'status' => fake()->randomElement($statuses),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function tier100(): static
    {
        return $this->state(fn () => ['deed_ratio' => '100']);
    }

    public function tier80(): static
    {
        return $this->state(fn () => ['deed_ratio' => '80']);
    }

    public function tier60(): static
    {
        return $this->state(fn () => ['deed_ratio' => '60']);
    }
}
