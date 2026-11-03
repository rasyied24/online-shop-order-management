<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(OrderIndexRequest $request): JsonResponse
    {
        $orders = Order::query()
            ->with('customer')
            ->withCount('items')
            ->filter($request->validated())
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate((int) $request->validated('per_page', 10));

        return ApiResponse::paginated($orders, OrderResource::class);
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute(
            $request->user(),
            $request->validated('customer_id'),
            $request->validated('items'),
        );

        return ApiResponse::success(new OrderResource($order), 'Order berhasil dibuat', 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'creator', 'items.product']);

        return ApiResponse::success(new OrderResource($order));
    }
}
