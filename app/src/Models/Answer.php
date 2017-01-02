<?php

namespace App\Models;

use DateTime
  , App\Model
  , Particle\Validator\Validator
  , App\Exceptions\Validation as ValidationException;

class Answer extends Model
{
    public $id;
    public $year;
    public $answer;
    public $select;
    public $user_id;
    public $group_id;
    public $event_id;
    public $created_on;
    public $question_id;

    protected $_table = 'answers';
    protected $_modelClass = 'Answers';

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
        $val->optional( 'user_id', 'User ID' )->integer();
        $val->required( 'group_id', 'Group ID' )->integer();
        $val->optional( 'event_id', 'Event ID' )->integer();
        $val->required( 'question_id', 'Question ID' )->length( 2 );
        $val->required( 'answer', 'Value' )->lengthBetween( 1, 255 );
        $val->optional( 'select', 'Select' )->lengthBetween( 1, 20 );
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