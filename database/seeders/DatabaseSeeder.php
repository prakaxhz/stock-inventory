<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Customers
        $customers = Customer::factory(10)->create();

        // Seed Products
        $products = Product::factory(20)->create();

        // Seed Orders for each customer
        foreach ($customers as $customer) {
            // Create 1-3 orders for this customer
            $orders = Order::factory(random_int(1, 3))->create([
                'customer_id' => $customer->id
            ]);

            foreach ($orders as $order) {
                $subtotal = 0;
                $taxTotal = 0;

                // Pick 1-4 random products for the order
                $orderProducts = $products->random(random_int(1, 4));

                foreach ($orderProducts as $product) {
                    $quantity = random_int(1, 5);
                    $unitPrice = $product->price;
                    $lineTotal = $quantity * $unitPrice;
                    $lineTax = ($lineTotal * $product->tax_percentage) / 100;

                    OrderLine::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;
                    $taxTotal += $lineTax;
                }

                $order->update([
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => $subtotal + $taxTotal,
                ]);
            }
        }
    }
}
