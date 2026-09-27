<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Jobs\SendOrderConfirmationJob;
use App\Services\OrderService;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->validated());

        SendOrderConfirmationJob::dispatch($order);

        return response()->json($order->load(['customer', 'orderLines.product']), Response::HTTP_CREATED);
    }
}
