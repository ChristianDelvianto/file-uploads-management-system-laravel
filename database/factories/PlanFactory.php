<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(rand(2, 3)),
            'price' => fake()->numberBetween(20, 500),
            'is_active' => true,
            'size' => fake()->numberBetween(1e7, 10e8),
        ];
    }
}
