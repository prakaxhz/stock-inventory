<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // Happy path
    // -------------------------------------------------------

    public function test_can_create_order_successfully(): void
    {
        Queue::fake();

        $product1 = Product::factory()->create(['price' => 100, 'tax_percentage' => 10, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50,  'tax_percentage' => 5,  'stock' => 20]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'customer_name'  => 'Test User',
            'products' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'customer_id', 'subtotal', 'tax_total', 'grand_total', 'customer', 'order_lines']);

        // subtotal = (100×2) + (50×3) = 350, tax = 20 + 7.5 = 27.5, grand = 377.5
        $this->assertDatabaseHas('orders', ['subtotal' => 350, 'tax_total' => 27.5, 'grand_total' => 377.5]);

        // Stock deducted
        $this->assertDatabaseHas('products', ['id' => $product1->id, 'stock' => 8]);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'stock' => 17]);

        Queue::assertPushed(SendOrderConfirmationJob::class);
    }

    public function test_creates_new_customer_if_email_does_not_exist(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'customer_email' => 'newuser@example.com',
            'customer_name'  => 'New User',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(201);

        $this->assertDatabaseHas('customers', ['email' => 'newuser@example.com', 'name' => 'New User']);
    }

    public function test_reuses_existing_customer_on_order(): void
    {
        Queue::fake();
        $customer = Customer::factory()->create(['email' => 'existing@example.com']);
        $product  = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'customer_email' => 'existing@example.com',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(201);

        $this->assertSame(1, Customer::where('email', 'existing@example.com')->count());
    }

    public function test_confirmation_job_is_dispatched_after_order(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/orders', [
            'customer_email' => 'job@example.com',
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(201);

        Queue::assertPushed(SendOrderConfirmationJob::class, function ($job) {
            return $job->order !== null;
        });
    }

    // -------------------------------------------------------
    // Edge cases - stock / availability
    // -------------------------------------------------------

    public function test_cannot_create_order_with_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [['product_id' => $product->id, 'quantity' => 10]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['products']);

        // Stock must NOT be deducted
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_duplicate_product_ids_in_request_are_merged(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['price' => 100, 'tax_percentage' => 0, 'stock' => 10]);

        // Sending same product twice - should be treated as qty 4 total
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
    }

    public function test_cannot_create_order_when_merged_quantity_exceeds_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        // Each line is fine individually but together exceed stock
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['products']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    // -------------------------------------------------------
    // Edge cases - validation
    // -------------------------------------------------------

    public function test_cannot_create_order_without_customer_email(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'products' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_email']);
    }

    public function test_cannot_create_order_with_invalid_product_id(): void
    {
        $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [['product_id' => 99999, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['products.0.product_id']);
    }

    public function test_cannot_create_order_with_zero_quantity(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [['product_id' => $product->id, 'quantity' => 0]],
        ])->assertStatus(422)->assertJsonValidationErrors(['products.0.quantity']);
    }

    public function test_cannot_create_order_with_empty_products_array(): void
    {
        $this->postJson('/api/orders', [
            'customer_email' => 'test@example.com',
            'products' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['products']);
    }

    // -------------------------------------------------------
    // Customer order history
    // -------------------------------------------------------

    public function test_can_fetch_customer_order_history(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 20]);

        $this->postJson('/api/orders', [
            'customer_email' => 'history@example.com',
            'customer_name'  => 'History User',
            'products' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertStatus(201);

        $response = $this->getJson('/api/customers/history@example.com/orders');

        $response->assertStatus(200)->assertJsonCount(1);
    }

    public function test_returns_404_for_unknown_customer_email(): void
    {
        $this->getJson('/api/customers/nobody@example.com/orders')
            ->assertStatus(404);
    }

    // -------------------------------------------------------
    // Low stock endpoint
    // -------------------------------------------------------

    public function test_low_stock_endpoint_returns_products_below_threshold(): void
    {
        Product::factory()->create(['stock' => 2]);  // below default threshold of 10
        Product::factory()->create(['stock' => 50]); // above threshold

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }
}
