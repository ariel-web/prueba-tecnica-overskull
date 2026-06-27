<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    private const ADJECTIVES = ['Premium', 'Pro', 'Ultra', 'Max', 'Lite', 'Plus', 'Classic', 'Smart', 'Eco', 'Elite'];

    private const NOUNS = [
        'Smartphone', 'Laptop', 'Audífonos', 'Teclado', 'Monitor', 'Cámara',
        'Parlante', 'Tablet', 'Mouse', 'Webcam', 'Impresora', 'Proyector',
        'Sartén', 'Olla', 'Cuchillo', 'Licuadora', 'Cafetera', 'Plancha',
        'Zapatillas', 'Polerón', 'Mochila', 'Termo', 'Botella', 'Guantes',
        'Balón', 'Bicicleta', 'Casco', 'Mancuernas', 'Tabla', 'Cuerda',
        'Taladro', 'Llave', 'Amoladora', 'Nivel', 'Destornillador', 'Martillo',
        'Mecedora', 'Macetero', 'Manguera', 'Paraguas', 'Kit', 'Set',
    ];

    private const BRANDS = [
        'Sony', 'Samsung', 'LG', 'Philips', 'Bose', 'JBL', 'Logitech',
        'Dell', 'HP', 'ASUS', 'Apple', 'Xiaomi', 'Bosch', 'Makita',
        'Tefal', 'Tramontina', 'Victorinox', 'Nike', 'Adidas', 'Stanley',
    ];

    private const TARGET_COUNT = 10000;
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $maxCategoryId = (int) DB::table('categories')->max('id');
        $now = now();

        for ($offset = 0; $offset < self::TARGET_COUNT; $offset += self::CHUNK_SIZE) {
            $batch = [];
            $batchSize = min(self::CHUNK_SIZE, self::TARGET_COUNT - $offset);

            for ($i = 0; $i < $batchSize; $i++) {
                $batch[] = [
                    'name' => $faker->randomElement(self::BRANDS) . ' '
                        . $faker->randomElement(self::NOUNS) . ' '
                        . $faker->randomElement(self::ADJECTIVES) . ' '
                        . $faker->numberBetween(1000, 9999),
                    'description' => $faker->sentence(8),
                    'price' => $faker->numberBetween(9990, 999990),
                    'stock' => $faker->numberBetween(0, 200),
                    'category_id' => $faker->numberBetween(1, $maxCategoryId),
                    'status' => $faker->boolean(90),
                    'created_at' => $now->copy()->subDays(rand(1, 180)),
                    'updated_at' => $now,
                ];
            }

            DB::table('products')->insert($batch);
        }
    }
}
