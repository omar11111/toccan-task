<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->string('payment_method'); // credit_card|paypal|...
            $table->string('status')->default('pending'); // pending|successful|failed
            $table->decimal('amount', 10, 2);

            // Idempotency - فريد، نعتمد عليه في الـ lookup
            $table->string('idempotency_key')->unique();

            // مرجع داخلي من الـ Gateway نفسه (لو فيه) - مفيد للـ reconciliation
            $table->string('gateway_reference')->nullable();

            // أي بيانات إضافية من رد الـ Gateway (للتشخيص/الـ logs)
            $table->json('gateway_response')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            // ملحوظة: idempotency_key already indexed via unique()
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
