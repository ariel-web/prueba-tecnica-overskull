<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    private const BASE_CATEGORIES = [
        ['Electrónica', 'Televisores, radios y dispositivos electrónicos de consumo'],
        ['Computación', 'Laptops, accesorios y componentes de computadora'],
        ['Audio y Video', 'Audífonos, parlantes, equipos de sonido y home theater'],
        ['Telefonía', 'Smartphones, fundas, cargadores y accesorios para móviles'],
        ['Hogar y Cocina', 'Utensilios de cocina, menaje y decoración para el hogar'],
        ['Electrodomésticos', 'Línea blanca y pequeños electrodomésticos'],
        ['Ropa y Calzado', 'Vestimenta, calzado y accesorios de moda para todas las edades'],
        ['Deportes y Aire Libre', 'Equipamiento deportivo, fitness y actividades al aire libre'],
        ['Juguetes y Juegos', 'Juguetes educativos, juegos de mesa y entretenimiento infantil'],
        ['Libros y Papelería', 'Libros, material de oficina y artículos escolares'],
        ['Salud y Belleza', 'Cuidado personal, cosméticos y productos de higiene'],
        ['Automotriz', 'Repuestos, accesorios y herramientas para vehículos'],
        ['Herramientas', 'Herramientas manuales, eléctricas y equipos de trabajo'],
        ['Jardín y Exterior', 'Muebles de exterior, plantas y artículos de jardinería'],
        ['Mascotas', 'Alimentos, accesorios y cuidado para mascotas'],
    ];

    private const PREFIXES = [
        'Premium', 'Pro', 'Express', 'Elite', 'Industrial', 'Artesanal',
        'Eco', 'Smart', 'Digital', 'Profesional', 'Mini', 'Max',
    ];

    private const SUFFIXES = [
        'de Lujo', 'Especialidad', 'Alta Gama', 'Uso Diario', 'Edición Limitada',
        'Importado', 'Nacional', 'Certificado', 'Orgánico', 'Sostenible',
    ];

    public function run(): void
    {
        $categories = [];
        $now = now();

        foreach (self::BASE_CATEGORIES as [$name, $description]) {
            $categories[] = [
                'name' => $name,
                'description' => $description,
                'status' => true,
                'created_at' => $now->copy()->subDays(rand(1, 90)),
                'updated_at' => $now,
            ];
        }

        // Generar categorías adicionales hasta llegar a 100
        $faker = \Faker\Factory::create();
        $generated = count($categories);

        while ($generated < 100) {
            $baseName = $faker->randomElement(self::BASE_CATEGORIES)[0];
            $name = $faker->randomElement(self::PREFIXES) . ' ' . $baseName;
            $name .= ' ' . $faker->randomElement(self::SUFFIXES) . ' #' . ($generated + 1);

            $categories[] = [
                'name' => $name,
                'description' => 'Subcategoría de ' . strtolower($baseName) . ' con productos especializados',
                'status' => $faker->boolean(85),
                'created_at' => $now->copy()->subDays(rand(1, 90)),
                'updated_at' => $now,
            ];
            $generated++;
        }

        DB::table('categories')->insert($categories);
    }
}
