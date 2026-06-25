<?php

namespace App\Exceptions;

use Exception;

class GatewayDoesNotSupportRefundException extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct("Gateway [{$method}] does not support refunds.");
    }
}
