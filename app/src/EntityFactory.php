<?php

namespace App;

use App\Entity
  , App\Entities\User
  , App\Entities\Group
  , App\Exceptions\NotFound as NotFoundException;

class EntityFactory
{
    public function make( $objectType, $var = NULL )
    {
        $options = [
            Entity::POPULATE_SQL => TRUE
        ];

        $make = function ( $var ) use ( $objectType, $options ) {
            switch ( $objectType ) {
                case USER:
                    $entity = new User( (int) $var, $options );
                    break;
                case GROUP:
                    $entity = ( is_numeric( $var ) )
                        ? new Group( (int) $var, $options )
                        : Group::loadByName( $var );
                    break;
                default:
                    return NULL;
            }

            if ( ! $entity->exists() ) {
                throw new NotFoundException( $objectType, $var );
            }

            return $entity;
        };

        if ( $var ) {
            return $make( $var );
        }

        return $make;
    }
}