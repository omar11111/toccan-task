<?php

namespace App\Exceptions;

use Exception;

class PaymentAlreadyProcessedException extends Exception
{
    public function __construct()
    {
        parent::__construct('This order has already been paid for.');
    }
}
