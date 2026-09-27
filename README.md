# Store Order & Inventory Mini-System

A Laravel-based retail counter application for recording customer orders against a product catalog, with real-time stock management, tax computation, and async order confirmation.

---

## Setup Instructions

### 1. Clone & Install Dependencies
```bash
git clone <repo-url>
cd store-order-system
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database and queue:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_inventory
DB_USERNAME=root
DB_PASSWORD=your_password

QUEUE_CONNECTION=database

# Optional: configure the low-stock alert threshold (default: 10)
LOW_STOCK_THRESHOLD=10
```

### 3. Run Migrations & Seed Sample Data
```bash
php artisan migrate --seed
```
This seeds the database with sample **Customers**, **Products**, and realistic **Orders**.

### 4. Start the Queue Worker
The application dispatches a queued job when an order is created. Run the queue worker to process it:
```bash
php artisan queue:work
```

### 5. Start the Dev Server
```bash
php artisan serve
```
Visit **http://127.0.0.1:8000** in your browser to access the frontend UI.

---

## Running Tests
Run the PHPUnit test suite to verify the logic and edge cases:
```bash
php artisan test
```

---

## Architecture & Design Decisions

### 1. Service Layer Pattern
I kept the Controllers thin (only handling HTTP requests and validation) and moved all core business logic into dedicated Service classes (`OrderService`, `ProductService`, `CustomerService`). This makes the logic reusable, testable, and easier to maintain.

### 2. Concurrency Safety (No Overselling)
When multiple users try to buy the same product at the exact same time, there is a risk of overselling (e.g., selling 2 units when only 1 is left). 
To prevent this, the `OrderService` wraps order creation in a database transaction (`DB::transaction`) and uses pessimistic locking (`lockForUpdate()`) on the `products` table. 
- The product rows are locked during the transaction.
- Product IDs are sorted before locking to prevent database deadlocks.
- Any concurrent request for the same products will wait until the lock is released, ensuring stock is validated sequentially.

### 3. Queued Jobs for Async Processing
Instead of slowing down the order creation API to send an email, a `SendOrderConfirmationJob` is dispatched to the background queue. It uses Laravel's `database` queue driver to persist the job.

### 4. Handling Duplicate Products in a Single Request
If an API request sends the same product ID multiple times in the payload, the `OrderService` consolidates them (sums their quantities) before checking stock to prevent bypassing stock limits.

---

## Assumptions Made

1. **Static Pricing for Historic Orders:** I assumed that product prices might change in the future. To preserve historical accuracy, the `unit_price` is copied from the `Product` into the `OrderLine` at the time of purchase.
2. **Tax Calculation:** I assumed tax is applied per product individually, not on the overall subtotal. This is common in retail where different products have different tax brackets. `grand_total` is calculated as `subtotal + sum(line_taxes)`.
3. **Customer Upsert:** If an order request comes in with an email but no name, or a new name, I assumed the `email` is the unique identifier. The system uses `firstOrCreate` to either fetch the existing customer or create a new one with the provided name (or "Unknown").
4. **UI Framework:** I assumed a lightweight frontend approach was acceptable, so I used Vanilla JavaScript with AJAX `fetch` calls and Tailwind CSS (via CDN) to build the UI without requiring an NPM build step.
5. **No Authentication:** I assumed authentication was out of scope for this mini-system, so the API endpoints are public.

---

## API Documentation

### 1. Create an Order
`POST /api/orders`
```json
{
  "customer_email": "john@example.com",
  "customer_name": "John Doe",
  "products": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

### 2. Customer Order History
`GET /api/customers/{email}/orders`
Returns all orders (with line items) for a given customer.

### 3. Low-Stock Products
`GET /api/products/low-stock`
Returns products where `stock` is less than `LOW_STOCK_THRESHOLD`.

### 4. All Products
`GET /api/products`
Returns a list of all available products.

---

## AI Prompt Log

As per the assessment requirements, AI-assisted tools were used during development. You can find the prompt history detailing exactly what was asked in the `prompts` folder.

- `prompts/` — Contains screenshots of the AI conversations used during development.