<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'subtotal' => fake()->randomFloat(2, 50, 500),
            'tax_total' => fake()->randomFloat(2, 5, 50),
            'grand_total' => fake()->randomFloat(2, 55, 550),
        ];
    }
}
