<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    public function getCustomerOrders(string $email): Collection
    {
        $customer = Customer::where('email', $email)->firstOrFail();

        return $customer->orders()->with('orderLines.product')->get();
    }
}
