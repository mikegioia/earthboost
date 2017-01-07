<?php

namespace App\Exceptions;

use App\Exception;

/**
 * Thrown when the user is not logged in.
 */
class Login extends Exception
{
    public $httpCode = 401;
    public $message = "Invalid credentials when authorizing account.";
}