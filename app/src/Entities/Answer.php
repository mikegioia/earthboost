<?php

namespace App\Entities;

use App\Entity
  , App\Entities\User
  , App\Entities\Group
  , App\Libraries\Questions
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

    private $arrayQuestions = [
        Questions::WASTE
    ];

    public function __construct( $id = NULL, $options = [] )
    {
        parent::__construct( $id, $options );

        if ( $this->answer
            && in_array( $this->question_id, $this->arrayQuestions ) )
        {
            $this->answer = json_decode( $this->answer );
        }
    }

    /**
     * Saves the data to the entity in SQL.
     * @param array $data
     */
    public function save( array $data = NULL )
    {
        // Load the ID and other data to not create a dupe.
        $this->checkExists();

        // If the answer is for an array question, encode it
        if ( isset( $data[ 'answer' ] )
            && in_array( $this->question_id, $this->arrayQuestions )
            && is_array( $data[ 'answer' ] ) )
        {
            $data[ 'answer' ] = json_encode( $data[ 'answer' ] );
        }

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
            'question_id' => $this->question_id,
            'user_id' => ( $this->user_id ) ?: NULL
        ];

        if ( $this->event_id ) {
            $params[ 'event_id' ] = $this->event_id;
        }

        $answer = (new AnswerModel)->get( $params );

        if ( $answer && $populate ) {
            $this->populateArray( $answer );
        }

        return ( $answer ) ? TRUE : FALSE;
    }

    /**
     * Fetch all answers for a group and year.
     * @param Group $group
     * @param int $year
     * @return array of Answers
     */
    static public function findByGroup( Group $group, $year )
    {
        $answers = (new AnswerModel)->fetchAll([
            'year' => $year,
            'group_id' => $group->id
        ]);

        return self::hydrate( $answers );
    }

    /**
     * Fetch all answers either for a group or a user. This won't fetch
     * the user answers for a group.
     * @param Group $group
     * @param User $user
     * @param int $year
     * @return array of Answers
     */
    static public function findByUserGroup( Group $group, User $user, $year )
    {
        $answers = (new AnswerModel)->fetchAll([
            'year' => $year,
            'group_id' => $group->id,
            'user_id' => ( $user && $user->exists() )
                ? $user->id
                : NULL
        ]);

        return self::hydrate( $answers );
    }
}