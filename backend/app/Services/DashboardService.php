<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function metrics(): array
    {
        return Cache::remember('dashboard.metrics', 300, function () {
            return [
                'products' => Product::count(),
                'categories' => Category::count(),
                'low_stock' => Product::where('stock', '<', 10)
                    ->with('category')
                    ->orderBy('stock', 'asc')
                    ->limit(50)
                    ->get(),
                'last_movements' => StockMovement::with(['product', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get(),
            ];
        });
    }
}
