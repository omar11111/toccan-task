<?php

namespace App\PaymentGateways;

use App\Exceptions\UnsupportedGatewayException;
use App\PaymentGateways\Contracts\PaymentGatewayInterface;
use Illuminate\Container\Container;

class PaymentGatewayResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(string $method): PaymentGatewayInterface
    {
        $gateways = config('payments.gateways', []);

        if (! isset($gateways[$method])) {
            throw new UnsupportedGatewayException($method);
        }

        $definition = $gateways[$method];

        // الـ Container بيحل الـ class ويمرر الـ config كـ constructor params تلقائيًا
        return $this->container->make($definition['class'], $definition['config'] ?? []);
    }
}
