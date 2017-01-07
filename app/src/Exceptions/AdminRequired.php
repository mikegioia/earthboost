<?php

namespace App\Exceptions;

use App\Exception;

/**
 * Thrown when the adminstrative access to a page is required.
 */
class AdminRequired extends Exception
{
    public $httpCode = 403;
    public $message = "Administrative rights are required.";
}