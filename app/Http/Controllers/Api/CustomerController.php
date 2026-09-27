<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function orders(string $email)
    {
        $orders = $this->customerService->getCustomerOrders($email);

        return response()->json($orders, Response::HTTP_OK);
    }
}
