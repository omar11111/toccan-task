<?php

namespace Tests\Unit\PaymentGateways;

use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\CreditCardGateway;
use Tests\TestCase;

class CreditCardGatewayTest extends TestCase
{
    public function test_charge_succeeds_with_normal_card_number(): void
    {
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $order = new Order(['total' => 100]);

        $result = $gateway->charge($order, ['card_number' => '4111111111111111']);

        $this->assertEquals('successful', $result['status']);
        $this->assertNotNull($result['gateway_reference']);
        $this->assertStringStartsWith('cc_', $result['gateway_reference']);
        $this->assertNull($result['failure_reason']);
    }

    public function test_charge_fails_with_card_number_ending_in_0000(): void
    {
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $order = new Order(['total' => 100]);

        $result = $gateway->charge($order, ['card_number' => '4111111111110000']);

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['gateway_reference']);
        $this->assertNotNull($result['failure_reason']);
    }

    public function test_charge_succeeds_when_no_card_number_provided(): void
    {
        // مفيش card_number في الـ data خالص - المفروض ينجح (الفشل بس لو فعليًا اتبعت رقم بينتهي بـ 0000)
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $order = new Order(['total' => 100]);

        $result = $gateway->charge($order, []);

        $this->assertEquals('successful', $result['status']);
    }

    public function test_refund_returns_successful_result(): void
    {
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $payment = new Payment(['amount' => 100]);

        $result = $gateway->refund($payment);

        $this->assertTrue($result['successful']);
        $this->assertNotNull($result['gateway_reference']);
        $this->assertStringStartsWith('rf_', $result['gateway_reference']);
    }

    public function test_refund_uses_partial_amount_when_specified(): void
    {
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $payment = new Payment(['amount' => 100]);

        $result = $gateway->refund($payment, 30.0);

        $this->assertTrue($result['successful']);
        $this->assertEquals(30.0, $result['raw_response']['refunded_amount']);
    }

    public function test_refund_uses_full_payment_amount_when_not_specified(): void
    {
        $gateway = new CreditCardGateway('test-key', 'test-secret');
        $payment = new Payment(['amount' => 100]);

        $result = $gateway->refund($payment);

        $this->assertEquals(100.0, $result['raw_response']['refunded_amount']);
    }
}
