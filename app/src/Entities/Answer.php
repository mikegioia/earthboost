<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Group
  , App\Models\Answer as AnswerModel;

class Answer extends Entity
{
    public $id;
    public $year;
    public $answer;
    public $select;
    public $user_id;
    public $event_id;
    public $group_id;
    public $question_id;

    protected $_modelClass = 'Answer';

    /**
     * Saves the data to the entity in SQL.
     * @param array $data
     */
    public function save( array $data = NULL )
    {
        // Load the ID and other data to not create a dupe.
        $this->checkExists();

        return parent::save( $data );
    }

    /**
     * Check to see if there's a record for this user/group/question.
     * @param bool $populate Whether to update this object
     * @return bool
     */
    public function checkExists( $populate = TRUE )
    {
        $params = [
            'year' => $this->year,
            'group_id' => $this->group_id,
            'question_id' => $this->question_id
        ];

        if ( $this->user_id ) {
            $params[ 'user_id' ] = $this->user_id;
        }

        if ( $this->event_id ) {
            $params[ 'event_id' ] = $this->event_id;
        }

        $answer = (new AnswerModel)->get( $params );

        if ( $answer && $populate ) {
            $this->populateArray( $answer );
        }

        return ( $answer ) ? TRUE : FALSE;
    }

    static public function findByGroup( Group $group, $year )
    {
        $answers = (new AnswerModel)->fetch([
            'year' => $year,
            'group_id' => $group->id
        ]);

        return self::hydrate( $answers );
    }
}