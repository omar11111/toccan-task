<?php

namespace App\Exceptions;

use Exception;

class OrderHasPaymentsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Cannot delete an order that has associated payments.');
    }
}
