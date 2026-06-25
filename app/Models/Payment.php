<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'order_id',
        'payment_method',
        'status',
        'amount',
        'idempotency_key',
        'gateway_reference',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markAsSuccessful(?string $gatewayReference = null, ?array $gatewayResponse = null): void
    {
        $this->update([
            'status' => PaymentStatus::Successful,
            'gateway_reference' => $gatewayReference,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    public function markAsFailed(?array $gatewayResponse = null): void
    {
        $this->update([
            'status' => PaymentStatus::Failed,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}
