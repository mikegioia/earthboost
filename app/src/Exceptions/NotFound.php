<?php

namespace App\Exceptions;

use App\Exception;

/**
 * Thrown when the an entity doesn't exist.
 */
class NotFound extends Exception
{
    public $httpCode = 404;
    public $message = "Page not found";

    public function __construct( $type = NULL, $id = NULL, $message = NULL )
    {
        if ( is_null( $type ) && is_null( $id ) ) {
            return parent::__construct( $this->statusCode, $this->message );
        }

        parent::__construct(
            $this->statusCode,
            sprintf(
                "The requested %s [%s] %s.",
                $type,
                ( $id ?: "?" ),
                ( $message ?: "does not exist" )
            ));
    }
}