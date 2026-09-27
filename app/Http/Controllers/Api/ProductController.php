<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $products = $this->productService->getAllProducts();

        return response()->json($products, Response::HTTP_OK);
    }

    public function lowStock()
    {
        $products = $this->productService->getLowStockProducts();

        return response()->json($products, Response::HTTP_OK);
    }
}
