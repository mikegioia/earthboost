<?php

namespace App\Exceptions;

use App\Exception;

class Database extends Exception
{
    public $httpCode = 412;

    public function __construct( $message = "" )
    {
        $message = ( $message ) ?: "Unknown database error.";

        parent::__construct( $this->statusCode, $message );
    }
}