<?php

namespace App;

class Exception extends \Exception
{
    public $httpCode = 500;

    public function getHttpCode()
    {
        return $this->httpCode;
    }
}