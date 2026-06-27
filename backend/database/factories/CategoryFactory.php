<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $prefix = fake()->randomElement(['Premium', 'Pro', 'Express', 'Elite', 'Industrial', 'Smart', 'Eco', 'Digital']);
        $base = fake()->randomElement([
            'Electrónica', 'Computación', 'Audio', 'Telefonía', 'Hogar',
            'Cocina', 'Moda', 'Deportes', 'Juegos', 'Libros',
            'Belleza', 'Automotriz', 'Herramientas', 'Jardín', 'Mascotas',
        ]);

        return [
            'name' => "{$prefix} {$base} " . fake()->numberBetween(100, 999),
            'description' => 'Categoría de productos de ' . strtolower($base),
            'status' => fake()->boolean(85),
        ];
    }
}
