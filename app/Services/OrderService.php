<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name'] ?? 'Unknown']
            );

            $productIds = collect($data['products'])->pluck('product_id')->unique()->sort();

            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $taxTotal = 0;
            $orderLinesData = [];

            $requestedQuantities = collect($data['products'])
                ->groupBy('product_id')
                ->map(fn ($group) => $group->sum('quantity'));

            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (!$product) {
                     throw ValidationException::withMessages(['products' => "Product ID {$productId} not found."]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'products' => "Insufficient stock for product: {$product->name}. Requested: {$quantity}, Available: {$product->stock}."
                    ]);
                }

                $product->stock -= $quantity;
                $product->save();

                $unitPrice = $product->price;
                $lineTotal = $unitPrice * $quantity;
                $lineTax = ($lineTotal * $product->tax_percentage) / 100;

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;

                $orderLinesData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $grandTotal = $subtotal + $taxTotal;

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal'    => $subtotal,
                'tax_total'   => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            foreach ($orderLinesData as $lineData) {
                $order->orderLines()->create($lineData);
            }

            return $order;
        });
    }
}
