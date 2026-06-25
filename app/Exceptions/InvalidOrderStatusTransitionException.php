<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;

class InvalidOrderStatusTransitionException extends Exception
{
    public function __construct(OrderStatus $from, OrderStatus $to)
    {
        parent::__construct(
            "Cannot transition order from [{$from->value}] to [{$to->value}]."
        );
    }
}
