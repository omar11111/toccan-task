<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrderOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    use AuthorizesOrderOwnership;

    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', $this->currentUserId())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->with('items')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($this->currentUserId(), $request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        $this->authorizeOrderOwnership($order);

        return new OrderResource($order->load('items', 'payments'));
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $this->authorizeOrderOwnership($order);

        $order = $this->orderService->update($order, $request->validated());

        return new OrderResource($order);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorizeOrderOwnership($order);

        $this->orderService->delete($order);

        return response()->json(null, 204);
    }

    public function confirm(Order $order): OrderResource
    {
        $this->authorizeOrderOwnership($order);

        $order = $this->orderService->confirm($order);

        return new OrderResource($order);
    }

    public function cancel(Order $order): OrderResource
    {
        $this->authorizeOrderOwnership($order);

        $order = $this->orderService->cancel($order);

        return new OrderResource($order);
    }
}
