<?php

namespace App\Exceptions;

use App\Exception;

class Database extends Exception
{
    private $httpCode = 412;
}