<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:5,1')->prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    /*
    |----------------------------------------------------------------------
    | Orders
    |----------------------------------------------------------------------
    */
    Route::apiResource('orders', OrderController::class);

    Route::patch('/orders/{order}/confirm', [OrderController::class, 'confirm']);
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    /*
    |----------------------------------------------------------------------
    | Payments
    |----------------------------------------------------------------------
    */
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/orders/{order}/payments', [PaymentController::class, 'indexForOrder']);

    Route::post('/orders/{order}/payments', [PaymentController::class, 'process'])
        ->middleware('ensure.idempotency');

});
