<?php

namespace App\Exceptions;

use App\Models\Order;
use Exception;

class OrderNotConfirmedException extends Exception
{
    public function __construct(Order $order)
    {
        parent::__construct(
            "Order [{$order->id}] must be in 'confirmed' status to process payment. Current status: [{$order->status->value}]."
        );
    }
}
