<?php

namespace App\Exceptions;

use App\Exception;

class Validation extends Exception
{
    public $httpCode = 412;

    public function __construct( $message = "" )
    {
        $message = ( $message ) ?: "Validation error.";

        parent::__construct( $this->statusCode, $message );
    }
}