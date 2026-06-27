<?php

// bootstrap/app.php

use App\Http\Middleware\EnsureIdempotencyKeyIsPresent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ensure.idempotency' => EnsureIdempotencyKeyIsPresent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\App\Exceptions\InvalidOrderStatusTransitionException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (\App\Exceptions\OrderHasPaymentsException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (\App\Exceptions\OrderNotConfirmedException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (\App\Exceptions\PaymentAlreadyProcessedException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (\App\Exceptions\UnsupportedGatewayException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (\App\Exceptions\GatewayDoesNotSupportRefundException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();
