<?php

// bootstrap/app.php

use App\Exceptions\GatewayDoesNotSupportRefundException;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\OrderHasPaymentsException;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\PaymentAlreadyProcessedException;
use App\Exceptions\UnsupportedGatewayException;
use App\Http\Middleware\EnsureIdempotencyKeyIsPresent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ensure.idempotency' => EnsureIdempotencyKeyIsPresent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidOrderStatusTransitionException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (OrderHasPaymentsException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (OrderNotConfirmedException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (PaymentAlreadyProcessedException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnsupportedGatewayException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (GatewayDoesNotSupportRefundException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();
