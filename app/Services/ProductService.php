<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function getAllProducts(): Collection
    {
        return Product::orderBy('name')->get();
    }

    public function getLowStockProducts(): Collection
    {
        $threshold = config('shop.low_stock_threshold', 10);
        return Product::where('stock', '<', $threshold)->get();
    }
}
