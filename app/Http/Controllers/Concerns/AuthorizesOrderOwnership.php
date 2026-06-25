<?php 
// app/Http/Controllers/Concerns/AuthorizesOrderOwnership.php
namespace App\Http\Controllers\Concerns;

use App\Models\Order;

trait AuthorizesOrderOwnership
{
    protected function authorizeOrderOwnership(Order $order): void
    {
        abort_if($order->user_id !== $this->currentUserId(), 403, 'You do not own this order.');
    }
}
