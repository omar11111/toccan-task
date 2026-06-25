<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrderOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use AuthorizesOrderOwnership;

    public function __construct(private readonly PaymentService $paymentService) {}

    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOrderOwnership($order);

        $idempotencyKey = $request->header('Idempotency-Key');

        $payment = $this->paymentService->process(
            order: $order,
            paymentMethod: $request->validated('payment_method'),
            idempotencyKey: $idempotencyKey,
            data: $request->validated('gateway_data', []),
        );

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function indexForOrder(Request $request, Order $order): AnonymousResourceCollection
    {
        $this->authorizeOrderOwnership($order);

        return PaymentResource::collection(
            $order->payments()->latest()->paginate($request->integer('per_page', 15))
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('user_id', $this->currentUserId()))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }
}
