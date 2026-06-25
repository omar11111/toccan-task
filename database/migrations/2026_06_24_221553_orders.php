<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();

            // snapshot من بيانات الـ user وقت إنشاء الـ order (مش لازم نعتمد على join دايمًا)
            $table->string('customer_name');
            $table->string('customer_email');

            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending'); // pending|confirmed|cancelled|paid

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
