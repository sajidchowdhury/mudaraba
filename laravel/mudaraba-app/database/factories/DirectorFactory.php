<?php

namespace Database\Factories;

use App\Models\Director;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Director>
 */
class DirectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => fake()->optional(0.8)->numerify('01#########'),
            'address' => fake()->optional(0.4)->address(),
            'is_my' => false,
        ];
    }

    public function primaryMy(): static
    {
        return $this->state(fn () => ['is_my' => true]);
    }
}
