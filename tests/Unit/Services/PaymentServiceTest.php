<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\PaymentAlreadyProcessedException;
use App\Models\Order;
use App\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\PaymentGateways\PaymentGatewayResolver;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_charge_marks_payment_successful_and_order_paid(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $fakeGateway = Mockery::mock(PaymentGatewayInterface::class);
        $fakeGateway->shouldReceive('charge')->once()->andReturn([
            'status' => PaymentStatus::Successful->value,
            'gateway_reference' => 'fake_ref_123',
            'raw_response' => ['amount' => 100],
            'failure_reason' => null,
        ]);

        $resolver = Mockery::mock(PaymentGatewayResolver::class);
        $resolver->shouldReceive('resolve')->with('credit_card')->andReturn($fakeGateway);

        $service = new PaymentService($resolver);

        $payment = $service->process($order, 'credit_card', 'unit-test-key-001', []);

        $this->assertEquals(PaymentStatus::Successful, $payment->status);
        $this->assertEquals(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_failed_charge_marks_payment_failed_but_order_remains_confirmed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $fakeGateway = Mockery::mock(PaymentGatewayInterface::class);
        $fakeGateway->shouldReceive('charge')->once()->andReturn([
            'status' => PaymentStatus::Failed->value,
            'gateway_reference' => null,
            'raw_response' => null,
            'failure_reason' => 'Simulated decline.',
        ]);

        $resolver = Mockery::mock(PaymentGatewayResolver::class);
        $resolver->shouldReceive('resolve')->with('credit_card')->andReturn($fakeGateway);

        $service = new PaymentService($resolver);

        $payment = $service->process($order, 'credit_card', 'unit-test-key-002', []);

        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        // أهم assertion في الملف كله: فشل الدفع لا يغير حالة الـ Order أبدًا
        $this->assertEquals(OrderStatus::Confirmed, $order->refresh()->status);
    }

    public function test_throws_exception_when_order_is_not_confirmed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $resolver = Mockery::mock(PaymentGatewayResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(Mockery::mock(PaymentGatewayInterface::class));

        $service = new PaymentService($resolver);

        $this->expectException(OrderNotConfirmedException::class);

        $service->process($order, 'credit_card', 'unit-test-key-003', []);
    }

    public function test_throws_exception_when_order_already_paid(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $resolver = Mockery::mock(PaymentGatewayResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(Mockery::mock(PaymentGatewayInterface::class));

        $service = new PaymentService($resolver);

        $this->expectException(PaymentAlreadyProcessedException::class);

        $service->process($order, 'credit_card', 'unit-test-key-004', []);
    }

    public function test_replaying_same_idempotency_key_does_not_call_gateway_again(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $fakeGateway = Mockery::mock(PaymentGatewayInterface::class);
        // .once() هنا مهم جدًا - لو الـ replay عمل استدعاء تاني للـ Gateway، الـ test يفشل
        $fakeGateway->shouldReceive('charge')->once()->andReturn([
            'status' => PaymentStatus::Successful->value,
            'gateway_reference' => 'fake_ref_456',
            'raw_response' => null,
            'failure_reason' => null,
        ]);

        $resolver = Mockery::mock(PaymentGatewayResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn($fakeGateway);

        $service = new PaymentService($resolver);

        $firstPayment = $service->process($order, 'credit_card', 'same-key-unit', []);
        $secondPayment = $service->process($order, 'credit_card', 'same-key-unit', []);

        $this->assertEquals($firstPayment->id, $secondPayment->id);
    }
}
