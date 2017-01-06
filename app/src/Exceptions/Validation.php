<?php

namespace App\Exceptions;

use App\Exception;

class Validation extends Exception
{
    public $httpCode = 449;

    public function __construct( $message = "" )
    {
        $message = ( $message ) ?: "Validation error.";

        parent::__construct( $this->statusCode, $message );
    }
}