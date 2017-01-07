<?php

namespace App\Exceptions;

use App\Exception;

/**
 * Thrown when the access to an entity is denied.
 */
class AccessDenied extends Exception
{
    public $httpCode = 403;

    public function __construct( $type, $id = NULL )
    {
        parent::__construct(
            $this->statusCode,
            sprintf(
                "The requested %s [#%s] could not be accessed.",
                $type,
                ( $id ?: "?" )
            ));
    }
}