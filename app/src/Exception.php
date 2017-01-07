<?php

namespace App;

use Symfony\Component\HttpKernel\Exception\HttpException;

class Exception extends HttpException
{
    // Our HTTP code for the JSON response
    protected $httpCode = 500;
    // Let the client handle the error
    protected $statusCode = 200;

    function getHttpCode()
    {
        return $this->httpCode;
    }

    public function __construct( $message = "" )
    {
        $message = ( $message ) ?: $this->message;

        parent::__construct( $this->statusCode, $message );
    }
}