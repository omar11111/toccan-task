<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function currentUserId(): string
    {
        return request()->user()->id;
    }
}
