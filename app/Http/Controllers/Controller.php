<?php

namespace App\Http\Controllers;

use App\Models\Order;

abstract class Controller
{
    protected function currentUserId(): string
    {
        return request()->user()->id;
    }
}
