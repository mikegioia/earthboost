<?php

namespace App\Models;

use App\Model
  , Particle\Validator\Validator
  , App\Exceptions\Validation as ValidationException;

class Group extends Model
{
    public $id;
    public $name;
    public $type;
    public $label;
    public $created_on;

    const TYPE_HOME = 'home';
    const TYPE_OFFICE = 'office';

    protected $_table = 'groups';
    protected $_modelClass = 'Group';

    public function validate( array $data )
    {
        $val = new Validator;
        $val->required( 'name', 'Name' )->lengthBetween( 1, 32 );
        $val->required( 'label', 'State' )->lengthBetween( 1, 255 );
        $val->required( 'type', 'Type' )->inArray([
            self::TYPE_HOME,
            self::TYPE_OFFICE
        ]);
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