<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['min_price'] ?? null, fn ($q, $min) => $q->where('price', '>=', $min))
            ->when($filters['max_price'] ?? null, fn ($q, $max) => $q->where('price', '<=', $max))
            ->when($filters['min_stock'] ?? null, fn ($q, $min) => $q->where('stock', '>=', $min))
            ->when($filters['max_stock'] ?? null, fn ($q, $max) => $q->where('stock', '<=', $max))
            ->when(
                $filters['sort'] ?? null,
                fn ($q, $sort) => $q->orderBy($sort, $filters['order'] ?? 'desc'),
                fn ($q) => $q->orderBy('created_at', 'desc')
            )
            ->paginate($filters['per_page'] ?? 15);
    }

    public function get(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $product = Product::create($data);
        $this->flushCache();

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        $this->flushCache();

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
        $this->flushCache();
    }

    private function flushCache(): void
    {
        Cache::forget('dashboard.metrics');
    }
}
