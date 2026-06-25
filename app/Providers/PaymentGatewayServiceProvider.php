<?php

namespace App\Providers;

use App\PaymentGateways\PaymentGatewayResolver;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayResolver::class, function ($app) {
            return new PaymentGatewayResolver($app);
        });
    }
}
