<?php

namespace Tests\Unit\PaymentGateways;

use App\Exceptions\UnsupportedGatewayException;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PaymentGatewayResolver;
use App\PaymentGateways\PaypalGateway;
use Tests\TestCase;

class PaymentGatewayResolverTest extends TestCase
{
    public function test_resolves_credit_card_gateway(): void
    {
        $resolver = $this->app->make(PaymentGatewayResolver::class);

        $gateway = $resolver->resolve('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_resolves_paypal_gateway(): void
    {
        $resolver = $this->app->make(PaymentGatewayResolver::class);

        $gateway = $resolver->resolve('paypal');

        $this->assertInstanceOf(PaypalGateway::class, $gateway);
    }

    public function test_throws_exception_for_unsupported_gateway(): void
    {
        $resolver = $this->app->make(PaymentGatewayResolver::class);

        $this->expectException(UnsupportedGatewayException::class);

        $resolver->resolve('bitcoin');
    }
}
