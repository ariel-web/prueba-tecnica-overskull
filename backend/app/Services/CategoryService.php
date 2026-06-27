<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CategoryService
{
    private const CACHE_PREFIX = 'categories:list:';
    private const CACHE_TTL = 600;

    public function list(array $filters = []): LengthAwarePaginator
    {
        $cacheKey = self::CACHE_PREFIX . md5(json_encode($filters));

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => Category::query()
                ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['q'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 15)
        );
    }

    public function get(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function create(array $data): Category
    {
        $category = Category::create($data);
        $this->flushCache();

        return $category;
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        $this->flushCache();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
        $this->flushCache();
    }

    public function sync(): array
    {
        $this->flushCache();

        $defaultFilters = [
            ['per_page' => 100],
            ['per_page' => 100, 'status' => 1],
            ['per_page' => 100, 'status' => 0],
        ];

        foreach ($defaultFilters as $filters) {
            $this->list($filters);
        }

        return [
            'status' => 'synced',
            'cached_keys' => count($defaultFilters),
        ];
    }

    private function flushCache(): void
    {
        Cache::forget('dashboard.metrics');

        $prefix = config('cache.prefix', '');
        $keys = Redis::keys($prefix . self::CACHE_PREFIX . '*');
        foreach ($keys as $key) {
            Cache::forget(str_replace($prefix, '', $key));
        }
    }
}
