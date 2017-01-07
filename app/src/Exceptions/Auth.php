<?php

namespace App\Exceptions;

use App\Exception;

/**
 * Thrown when the user is not logged in
 */
class Auth extends Exception
{
    public $httpCode = 401;
    public $message = "You must be signed in to access that resource.";
}