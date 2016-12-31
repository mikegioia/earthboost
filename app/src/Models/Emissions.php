<?php

namespace App\Models;

use DateTime
  , App\Model
  , Particle\Validator\Validator
  , App\Exceptions\Validation as ValidationException;

class Emissions extends Model
{
    public $id;
    public $year;
    public $value;
    public $type_id;
    public $user_id;
    public $group_id;
    public $event_id;
    public $created_on;

    protected $_table = 'emissions';
    protected $_modelClass = 'Emissions';

    public function save( array $data = [], array $options = [] )
    {
        if ( ! valid( $this->id, INT ) ) {
            $data[ 'created_on' ] = (new DateTime)->format( DATE_SQL );
        }

        return parent::save( $data, $options );
    }

    public function validate( array $data )
    {
        $val = new Validator;
        $val->required( 'year', 'Year' )->integer();
        $val->required( 'value', 'Value' )->digits();
        $val->optional( 'user_id', 'User ID' )->integer();
        $val->required( 'group_id', 'Group ID' )->integer();
        $val->required( 'type_id', 'Type ID' )->length( 2 );
        $val->optional( 'event_id', 'Event ID' )->integer();
        $res = $val->validate( $data );

        if ( ! $res->isValid() ) {
            throw new ValidationException(
                $this->getErrorString(
                    $res,
                    "There was a problem validating this group."
                ));
        }
    }
}