<?php

namespace App\Exceptions;

use App\Exception;

class NoGroups extends Exception
{
    public $httpCode = 403;
    public $message = "You have no groups!";
}