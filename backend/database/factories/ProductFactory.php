<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    private array $adjectives = ['Premium', 'Pro', 'Ultra', 'Max', 'Lite', 'Plus', 'Classic', 'Smart', 'Eco', 'Elite'];
    private array $nouns = [
        'Smartphone', 'Laptop', 'Audífonos', 'Teclado', 'Monitor', 'Cámara',
        'Parlante', 'Tablet', 'Mouse', 'Webcam', 'Impresora', 'Proyector',
        'Sartén', 'Olla', 'Cuchillo', 'Licuadora', 'Cafetera', 'Plancha',
        'Zapatillas', 'Polerón', 'Mochila', 'Termo', 'Botella', 'Guantes',
    ];
    private array $brands = [
        'Sony', 'Samsung', 'LG', 'Philips', 'Bose', 'JBL', 'Logitech',
        'Dell', 'HP', 'ASUS', 'Apple', 'Xiaomi', 'Bosch', 'Makita',
        'Tefal', 'Tramontina', 'Victorinox', 'Nike', 'Adidas',
    ];

    public function definition(): array
    {
        $name = $this->faker->randomElement($this->brands) . ' '
            . $this->faker->randomElement($this->nouns) . ' '
            . $this->faker->randomElement($this->adjectives);

        return [
            'name' => $name,
            'description' => $this->faker->sentence(8),
            'price' => $this->faker->randomFloat(2, 9990, 999990),
            'stock' => $this->faker->numberBetween(0, 200),
            'category_id' => Category::factory(),
            'status' => $this->faker->boolean(90),
        ];
    }
}
