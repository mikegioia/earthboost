<?php

namespace App\Models;

use App\Model
  , Particle\Validator\Validator
  , App\Exceptions\Validation as ValidationException;

class Inquiry extends Model
{
    public $id;
    public $name;
    public $email;
    public $company;
    public $created_on;

    protected $_table = 'inquiries';
    protected $_modelClass = 'Inquiry';

    public function validate( array $data )
    {
        $val = new Validator;
        $val->optional( 'name', 'Name' )->lengthBetween( 1, 255 );
        $val->required( 'email', 'Email' )->lengthBetween( 1, 255 );
        $val->optional( 'company', 'Company' )->lengthBetween( 1, 255 );
        $res = $val->validate( $data );

        if ( ! $res->isValid() ) {
            throw new ValidationException(
                $this->getErrorString(
                    $res,
                    "There was a problem validating this answer."
                ));
        }
    }
}