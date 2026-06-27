<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    private const ENTRY_REASONS = [
        'Compra a proveedor',
        'Reposición de stock',
        'Devolución de cliente',
        'Ajuste positivo de inventario',
        'Transferencia entrante de sucursal',
        'Recepción de mercancía en consignación',
        'Reingreso por cancelación de venta',
    ];

    private const EXIT_REASONS = [
        'Venta directa',
        'Venta online',
        'Devolución a proveedor por defecto',
        'Daño de mercancía',
        'Merma por caducidad',
        'Ajuste negativo de inventario',
        'Transferencia saliente a sucursal',
        'Salida por garantía',
    ];

    private const TARGET_COUNT = 1000;
    private const CHUNK_SIZE = 250;

    public function run(): void
    {
        DB::table('stock_movements')->truncate();
        $maxProductId = (int) DB::table('products')->max('id');
        $movements = [];
        $now = now();
        $targetCount = (int) env('SEED_STOCK_MOVEMENT_COUNT', self::TARGET_COUNT);
        $chunkSize = (int) env('SEED_STOCK_MOVEMENT_CHUNK_SIZE', self::CHUNK_SIZE);

        for ($i = 1; $i <= $targetCount; $i++) {
            $type = rand(0, 1) ? 'entrada' : 'salida';
            $reasons = $type === 'entrada' ? self::ENTRY_REASONS : self::EXIT_REASONS;

            $movements[] = [
                'product_id' => rand(1, $maxProductId),
                'type' => $type,
                'quantity' => rand(1, 50),
                'reason' => $reasons[array_rand($reasons)],
                'user_id' => 1,
                'created_at' => $now->copy()->subDays(rand(0, 180)),
                'updated_at' => $now,
            ];

            if (count($movements) === $chunkSize) {
                DB::table('stock_movements')->insert($movements);
                $movements = [];
            }
        }

        if (!empty($movements)) {
            DB::table('stock_movements')->insert($movements);
        }
    }
}
