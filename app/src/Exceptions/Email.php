<?php

namespace App\Exceptions;

use App\Exception;

class Email extends Exception
{
    public $httpCode = 470;

    public function __construct( $message = "" )
    {
        $message = "There was a problem sending an email. $message.";
        $message = trim( $message, ". \t\n\r\0\x0B" );

        parent::__construct( $message );
    }
}