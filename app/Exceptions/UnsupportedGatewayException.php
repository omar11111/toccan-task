<?php

namespace App\Exceptions;

use Exception;

class UnsupportedGatewayException extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct("Unsupported payment gateway: [{$method}].");
    }
}
