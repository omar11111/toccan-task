<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Exceptions\OrderHasPaymentsException;
use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUuidV7;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- State Transitions (explicit methods, validated centrally via Enum) ---

    public function confirm(): void
    {
        $this->transitionTo(OrderStatus::Confirmed);
    }

    public function cancel(): void
    {
        $this->transitionTo(OrderStatus::Cancelled);
    }

    public function markAsPaid(): void
    {
        $this->transitionTo(OrderStatus::Paid);
    }

    protected function transitionTo(OrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidOrderStatusTransitionException($this->status, $target);
        }

        $this->update(['status' => $target]);
    }

    // --- Business Rules ---

    public function canBeDeleted(): bool
    {
        return ! $this->payments()->exists();
    }

    public function assertCanBeDeleted(): void
    {
        if (! $this->canBeDeleted()) {
            throw new OrderHasPaymentsException();
        }
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()
            ->selectRaw('SUM(quantity * price) as total')
            ->value('total');

        $this->update(['total' => $total ?? 0]);
    }
}
