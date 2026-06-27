<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function listByProduct(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(int $productId, array $data, int $userId): StockMovement
    {
        return DB::transaction(function () use ($productId, $data, $userId) {
            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();

            if (!$product) {
                abort(404, 'Product not found');
            }

            if ($data['type'] === StockMovement::TYPE_SALIDA && $product->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock insuficiente',
                ]);
            }

            $newStock = $data['type'] === StockMovement::TYPE_SALIDA
                ? $product->stock - $data['quantity']
                : $product->stock + $data['quantity'];

            DB::table('products')->where('id', $productId)->update(['stock' => $newStock]);

            return StockMovement::create([
                'product_id' => $productId,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'reason' => $data['reason'] ?? null,
                'user_id' => $userId,
            ])->load('product', 'user');
        });
    }
}
