<?php

namespace App\Models;

use DateTime
  , App\Model
  , Particle\Validator\Validator
  , App\Exception\ValidationException;

class Group extends Model
{
    public $id;
    public $name;
    public $label;
    public $created_on;

    protected $_alias = 'groups';
    protected $_table = 'groups';
    protected $_modelClass = 'Group';

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
        $val->required( 'name', 'Name' )->lengthBetween( 1, 32 );
        $val->required( 'label', 'State' )->lengthBetween( 1, 255 );
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