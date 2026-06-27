<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => null,
            'type' => fake()->randomElement(['entrada', 'salida']),
            'quantity' => fake()->numberBetween(1, 20),
            'reason' => fake()->sentence(),
            'user_id' => null,
        ];
    }
}
